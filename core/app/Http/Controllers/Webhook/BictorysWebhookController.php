<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBictorysWebhookJob;
use App\Services\BictorysWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BictorysWebhookController extends Controller
{
    public function __invoke(Request $request, BictorysWebhookService $service): JsonResponse
    {
        $isSecureRequest = $this->isSecureRequest($request);
        $requireHttps = (bool) config('services.bictorys.webhook_require_https', false);

        if (!$isSecureRequest) {
            Log::warning('Bictorys webhook received over non-secure transport', [
                'ip' => $request->ip(),
                'forwarded_proto' => $request->header('X-Forwarded-Proto'),
                'strict_https' => $requireHttps,
            ]);

            if ($requireHttps) {
                return response()->json(['received' => false, 'message' => 'HTTPS required'], 403);
            }
        }

        $rawPayload = (string) $request->getContent();
        $payload = $request->all();
        if (empty($payload) && $rawPayload !== '') {
            $decoded = json_decode($rawPayload, true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $signatureHeader = $request->header('X-Bictorys-Signature')
            ?? $request->header('X-Signature')
            ?? $request->header('X-Webhook-Signature');

        if (!$service->verifySignaturePlaceholder($rawPayload, $signatureHeader)) {
            Log::warning('Bictorys webhook rejected: signature mismatch', [
                'ip' => $request->ip(),
                'has_signature' => !empty($signatureHeader),
            ]);

            return response()->json(['received' => false, 'message' => 'Invalid signature'], 401);
        }

        $validator = Validator::make($payload, [
            'id' => 'sometimes|nullable|string|max:191',
            'eventId' => 'sometimes|nullable|string|max:191',
            'event_id' => 'sometimes|nullable|string|max:191',
            'status' => 'sometimes|nullable|string|max:191',
            'paymentReference' => 'sometimes|nullable|string|max:191',
            'payment_reference' => 'sometimes|nullable|string|max:191',
            'chargeId' => 'sometimes|nullable|string|max:191',
            'charge_id' => 'sometimes|nullable|string|max:191',
        ]);

        if ($validator->fails()) {
            Log::warning('Bictorys webhook payload validation warning', [
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
            ]);
        }

        $context = [
            'gateway' => (string) $request->query('gateway', ''),
            'verification_token' => (string) $request->query('vtoken', ''),
            'received_at' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ];

        $queueEnabled = (bool) config('services.bictorys.webhook_queue_enabled', false);
        $processInline = (bool) config('services.bictorys.webhook_process_inline', true);

        if ($queueEnabled) {
            // Queue-first mode when worker infrastructure is available.
            ProcessBictorysWebhookJob::dispatchAfterResponse(
                $payload,
                $rawPayload,
                $context
            )->onQueue('webhooks');
        }

        if ($processInline) {
            try {
                // Inline fallback keeps production stable even when queue workers are down.
                $result = $service->processWebhook($payload, $rawPayload, $context);
                Log::info('Bictorys webhook processed inline', [
                    'result' => $result,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Bictorys webhook inline processing failed', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json(['received' => true], 200);
    }

    private function isSecureRequest(Request $request): bool
    {
        if ($request->isSecure()) {
            return true;
        }

        $forwardedProto = strtolower((string) $request->header('X-Forwarded-Proto'));

        return $forwardedProto === 'https';
    }
}
