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
        Log::info('Bictorys webhook received', [
            'method' => $request->method(),
            'path' => $request->path(),
            'gateway' => (string) $request->query('gateway', ''),
            'ip' => $request->ip(),
        ]);

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

        // Default to inline processing to avoid dependency on queue workers in production.
        // If async is explicitly enabled, queue the job with a safe inline fallback.
        $asyncEnabled = (bool) config('services.bictorys.webhook_async', false);

        if ($asyncEnabled) {
            try {
                ProcessBictorysWebhookJob::dispatchAfterResponse(
                    $payload,
                    $rawPayload,
                    $context
                )->onQueue((string) config('services.bictorys.webhook_queue', 'webhooks'));
            } catch (\Throwable $e) {
                Log::warning('Bictorys webhook queue dispatch failed; processing inline fallback', [
                    'error' => $e->getMessage(),
                ]);

                $result = $service->processWebhook($payload, $rawPayload, $context);
                Log::info('Bictorys webhook processed inline fallback', ['result' => $result]);
            }
        } else {
            $result = $service->processWebhook($payload, $rawPayload, $context);
            Log::info('Bictorys webhook processed inline', ['result' => $result]);
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
