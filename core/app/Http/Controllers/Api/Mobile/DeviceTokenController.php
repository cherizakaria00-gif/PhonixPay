<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string|min:20|max:512',
        ]);

        $existing = DeviceToken::where('token', $request->token)->first();
        if ($existing) {
            // Re-associate to current user in case token was previously from another account
            if ($existing->user_id !== auth()->id()) {
                $existing->user_id = auth()->id();
                $existing->is_app = 1;
                $existing->save();
            }
            return response()->json(['success' => true, 'message' => 'Token registered']);
        }

        $deviceToken = new DeviceToken();
        $deviceToken->user_id = auth()->id();
        $deviceToken->token = $request->token;
        $deviceToken->is_app = 1;
        $deviceToken->save();

        return response()->json(['success' => true, 'message' => 'Device token registered']);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'token' => 'required|string|min:20|max:512',
        ]);

        DeviceToken::where('token', $request->token)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['success' => true, 'message' => 'Device token removed']);
    }
}
