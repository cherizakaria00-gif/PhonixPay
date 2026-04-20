<?php

namespace App\Http\Controllers\Webhook;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\DiditVerificationSession;
use App\Models\User;
use App\Services\Didit\DiditSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiditWebhookController extends Controller
{
    public function __construct(private readonly DiditSessionService $diditSessionService)
    {
    }

    public function __invoke(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = (string) $request->header('X-Signature-V2', '');

        if (!$this->diditSessionService->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('didit_webhook_invalid_signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'message' => 'invalid_signature'], 401);
        }

        $payload = $request->json()->all();
        $sessionId = trim((string) Arr::get($payload, 'session_id', ''));

        if ($sessionId === '') {
            return response()->json(['ok' => true, 'message' => 'ignored']);
        }

        $normalizedStatus = $this->diditSessionService->normalizeStatus((string) Arr::get($payload, 'status', 'Not Started'));
        $decision = Arr::get($payload, 'decision');

        try {
            $session = DiditVerificationSession::query()->firstOrNew([
                'session_id' => $sessionId,
            ]);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'module_not_ready'], 503);
        }

        $vendorData = trim((string) Arr::get($payload, 'vendor_data', ''));
        $userId = $session->user_id ?: (ctype_digit($vendorData) ? (int) $vendorData : null);

        if ($userId) {
            $session->user_id = $userId;
        }

        $session->workflow_id = (string) Arr::get($payload, 'workflow_id', $session->workflow_id);
        $session->status = $normalizedStatus;
        $session->vendor_data = $vendorData !== '' ? $vendorData : $session->vendor_data;
        $session->verification_url = (string) Arr::get($payload, 'verification_url', $session->verification_url);
        $session->decision = is_array($decision) ? $decision : $session->decision;
        $session->last_webhook_at = now();

        if ($session->opened_at === null) {
            $session->opened_at = now();
        }

        if (in_array($normalizedStatus, ['approved', 'declined', 'expired', 'abandoned'], true)) {
            $session->completed_at = now();
        }

        $session->save();

        if ($session->user_id) {
            /** @var User|null $user */
            $user = User::query()->find($session->user_id);
            if ($user) {
                $user->identity_verification_status = $normalizedStatus;
                $user->didit_last_session_id = $session->session_id;

                if (is_array($session->decision)) {
                    $user->didit_decision = $session->decision;
                }

                if ($normalizedStatus === 'approved') {
                    $user->didit_verified_at = now();

                    // Promote to platform KYC verified only when Didit approves.
                    if ((int) $user->kv !== Status::KYC_VERIFIED) {
                        $user->kv = Status::KYC_VERIFIED;
                        $user->kyc_rejection_reason = null;
                    }

                    $this->notifyAdmin($user, 'Didit identity approved for ' . ($user->username ?: ('User #' . $user->id)));
                }

                if ($normalizedStatus === 'declined') {
                    $this->notifyAdmin($user, 'Didit identity declined for ' . ($user->username ?: ('User #' . $user->id)));
                }

                if ($normalizedStatus === 'in_review' && (int) $user->kv === Status::KYC_UNVERIFIED) {
                    $user->kv = Status::KYC_PENDING;
                }

                $user->save();
            }
        }

        return response()->json(['ok' => true]);
    }

    private function notifyAdmin(User $user, string $title): void
    {
        $notification = new AdminNotification();
        $notification->user_id = $user->id;
        $notification->title = $title;
        $notification->click_url = urlPath('admin.users.detail', $user->id);
        $notification->save();
    }
}
