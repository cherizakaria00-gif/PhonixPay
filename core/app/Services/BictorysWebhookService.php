<?php

namespace App\Services;

use App\Constants\Status;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\BictorysWebhookEvent;
use App\Models\Deposit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BictorysWebhookService
{
    private const PROVIDER = 'bictorys';

    public function verifySignaturePlaceholder(string $rawPayload, ?string $signatureHeader): bool
    {
        $secret = trim((string) config('services.bictorys.webhook_secret', ''));
        $signatureRequired = (bool) config('services.bictorys.webhook_require_signature', false);

        // Placeholder verification: if no secret is configured, keep compatibility but log in strict mode.
        if ($secret === '') {
            if ($signatureRequired) {
                Log::warning('Bictorys webhook signature rejected: secret missing while strict mode is enabled');
                return false;
            }

            return true;
        }

        $provided = $this->normalizeSignatureValue($signatureHeader);
        if ($provided === '') {
            return !$signatureRequired;
        }

        $expected = hash_hmac('sha256', $rawPayload, $secret);

        return hash_equals(strtolower($expected), strtolower($provided));
    }

    public function processWebhook(array $payload, string $rawPayload = '', array $context = []): array
    {
        $payloadSource = $rawPayload;
        if ($payloadSource === '') {
            $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $payloadSource = is_string($encoded) ? $encoded : serialize($payload);
        }
        $payloadHash = hash('sha256', $payloadSource);
        $gatewayAlias = $this->normalizeGatewayAlias((string) ($context['gateway'] ?? ''));
        $verificationToken = trim((string) ($context['verification_token'] ?? ''));

        $eventId = $this->extractFirstScalar($payload, [
            'eventId',
            'event_id',
            'id',
            'data.eventId',
            'data.event_id',
            'data.id',
        ]);

        $chargeId = $this->extractFirstScalar($payload, [
            'chargeId',
            'charge_id',
            'payment.chargeId',
            'payment.charge_id',
            'data.chargeId',
            'data.charge_id',
            'data.data.chargeId',
            'data.data.charge_id',
        ]);

        $paymentReference = $this->extractFirstScalar($payload, [
            'paymentReference',
            'payment_reference',
            'merchantReference',
            'merchant_reference',
            'reference',
            'payment.reference',
            'data.paymentReference',
            'data.payment_reference',
            'data.reference',
            'data.merchantReference',
            'data.merchant_reference',
        ]);

        $eventUid = $this->buildEventUid($eventId, $payloadHash, $gatewayAlias, $chargeId, $paymentReference);

        $persistEvents = Schema::hasTable('bictorys_webhook_events');
        $event = null;

        if ($persistEvents) {
            $event = $this->findOrCreateEvent(
                $eventUid,
                $eventId,
                $gatewayAlias,
                $chargeId,
                $paymentReference,
                $payloadHash,
                $payload
            );

            if (!$event->wasRecentlyCreated && $event->processed_at !== null) {
                return ['duplicate' => true, 'event_uid' => $eventUid];
            }
        } else {
            if (!Cache::add('flujipay_bictorys_webhook_event_fallback_' . sha1($eventUid), now()->timestamp, 86400)) {
                return ['duplicate' => true, 'event_uid' => $eventUid, 'source' => 'cache_fallback'];
            }

            Log::warning('Bictorys webhook event table missing; using cache-only idempotency fallback');
        }

        $lockKey = 'flujipay_bictorys_webhook_event_lock_' . sha1($eventUid);
        if (!Cache::add($lockKey, now()->timestamp, 30)) {
            return ['locked' => true, 'event_uid' => $eventUid];
        }

        try {
            if ($event) {
                $event->attempts = (int) $event->attempts + 1;
                $event->status = 'processing';
                $event->save();
            }

            $references = $this->extractReferences($payload);
            $status = $this->extractStatus($payload);
            $successFlag = $this->extractSuccessFlag($payload);

            $deposit = $this->findDeposit($references, $verificationToken, $gatewayAlias);

            if (!$deposit) {
                if ($event) {
                    $event->status = 'ignored_unmatched';
                    $event->processed_at = now();
                    $event->save();
                }

                Log::warning('Bictorys webhook ignored: deposit not found', [
                    'event_uid' => $eventUid,
                    'references' => $references,
                    'gateway_alias' => $gatewayAlias,
                ]);

                return ['ignored' => 'deposit_not_found', 'event_uid' => $eventUid];
            }

            if ($event) {
                $event->deposit_id = $deposit->id;
                $event->charge_id = $event->charge_id ?: $this->normalizeReference($deposit->btc_wallet);
                $event->payment_reference = $event->payment_reference ?: $this->normalizeReference($deposit->trx);
                $event->gateway_alias = $event->gateway_alias ?: $gatewayAlias;
                $event->save();
            }

            $this->trackWebhookReceipt($deposit, $status, $eventUid);

            if (!$deposit->btc_wallet && !empty($references)) {
                $chargeReference = $this->resolveChargeReference($references, (string) $deposit->trx);
                if ($chargeReference !== null) {
                    $deposit->btc_wallet = $chargeReference;
                    $deposit->save();
                }
            }

            $isFailure = ($successFlag === false) || $this->isFailureStatus($status);
            $isSuccess = $this->isSuccessPayload($payload, $status, $successFlag);

            if ($isFailure && in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
                $deposit->status = Status::PAYMENT_REJECT;
                $deposit->save();

                if ($deposit->apiPayment) {
                    $deposit->apiPayment->status = Status::PAYMENT_REJECT;
                    $deposit->apiPayment->save();
                }

                if ($event) {
                    $event->status = 'processed_rejected';
                    $event->processed_at = now();
                    $event->save();
                }

                return ['processed' => 'rejected', 'deposit_id' => (int) $deposit->id, 'event_uid' => $eventUid];
            }

            if ($isSuccess && in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING, Status::PAYMENT_REJECT], true)) {
                // Webhook-first finalization: one atomic path used by all payment methods.
                PaymentController::userDataUpdate((int) $deposit->id);

                if ($event) {
                    $event->status = 'processed_paid';
                    $event->processed_at = now();
                    $event->save();
                }

                return ['processed' => 'paid', 'deposit_id' => (int) $deposit->id, 'event_uid' => $eventUid];
            }

            if ($event) {
                $event->status = 'ignored_no_state_change';
                $event->processed_at = now();
                $event->save();
            }

            return ['ignored' => 'no_state_change', 'deposit_id' => (int) $deposit->id, 'event_uid' => $eventUid];
        } catch (\Throwable $exception) {
            if ($event) {
                $event->status = 'failed';
                $event->last_error = $exception->getMessage();
                $event->save();
            }

            throw $exception;
        } finally {
            Cache::forget($lockKey);
        }
    }

    private function findOrCreateEvent(
        string $eventUid,
        ?string $eventId,
        ?string $gatewayAlias,
        ?string $chargeId,
        ?string $paymentReference,
        string $payloadHash,
        array $payload
    ): BictorysWebhookEvent {
        $existing = BictorysWebhookEvent::query()
            ->where('event_uid', $eventUid)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return BictorysWebhookEvent::query()->create([
                'event_uid' => $eventUid,
                'provider' => self::PROVIDER,
                'event_id' => $eventId,
                'gateway_alias' => $gatewayAlias,
                'charge_id' => $chargeId,
                'payment_reference' => $paymentReference,
                'payload_hash' => $payloadHash,
                'payload' => $payload,
                'status' => 'queued',
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateWebhookEventException($exception)) {
                $existing = BictorysWebhookEvent::query()
                    ->where('event_uid', $eventUid)
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            throw $exception;
        }
    }

    private function isDuplicateWebhookEventException(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, ['1062', '19'], true);
    }

    private function findDeposit(array $references, string $verificationToken, ?string $gatewayAlias): ?Deposit
    {
        $processableStatuses = [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING, Status::PAYMENT_REJECT];

        $deposit = $this->findDepositByReferences($references, $processableStatuses, $gatewayAlias);
        if ($deposit) {
            return $deposit;
        }

        if ($verificationToken !== '') {
            $deposit = $this->findDepositByVerificationToken($verificationToken, $gatewayAlias, $processableStatuses, 5000);
            if ($deposit) {
                return $deposit;
            }
        }

        return $this->findDepositByReferences($references, null, $gatewayAlias);
    }

    private function findDepositByReferences(array $references, ?array $statuses, ?string $gatewayAlias): ?Deposit
    {
        if (empty($references)) {
            return null;
        }

        $normalizedReferences = array_values(array_unique(array_filter(array_map(
            fn($reference) => $this->normalizeReference($reference),
            $references
        ))));

        $query = Deposit::query();

        if ($statuses !== null) {
            $query->whereIn('status', $statuses);
        }

        if ($gatewayAlias !== null) {
            $query->whereHas('gateway', function ($builder) use ($gatewayAlias) {
                $builder->where('alias', $gatewayAlias);
            });
        } else {
            $query->whereHas('gateway', function ($builder) {
                $builder->whereIn('alias', ['BictorysCheckout', 'BictorysDirect']);
            });
        }

        return $query->where(function ($builder) use ($references, $normalizedReferences) {
            $builder->whereIn('trx', $references)
                ->orWhereIn('btc_wallet', $references);

            // Case-insensitive fallback for environments using case-sensitive collation.
            if (!empty($normalizedReferences)) {
                $builder->orWhereIn(DB::raw('LOWER(trx)'), $normalizedReferences)
                    ->orWhereIn(DB::raw('LOWER(btc_wallet)'), $normalizedReferences);
            }
        })->latest('id')->first();
    }

    private function findDepositByVerificationToken(string $token, ?string $gatewayAlias, array $statuses, int $limit): ?Deposit
    {
        $query = Deposit::query()->whereIn('status', $statuses);

        if ($gatewayAlias !== null) {
            $query->whereHas('gateway', function ($builder) use ($gatewayAlias) {
                $builder->where('alias', $gatewayAlias);
            });
        } else {
            $query->whereHas('gateway', function ($builder) {
                $builder->whereIn('alias', ['BictorysCheckout', 'BictorysDirect']);
            });
        }

        $candidates = $query->latest('id')->take(max(1, $limit))->get();
        foreach ($candidates as $deposit) {
            $expected = hash_hmac('sha256', $deposit->id . '|' . $deposit->trx, (string) config('app.key'));
            if (hash_equals($expected, $token)) {
                return $deposit;
            }
        }

        return null;
    }

    private function trackWebhookReceipt(Deposit $deposit, string $status, string $eventUid): void
    {
        $detail = $this->normalizeDetailPayload($deposit->detail);
        $changed = false;

        $webhookAt = now()->toIso8601String();
        if (data_get($detail, 'bictorys.webhook_received_at') !== $webhookAt) {
            data_set($detail, 'bictorys.webhook_received_at', $webhookAt);
            $changed = true;
        }

        if ($status !== '' && data_get($detail, 'bictorys.webhook_last_status') !== $status) {
            data_set($detail, 'bictorys.webhook_last_status', $status);
            $changed = true;
        }

        if (data_get($detail, 'bictorys.last_event_uid') !== $eventUid) {
            data_set($detail, 'bictorys.last_event_uid', $eventUid);
            $changed = true;
        }

        if ($changed) {
            $deposit->detail = $detail;
            $deposit->save();
        }
    }

    private function extractSuccessFlag(array $payload): ?bool
    {
        $paths = [
            'success',
            'isSuccess',
            'is_success',
            'paid',
            'isPaid',
            'is_paid',
            'data.success',
            'data.isSuccess',
            'data.is_success',
            'data.paid',
            'data.isPaid',
            'data.is_paid',
            'data.data.success',
            'data.data.isSuccess',
            'data.data.is_success',
            'data.data.paid',
            'data.data.isPaid',
            'data.data.is_paid',
        ];

        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (is_bool($value)) {
                return $value;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $normalized = strtolower(trim((string) $value));
            if (in_array($normalized, ['1', 'true', 'yes', 'ok', 'success', 'successful', 'paid', 'succeeded'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'fail', 'failed', 'error', 'rejected', 'cancelled', 'canceled'], true)) {
                return false;
            }
        }

        return null;
    }

    private function extractStatus(array $payload): string
    {
        $candidates = [
            data_get($payload, 'status'),
            data_get($payload, 'paymentStatus'),
            data_get($payload, 'payment_status'),
            data_get($payload, 'state'),
            data_get($payload, 'result'),
            data_get($payload, 'data.status'),
            data_get($payload, 'data.paymentStatus'),
            data_get($payload, 'data.payment_status'),
            data_get($payload, 'data.state'),
            data_get($payload, 'data.result'),
            data_get($payload, 'data.data.status'),
            data_get($payload, 'data.data.paymentStatus'),
            data_get($payload, 'data.data.payment_status'),
            data_get($payload, 'data.data.state'),
            data_get($payload, 'data.data.result'),
            data_get($payload, 'message'),
            data_get($payload, 'data.message'),
        ];

        foreach ($candidates as $candidate) {
            if (!is_scalar($candidate)) {
                continue;
            }

            $normalized = $this->normalizeStatus((string) $candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    private function isSuccessPayload(array $payload, string $status, ?bool $successFlag): bool
    {
        if ($successFlag === true) {
            return true;
        }

        if ($status !== '' && $this->isSuccessStatus($status)) {
            return true;
        }

        $eventName = $this->extractEventName($payload);
        if ($eventName !== '' && ($this->statusContainsAny($eventName, ['success', 'paid', 'complete', 'captur']) || $eventName === 'charge_succeeded')) {
            return true;
        }

        return false;
    }

    private function isSuccessStatus(string $status): bool
    {
        if ($status === '' || $this->isFailureStatus($status)) {
            return false;
        }

        if (in_array($status, ['success', 'successful', 'paid', 'completed', 'succeeded', 'approved', 'received', 'captured', 'settled', 'done'], true)) {
            return true;
        }

        return $this->statusContainsAny($status, ['success', 'succeed', 'paid', 'complete', 'approved', 'receiv', 'captur', 'settl']);
    }

    private function isFailureStatus(string $status): bool
    {
        if ($status === '') {
            return false;
        }

        if (in_array($status, ['failed', 'failure', 'error', 'canceled', 'cancelled', 'rejected', 'expired', 'refunded', 'chargeback', 'declined', 'unpaid', 'void'], true)) {
            return true;
        }

        return $this->statusContainsAny($status, ['fail', 'error', 'cancel', 'reject', 'expire', 'refund', 'chargeback', 'declin', 'unpaid', 'not_paid']);
    }

    private function extractEventName(array $payload): string
    {
        $value = data_get($payload, 'event')
            ?? data_get($payload, 'eventName')
            ?? data_get($payload, 'event_name')
            ?? data_get($payload, 'eventType')
            ?? data_get($payload, 'event_type')
            ?? data_get($payload, 'type')
            ?? data_get($payload, 'data.event')
            ?? data_get($payload, 'data.type')
            ?? data_get($payload, 'data.data.event')
            ?? data_get($payload, 'data.data.type')
            ?? '';

        return $this->normalizeStatus((string) $value);
    }

    private function extractReferences(array $payload): array
    {
        $paths = [
            'paymentReference',
            'payment_reference',
            'merchantReference',
            'merchant_reference',
            'reference',
            'id',
            'chargeId',
            'charge_id',
            'paymentId',
            'payment_id',
            'transactionId',
            'transaction_id',
            'trx',
            'data.paymentReference',
            'data.payment_reference',
            'data.merchantReference',
            'data.merchant_reference',
            'data.reference',
            'data.id',
            'data.chargeId',
            'data.charge_id',
            'data.paymentId',
            'data.payment_id',
            'data.transactionId',
            'data.transaction_id',
            'data.trx',
            'payment.reference',
            'payment.id',
            'payment.chargeId',
            'payment.charge_id',
            'payment.paymentReference',
            'payment.payment_reference',
            'payment.merchantReference',
            'payment.merchant_reference',
            'data.data.paymentReference',
            'data.data.payment_reference',
            'data.data.merchantReference',
            'data.data.merchant_reference',
            'data.data.reference',
            'data.data.id',
            'data.data.chargeId',
            'data.data.charge_id',
            'data.data.paymentId',
            'data.data.payment_id',
            'data.data.transactionId',
            'data.data.transaction_id',
            'data.data.trx',
        ];

        $references = [];
        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (is_array($value)) {
                foreach ($value as $item) {
                    $normalized = $this->normalizeReference($item);
                    if ($normalized !== null) {
                        $references[] = $normalized;
                    }
                }
                continue;
            }

            $normalized = $this->normalizeReference($value);
            if ($normalized !== null) {
                $references[] = $normalized;
            }
        }

        return array_values(array_unique($references));
    }

    private function extractFirstScalar(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (!is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    private function normalizeGatewayAlias(string $gateway): ?string
    {
        $normalized = strtolower(trim($gateway));
        return match ($normalized) {
            'bictoryscheckout', 'checkout', 'bictorys_checkout' => 'BictorysCheckout',
            'bictorysdirect', 'direct', 'bictorys_direct', 'bictorys' => 'BictorysDirect',
            default => null,
        };
    }

    private function normalizeSignatureValue(?string $signatureHeader): string
    {
        if (!is_string($signatureHeader)) {
            return '';
        }

        $signature = trim($signatureHeader);
        if ($signature === '') {
            return '';
        }

        if (str_contains($signature, '=')) {
            $parts = explode('=', $signature, 2);
            $signature = trim((string) ($parts[1] ?? ''));
        }

        return strtolower($signature);
    }

    private function buildEventUid(?string $eventId, string $payloadHash, ?string $gatewayAlias, ?string $chargeId, ?string $paymentReference): string
    {
        if ($eventId !== null && trim($eventId) !== '') {
            return self::PROVIDER . ':event:' . strtolower(trim($eventId));
        }

        $parts = [
            self::PROVIDER,
            strtolower((string) $gatewayAlias),
            strtolower((string) $chargeId),
            strtolower((string) $paymentReference),
            $payloadHash,
        ];

        return self::PROVIDER . ':payload:' . sha1(implode('|', $parts));
    }

    private function normalizeDetailPayload($detail): array
    {
        if (is_array($detail)) {
            return $detail;
        }

        if (is_object($detail)) {
            return (array) $detail;
        }

        $decoded = json_decode((string) $detail, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolveChargeReference(array $references, string $trx): ?string
    {
        $normalizedTrx = $this->normalizeReference($trx);

        foreach ($references as $reference) {
            if ($reference === null || $reference === $normalizedTrx) {
                continue;
            }

            return $reference;
        }

        return null;
    }

    private function normalizeReference($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        return $normalized === '' ? null : $normalized;
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === '') {
            return '';
        }

        $status = str_replace(['-', ' '], '_', $status);
        $status = preg_replace('/[^a-z0-9_]+/', '', $status) ?? '';
        $status = preg_replace('/_+/', '_', $status) ?? '';

        return trim($status, '_');
    }

    private function statusContainsAny(string $status, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($status, $needle)) {
                return true;
            }
        }

        return false;
    }
}
