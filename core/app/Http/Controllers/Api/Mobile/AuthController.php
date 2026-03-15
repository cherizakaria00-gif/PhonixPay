<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Http\Resources\Mobile\UserResource;
use App\Models\AdminNotification;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends ApiMobileController
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim((string) $request->input('username'));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $login, 'password' => (string) $request->input('password')])) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'errors' => [
                    'username' => ['The provided credentials are incorrect.'],
                ],
            ], 422);
        }

        /** @var User $user */
        $user = Auth::user();

        $user->tv = (int) $user->ts === Status::VERIFIED ? Status::UNVERIFIED : Status::VERIFIED;
        $user->save();

        $this->storeLoginLog($user);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user' => (new UserResource($user->fresh()))->toArray($request),
        ]);
    }

    public function register(Request $request)
    {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $request->validate([
            'firstname' => 'required|string|max:191',
            'lastname' => 'required|string|max:191',
            'username' => ['required', 'string', 'min:6', 'max:191', 'regex:/^[a-z0-9_]+$/', Rule::unique('users', 'username')],
            'email' => 'required|email|max:191|unique:users,email',
            'password' => ['required', 'confirmed', $passwordValidation],
            'mobile' => ['required', 'string', Rule::unique('users', 'mobile')->where('dial_code', $request->dial_code)],
            'dial_code' => 'required|string|max:20',
            'country' => 'required|string|max:191',
            'country_code' => 'required|string|max:10',
            'city' => 'nullable|string|max:191',
            'state' => 'nullable|string|max:191',
            'zip' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        if (!gs('registration')) {
            return response()->json([
                'message' => 'Registration not allowed.',
                'errors' => [
                    'registration' => ['Registration not allowed.'],
                ],
            ], 422);
        }

        $user = new User();
        $user->email = strtolower((string) $request->email);
        $user->firstname = (string) $request->firstname;
        $user->lastname = (string) $request->lastname;
        $user->username = (string) $request->username;
        $user->password = Hash::make((string) $request->password);
        $user->mobile = (string) $request->mobile;
        $user->dial_code = (string) $request->dial_code;
        $user->country_name = (string) $request->country;
        $user->country_code = strtoupper((string) $request->country_code);
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->address = $request->address;

        $user->ref_by = 0;
        $user->kv = gs('kv') ? Status::NO : Status::YES;
        $user->ev = gs('ev') ? Status::NO : Status::YES;
        $user->sv = gs('sv') ? Status::NO : Status::YES;
        $user->ts = Status::DISABLE;
        $user->tv = Status::ENABLE;
        $user->profile_complete = Status::YES;

        if (Schema::hasTable('plans') && Schema::hasColumn('users', 'plan_id')) {
            $starterPlan = Plan::query()->where('slug', 'starter')->first();
            if ($starterPlan) {
                $user->plan_id = $starterPlan->id;
                $user->plan_status = 'active';
                $user->plan_started_at = now();
                $user->monthly_tx_count = 0;
                $user->monthly_tx_count_reset_at = now()->startOfMonth();
            }
        }

        $user->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New member registered';
        $adminNotification->click_url = urlPath('admin.users.detail', $user->id);
        $adminNotification->save();

        $this->storeLoginLog($user);

        Auth::login($user);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user' => (new UserResource($user->fresh()))->toArray($request),
        ], 201);
    }

    public function me(Request $request)
    {
        return $this->ok([
            'user' => (new UserResource($request->user()))->toArray($request),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->message('Logged out successfully');
    }

    private function storeLoginLog(User $user): void
    {
        $ip = getRealIP();
        $existing = UserLogin::where('user_ip', $ip)->first();

        $log = new UserLogin();

        if ($existing) {
            $log->longitude = $existing->longitude;
            $log->latitude = $existing->latitude;
            $log->city = $existing->city;
            $log->country_code = $existing->country_code;
            $log->country = $existing->country;
        } else {
            $info = json_decode(json_encode(getIpInfo()), true);
            $log->longitude = @implode(',', $info['long'] ?? []);
            $log->latitude = @implode(',', $info['lat'] ?? []);
            $log->city = @implode(',', $info['city'] ?? []);
            $log->country_code = @implode(',', $info['code'] ?? []);
            $log->country = @implode(',', $info['country'] ?? []);
        }

        $userAgent = osBrowser();

        $log->user_id = $user->id;
        $log->user_ip = $ip;
        $log->browser = $userAgent['browser'] ?? null;
        $log->os = $userAgent['os_platform'] ?? null;
        $log->save();
    }
}
