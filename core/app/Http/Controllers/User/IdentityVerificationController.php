<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DiditVerificationSession;
use App\Services\Didit\DiditSessionService;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class IdentityVerificationController extends Controller
{
    public function __construct(private readonly DiditSessionService $diditSessionService)
    {
    }

    public function index()
    {
        $pageTitle = 'Identity Verification';
        $user = auth()->user();

        try {
            $latestSession = DiditVerificationSession::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            $history = DiditVerificationSession::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(10)
                ->get();
        } catch (Throwable) {
            $notify[] = ['error', 'Identity verification module is not ready yet. Please contact admin.'];
            return to_route('user.home')->withNotify($notify);
        }

        $isConfigured = $this->diditSessionService->isConfigured();

        return view('Template::user.identity_verification.index', compact(
            'pageTitle',
            'latestSession',
            'history',
            'isConfigured'
        ));
    }

    public function createSession(Request $request)
    {
        $user = $request->user();

        try {
            $session = $this->diditSessionService->createSessionForUser($user);
        } catch (RuntimeException|Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification session created successfully.',
            'data' => $session,
        ]);
    }

    public function callback(Request $request)
    {
        $sessionId = trim((string) $request->query('session_id', ''));

        $notify[] = ['success', 'Verification flow completed. Status will update automatically.'];

        if ($sessionId !== '') {
            return to_route('user.identity.index', ['session' => $sessionId])->withNotify($notify);
        }

        return to_route('user.identity.index')->withNotify($notify);
    }

    public function start(Request $request)
    {
        try {
            $session = $this->diditSessionService->createSessionForUser($request->user());
        } catch (RuntimeException|Throwable $e) {
            $notify[] = ['error', $e->getMessage()];
            return to_route('user.identity.index')->withNotify($notify);
        }

        $verificationUrl = trim((string) ($session['verification_url'] ?? ''));
        if ($verificationUrl === '') {
            $notify[] = ['error', 'Verification URL is missing. Please try again.'];
            return to_route('user.identity.index')->withNotify($notify);
        }

        return redirect()->away($verificationUrl);
    }
}
