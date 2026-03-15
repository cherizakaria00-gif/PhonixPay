<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Http\Resources\Mobile\ProfileSettingsResource;
use App\Lib\GoogleAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends ApiMobileController
{
    public function settings(Request $request)
    {
        return $this->ok([
            'profile' => (new ProfileSettingsResource($request->user()))->toArray($request),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'firstname' => 'required|string|max:191',
            'lastname' => 'required|string|max:191',
            'email' => 'required|email|max:191|unique:users,email,' . $user->id,
            'mobile' => 'required|string|max:50',
            'city' => 'nullable|string|max:191',
            'state' => 'nullable|string|max:191',
            'zip' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $emailChanged = strtolower((string) $request->email) !== strtolower((string) $user->email);
        $mobileChanged = (string) $request->mobile !== (string) $user->mobile;

        $user->firstname = (string) $request->firstname;
        $user->lastname = (string) $request->lastname;
        $user->email = strtolower((string) $request->email);
        $user->mobile = (string) $request->mobile;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->address = $request->address;

        if ($emailChanged || $mobileChanged) {
            $user->ev = Status::UNVERIFIED;
            $user->ver_code = null;
            $user->ver_code_send_at = null;
        }

        $user->save();

        return $this->ok([
            'success' => true,
            'message' => $emailChanged || $mobileChanged
                ? 'Profile updated. Please verify your email to continue.'
                : 'Profile updated successfully.',
            'profile' => (new ProfileSettingsResource($user->fresh()))->toArray($request),
        ]);
    }

    public function changePassword(Request $request)
    {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', $passwordValidation],
        ]);

        $user = $request->user();
        if (!Hash::check((string) $request->current_password, (string) $user->password)) {
            return response()->json([
                'message' => 'Current password is invalid.',
                'errors' => [
                    'current_password' => ['The password does not match.'],
                ],
            ], 422);
        }

        $user->password = Hash::make((string) $request->password);
        $user->save();

        return $this->message('Password changed successfully');
    }

    public function twoFactorStatus(Request $request)
    {
        $user = $request->user();

        $ga = new GoogleAuthenticator();
        $secret = $user->tsc ?: $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl(($user->username ?: $user->email) . '@' . gs('site_name'), $secret);

        return $this->ok([
            'enabled' => (int) $user->ts === Status::ENABLE,
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function enableTwoFactor(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'code' => 'required|string',
        ]);

        $user = $request->user();
        if (!verifyG2fa($user, $request->code, $request->key)) {
            return response()->json([
                'message' => 'Wrong verification code.',
                'errors' => [
                    'code' => ['Wrong verification code.'],
                ],
            ], 422);
        }

        $user->tsc = (string) $request->key;
        $user->ts = Status::ENABLE;
        $user->save();

        return $this->message('Two factor authenticator activated successfully');
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        if (!verifyG2fa($user, $request->code)) {
            return response()->json([
                'message' => 'Wrong verification code.',
                'errors' => [
                    'code' => ['Wrong verification code.'],
                ],
            ], 422);
        }

        $user->tsc = null;
        $user->ts = Status::DISABLE;
        $user->save();

        return $this->message('Two factor authenticator deactivated successfully');
    }
}
