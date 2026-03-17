<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Http\Resources\Mobile\UserResource;
use App\Lib\SocialLogin;
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
            'username' => 'nullable|string|required_without:email',
            'email' => 'nullable|string|required_without:username',
            'password' => 'required|string',
        ]);

        $login = trim((string) ($request->input('username') ?: $request->input('email')));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $login, 'password' => (string) $request->input('password')])) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'errors' => [
                    $field === 'email' ? 'email' : 'username' => ['The provided credentials are incorrect.'],
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

    public function registerMobile(Request $request)
    {
        $request->validate([
            'account.firstname' => 'required|string|max:191',
            'account.lastname' => 'required|string|max:191',
            'account.email' => 'required|email|max:191',
            'account.password' => 'required|string|min:6',
            'account.password_confirmation' => 'required|string|same:account.password',
            'account.agree' => 'accepted',
            'profile.username' => 'required|string|min:6|max:191',
            'profile.mobile' => 'required|string|max:50',
            'profile.dial_code' => 'required|string|max:20',
            'profile.country' => 'required|string|max:191',
            'profile.country_code' => 'required|string|max:10',
            'profile.city' => 'nullable|string|max:191',
            'profile.state' => 'nullable|string|max:191',
            'profile.zip' => 'nullable|string|max:50',
            'profile.address' => 'nullable|string|max:255',
        ]);

        $request->merge([
            'firstname' => data_get($request, 'account.firstname'),
            'lastname' => data_get($request, 'account.lastname'),
            'email' => data_get($request, 'account.email'),
            'password' => data_get($request, 'account.password'),
            'password_confirmation' => data_get($request, 'account.password_confirmation'),
            'username' => data_get($request, 'profile.username'),
            'mobile' => data_get($request, 'profile.mobile'),
            'dial_code' => data_get($request, 'profile.dial_code'),
            'country' => data_get($request, 'profile.country'),
            'country_code' => data_get($request, 'profile.country_code'),
            'city' => data_get($request, 'profile.city'),
            'state' => data_get($request, 'profile.state'),
            'zip' => data_get($request, 'profile.zip'),
            'address' => data_get($request, 'profile.address'),
        ]);

        return $this->register($request);
    }

    public function socialLogin(Request $request, string $provider)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        if (!in_array($provider, ['google', 'facebook', 'linkedin'], true)) {
            return response()->json([
                'message' => 'Unsupported social provider.',
                'errors' => [
                    'provider' => ['Unsupported social provider.'],
                ],
            ], 422);
        }

        try {
            $result = (new SocialLogin($provider, true))->login();
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'Unable to login with social provider.',
                'errors' => [
                    'token' => [$exception->getMessage() ?: 'Unable to login with social provider.'],
                ],
            ], 422);
        }

        /** @var User $user */
        $user = $result['user'];

        return $this->ok([
            'token' => $result['access_token'],
            'user' => (new UserResource($user->fresh()))->toArray($request),
        ]);
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
