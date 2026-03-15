<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuthorizationController extends ApiMobileController
{
    public function status(Request $request)
    {
        $user = $request->user();

        [$type, $message] = $this->resolveAuthorizationState($user);
        $retryAfter = $this->retryAfter($user);

        return $this->ok([
            'type' => $type,
            'message' => $message,
            'retry_after' => $retryAfter,
            'user' => [
                'email' => (string) $user->email,
                'mobile' => (string) $user->mobile,
                'email_masked' => $this->maskEmail((string) $user->email),
                'mobile_masked' => $this->maskMobile((string) $user->mobile),
                'ev' => (int) $user->ev,
                'sv' => (int) $user->sv,
                'tv' => (int) $user->tv,
                'status' => (int) $user->status,
            ],
        ]);
    }

    public function resend(Request $request)
    {
        $user = $request->user();
        [$type] = $this->resolveAuthorizationState($user);

        if (in_array($type, ['none', '2fa', 'ban'], true)) {
            return $this->message('No verification code is required for this account.', false, 422);
        }

        $retryAfter = $this->retryAfter($user);
        if ($retryAfter > 0) {
            return $this->message('Please try again later.', false, 429, $retryAfter);
        }

        $user->ver_code = verificationCode(6);
        $user->ver_code_send_at = Carbon::now();
        $user->save();

        if ($type === 'email') {
            notify($user, 'EVER_CODE', ['code' => $user->ver_code], ['email']);
        }

        if ($type === 'sms') {
            notify($user, 'SVER_CODE', ['code' => $user->ver_code], ['sms']);
        }

        return $this->message('Verification code sent successfully');
    }

    public function updateEmail(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $newEmail = strtolower(trim((string) $request->email));
        if ($newEmail === strtolower((string) $user->email)) {
            return $this->message('This email address is already in use.', false, 422);
        }

        $user->email = $newEmail;
        $user->ev = Status::UNVERIFIED;
        $user->ver_code = verificationCode(6);
        $user->ver_code_send_at = Carbon::now();
        $user->save();

        notify($user, 'EVER_CODE', ['code' => $user->ver_code], ['email']);

        return $this->message('Email updated successfully. A new verification code has been sent.');
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        if ((string) $user->ver_code !== (string) $request->code) {
            return response()->json([
                'message' => 'Verification code did not match.',
                'errors' => [
                    'code' => ['Verification code did not match.'],
                ],
            ], 422);
        }

        $user->ev = Status::VERIFIED;
        $user->ver_code = null;
        $user->ver_code_send_at = null;
        $user->save();

        return $this->message('Email verified successfully');
    }

    public function verifyMobile(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        if ((string) $user->ver_code !== (string) $request->code) {
            return response()->json([
                'message' => 'Verification code did not match.',
                'errors' => [
                    'code' => ['Verification code did not match.'],
                ],
            ], 422);
        }

        $user->sv = Status::VERIFIED;
        $user->ver_code = null;
        $user->ver_code_send_at = null;
        $user->save();

        return $this->message('Mobile number verified successfully');
    }

    public function verifyTwoFactor(Request $request)
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

        $user->tv = Status::VERIFIED;
        $user->save();

        return $this->message('2FA verification completed successfully');
    }

    private function resolveAuthorizationState($user): array
    {
        if ((int) $user->status !== Status::USER_ACTIVE) {
            return ['ban', 'Your account is banned. Please contact support.'];
        }

        if ((int) $user->ev !== Status::VERIFIED) {
            return ['email', 'Please verify your email address.'];
        }

        if ((int) $user->sv !== Status::VERIFIED) {
            return ['sms', 'Please verify your mobile number.'];
        }

        if ((int) $user->tv !== Status::VERIFIED) {
            return ['2fa', 'Please complete your two-factor authentication.'];
        }

        return ['none', 'Account authorization is complete.'];
    }

    private function retryAfter($user): int
    {
        if (!$user->ver_code_send_at) {
            return 0;
        }

        $target = $user->ver_code_send_at->copy()->addMinutes(2)->timestamp;

        return max(0, $target - time());
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return $email;
        }

        $prefix = substr($local, 0, min(2, strlen($local)));

        return $prefix . str_repeat('*', max(2, strlen($local) - strlen($prefix))) . '@' . $domain;
    }

    private function maskMobile(string $mobile): string
    {
        $len = strlen($mobile);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($mobile, -4);
    }
}
