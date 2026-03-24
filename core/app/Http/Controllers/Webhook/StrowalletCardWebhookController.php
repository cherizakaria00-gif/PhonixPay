<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\VirtualCard;
use App\Models\VirtualCardSetting;
use App\Models\VirtualCardTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class StrowalletCardWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->all();

        if (!$this->isSignatureValid($request)) {
            Log::warning('strowallet_webhook_invalid_signature', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 401);
        }

        $providerCardId = (string) (
            Arr::get($payload, 'card_id')
            ?? Arr::get($payload, 'data.card_id')
            ?? Arr::get($payload, 'card.card_id')
        );

        if ($providerCardId === '') {
            return response()->json(['ok' => true, 'message' => 'ignored']);
        }

        $card = VirtualCard::query()->where('provider_card_id', $providerCardId)->first();
        if (!$card) {
            return response()->json(['ok' => true, 'message' => 'card not found']);
        }

        $eventType = strtolower((string) (
            Arr::get($payload, 'event')
            ?? Arr::get($payload, 'event_type')
            ?? Arr::get($payload, 'type')
            ?? 'card_event'
        ));

        $providerTxnId = (string) (
            Arr::get($payload, 'transaction_id')
            ?? Arr::get($payload, 'data.transaction_id')
            ?? Arr::get($payload, 'id')
        );

        if ($providerTxnId !== '') {
            $txn = VirtualCardTransaction::firstOrNew([
                'virtual_card_id' => $card->id,
                'provider_txn_id' => $providerTxnId,
            ]);

            $txn->user_id = $card->user_id;
            $txn->type = (string) (Arr::get($payload, 'txn_type') ?? Arr::get($payload, 'transaction_type') ?? $eventType);
            $txn->amount = (float) (Arr::get($payload, 'amount') ?? Arr::get($payload, 'data.amount') ?? 0);
            $txn->balance_after = Arr::get($payload, 'balance_after') !== null ? (float) Arr::get($payload, 'balance_after') : null;
            $txn->currency = (string) (Arr::get($payload, 'currency') ?? Arr::get($payload, 'data.currency') ?? $card->currency);
            $txn->status = (string) (Arr::get($payload, 'status') ?? 'posted');
            $txn->description = (string) (Arr::get($payload, 'description') ?? $eventType);
            $txn->meta = $payload;
            $txn->transacted_at = Arr::get($payload, 'created_at') ? Carbon::parse((string) Arr::get($payload, 'created_at')) : now();
            $txn->save();
        }

        $newStatus = (string) (Arr::get($payload, 'card_status') ?? Arr::get($payload, 'status') ?? $card->status);
        $newBalance = Arr::get($payload, 'balance', Arr::get($payload, 'data.balance'));
        if ($newBalance !== null) {
            $card->balance = (float) $newBalance;
        }
        $card->status = $newStatus ?: $card->status;
        $card->meta = array_merge((array) $card->meta, [
            'last_webhook' => [
                'received_at' => now()->toDateTimeString(),
                'event' => $eventType,
                'payload' => $payload,
            ],
        ]);
        $card->save();

        return response()->json(['ok' => true]);
    }

    private function isSignatureValid(Request $request): bool
    {
        $setting = VirtualCardSetting::query()->find(1);
        $webhookSecret = (string) optional($setting)->webhook_secret;

        if ($webhookSecret === '') {
            return true;
        }

        $incoming = (string) (
            $request->header('x-strowallet-signature')
            ?? $request->header('x-signature')
            ?? ''
        );

        if ($incoming === '') {
            return false;
        }

        $raw = $request->getContent();
        $calculated = hash_hmac('sha256', $raw, $webhookSecret);

        return hash_equals($calculated, $incoming);
    }
}
