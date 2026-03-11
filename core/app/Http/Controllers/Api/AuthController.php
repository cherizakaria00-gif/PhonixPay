<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower((string) $request->email))->first();

        if (!$user || !Hash::check((string) $request->password, (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:6'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'dial_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user = new User();
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->username = $request->username;
        $user->email = strtolower((string) $request->email);
        $user->password = Hash::make((string) $request->password);
        $user->mobile = $request->mobile;
        $user->dial_code = $request->dial_code;
        $user->country = $request->country;
        $user->country_code = $request->country_code;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->address = $request->address;
        $user->status = $user->status ?? Status::ENABLE;
        $user->ev = $user->ev ?? Status::YES;
        $user->sv = $user->sv ?? Status::YES;
        $user->tv = $user->tv ?? Status::YES;
        $user->save();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => trim((string) (($user->firstname ?? '') . ' ' . ($user->lastname ?? ''))),
            'email' => $user->email,
            'username' => $user->username,
            'balance' => (float) ($user->balance ?? 0),
            'currency' => $user->currency ?? 'USD',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'mobile' => $user->mobile,
            'city' => $user->city,
            'state' => $user->state,
            'zip' => $user->zip,
            'address' => $user->address,
            'country' => $user->country ?? $user->country_name ?? null,
            'ev' => (int) ($user->ev ?? 0),
            'sv' => (int) ($user->sv ?? 0),
            'tv' => (int) ($user->tv ?? 0),
            'status' => (int) ($user->status ?? 0),
        ];
    }
}

