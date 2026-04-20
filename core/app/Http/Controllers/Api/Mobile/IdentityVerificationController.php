<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\DiditVerificationSession;
use App\Services\Didit\DiditSessionService;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class IdentityVerificationController extends ApiMobileController
{
    public function __construct(private readonly DiditSessionService $diditSessionService)
    {
    }

    public function status(Request $request)
    {
        $user = $request->user();

        $latest = null;
        try {
            $latest = DiditVerificationSession::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();
        } catch (Throwable) {
            $latest = null;
        }

        return $this->ok([
            'configured' => $this->diditSessionService->isConfigured(),
            'identity_verification_status' => $user->identity_verification_status,
            'didit_verified_at' => optional($user->didit_verified_at)->toIso8601String(),
            'latest_session' => $latest ? [
                'session_id' => $latest->session_id,
                'status' => $latest->status,
                'verification_url' => $latest->verification_url,
                'opened_at' => optional($latest->opened_at)->toIso8601String(),
                'completed_at' => optional($latest->completed_at)->toIso8601String(),
            ] : null,
        ]);
    }

    public function createSession(Request $request)
    {
        try {
            $session = $this->diditSessionService->createSessionForUser($request->user());
        } catch (RuntimeException|Throwable $e) {
            return response()->json([
                'message' => 'Identity verification module is not ready yet.',
                'errors' => [
                    'identity' => ['Module not ready'],
                ],
            ], 422);
        }

        return $this->ok([
            'success' => true,
            'message' => 'Verification session created successfully.',
            'session' => $session,
        ]);
    }
}
