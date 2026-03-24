<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualCard;
use App\Models\VirtualCardSetting;
use App\Models\VirtualCardTransaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class VirtualCardService
{
    public function __construct(private readonly StrowalletService $provider)
    {
    }

    public function ensureEnabled(): void
    {
        if (!$this->provider->enabled()) {
            throw new RuntimeException('Virtual card provider is not configured yet.');
        }
    }

    public function createCard(User $user, array $input): VirtualCard
    {
        $this->ensureEnabled();

        $maxCards = 3;
        if (Schema::hasTable('virtual_card_settings')) {
            $maxCards = (int) optional(VirtualCardSetting::query()->find(1))->max_cards_per_merchant ?: 3;
        }

        $currentCards = VirtualCard::where('user_id', $user->id)->count();
        if ($currentCards >= $maxCards) {
            throw new RuntimeException('Card limit reached for this merchant.');
        }

        $settings = Schema::hasTable('virtual_card_settings') ? VirtualCardSetting::query()->find(1) : null;
        $defaultCurrency = strtoupper((string) ($settings->default_currency ?? config('strowallet.currency', 'USD')));
        $mode = strtolower((string) ($settings->mode ?? 'sandbox'));
        $currency = strtoupper((string) ($input['currency'] ?? $defaultCurrency));
        $initialAmount = max(0, (float) ($input['amount'] ?? 5));
        $cardLabel = trim((string) ($input['card_label'] ?? ''));
        $cardColor = trim((string) ($input['card_color'] ?? ''));

        [$createFee, $createFeeMeta] = $this->calculateCardFee($initialAmount, $settings, false);
        $debitTotal = round($initialAmount + $createFee, 8);
        if ((float) $user->balance < $debitTotal) {
            throw new RuntimeException('Insufficient merchant balance.');
        }

        // Strowallet create-card payload aligned with provider docs/sample.
        $createPayload = [
            'name_on_card' => (string) ($input['name_on_card'] ?? $user->fullname),
            'card_type' => (string) ($input['card_type'] ?? 'visa'),
            'amount' => (string) $initialAmount,
            'customerEmail' => (string) $user->email,
            'mode' => in_array($mode, ['live', 'sandbox'], true) ? $mode : 'sandbox',
        ];

        $response = $this->provider->createCard($createPayload);
        $providerData = (array) (
            Arr::get($response, 'data')
            ?? Arr::get($response, 'response')
            ?? []
        );

        $providerCardId = (string) (
            Arr::get($providerData, 'card_id')
            ?? Arr::get($providerData, 'id')
            ?? Arr::get($providerData, 'card_id_hash')
            ?? Arr::get($response, 'card_id')
            ?? Arr::get($response, 'id')
        );

        if ($providerCardId === '') {
            throw new RuntimeException('Unable to create virtual card.');
        }

        $providerCustomerId = (string) (
            Arr::get($providerData, 'customer_id')
            ?? Arr::get($providerData, 'customerId')
            ?? Arr::get($response, 'customer_id')
            ?? $user->email
        );
        $cardReference = (string) (Arr::get($providerData, 'reference') ?? Arr::get($response, 'reference'));
        $brand = (string) (Arr::get($providerData, 'card_brand') ?? Arr::get($providerData, 'brand') ?? Arr::get($response, 'brand') ?? 'VISA');
        $last4 = (string) (Arr::get($providerData, 'last4') ?? Arr::get($response, 'last4') ?? '');
        $maskedPan = (string) (
            Arr::get($providerData, 'card_number')
            ?? Arr::get($providerData, 'masked_pan')
            ?? Arr::get($response, 'masked_pan')
            ?? ''
        );
        $cardBalance = (float) (Arr::get($providerData, 'balance') ?? Arr::get($response, 'balance') ?? $initialAmount);
        $providerStatus = (string) (
            Arr::get($providerData, 'card_status')
            ?? Arr::get($providerData, 'status')
            ?? Arr::get($response, 'status')
            ?? 'active'
        );

        $card = DB::transaction(function () use (
            $user,
            $providerCustomerId,
            $providerCardId,
            $cardReference,
            $brand,
            $last4,
            $maskedPan,
            $currency,
            $cardBalance,
            $providerStatus,
            $response,
            $initialAmount,
            $createFee,
            $createFeeMeta,
            $debitTotal,
            $cardLabel,
            $cardColor
        ) {
            $user->refresh();
            if ((float) $user->balance < $debitTotal) {
                throw new RuntimeException('Insufficient merchant balance.');
            }

            $user->balance = round((float) $user->balance - $debitTotal, 8);
            $user->save();

            $card = new VirtualCard();
            $card->user_id = $user->id;
            $card->provider = 'strowallet';
            $card->provider_customer_id = $providerCustomerId;
            $card->provider_card_id = $providerCardId;
            $card->card_reference = $cardReference;
            $card->brand = $brand;
            $card->last4 = $last4;
            $card->masked_pan = $maskedPan;
            $card->currency = $currency;
            $card->balance = $cardBalance;
            // Business rule: once provider returns a valid card id, card is immediately usable in dashboard.
            $card->status = $this->normalizeCreatedCardStatus($providerStatus);
            $card->meta = [
                'create_response' => $response,
                'label' => $cardLabel !== '' ? $cardLabel : ('Card ' . ($last4 ?: '0000')),
                'color' => $this->sanitizeCardColor($cardColor, $providerCardId),
            ];
            $card->save();

            $this->recordWalletTransaction(
                $user,
                $initialAmount,
                '-',
                'Virtual card creation amount (' . ($card->last4 ?: $card->provider_card_id) . ')',
                'virtual_card_create_amount'
            );

            if ($createFee > 0) {
                $this->recordWalletTransaction(
                    $user,
                    $createFee,
                    '-',
                    'Virtual card creation charge (' . ($card->last4 ?: $card->provider_card_id) . ')',
                    'virtual_card_create_charge'
                );
            }

            $createTxn = new VirtualCardTransaction();
            $createTxn->virtual_card_id = $card->id;
            $createTxn->user_id = $user->id;
            $createTxn->provider_txn_id = null;
            $createTxn->type = 'create';
            $createTxn->amount = $initialAmount;
            $createTxn->balance_after = $card->balance;
            $createTxn->currency = $card->currency;
            $createTxn->status = 'posted';
            $createTxn->description = 'Card created';
            $createTxn->meta = ['create_response' => $response, 'fee' => $createFee, 'fee_meta' => $createFeeMeta];
            $createTxn->transacted_at = Carbon::now();
            $createTxn->save();

            return $card;
        });

        return $card;
    }

    public function fundCardFromMerchantBalance(User $user, VirtualCard $card, float $amount): VirtualCard
    {
        $this->ensureEnabled();

        if ($card->user_id !== $user->id) {
            throw new RuntimeException('Card does not belong to merchant.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Funding amount must be greater than zero.');
        }

        $settings = Schema::hasTable('virtual_card_settings') ? VirtualCardSetting::query()->find(1) : null;
        [$reloadFee, $reloadFeeMeta] = $this->calculateCardFee($amount, $settings, true);
        $debitTotal = round($amount + $reloadFee, 8);

        if ((float) $user->balance < $debitTotal) {
            throw new RuntimeException('Insufficient merchant balance.');
        }

        $response = $this->provider->fundCard([
            'card_id' => $card->provider_card_id,
            'amount' => $amount,
            'currency' => $card->currency,
        ]);

        $providerTxnId = (string) (Arr::get($response, 'data.transaction_id') ?? Arr::get($response, 'transaction_id') ?? '');
        $providerBalance = Arr::get($response, 'data.balance', Arr::get($response, 'balance'));

        DB::transaction(function () use ($user, $card, $amount, $response, $providerTxnId, $providerBalance, $reloadFee, $reloadFeeMeta, $debitTotal) {
            $user->refresh();
            if ((float) $user->balance < $debitTotal) {
                throw new RuntimeException('Insufficient merchant balance.');
            }

            $user->balance = round((float) $user->balance - $debitTotal, 8);
            $user->save();

            if ($providerBalance !== null) {
                $card->balance = (float) $providerBalance;
            } else {
                $card->balance = round((float) $card->balance + $amount, 8);
            }
            $card->status = 'active';
            $card->save();

            $this->recordWalletTransaction(
                $user,
                $amount,
                '-',
                'Virtual card funding (' . ($card->last4 ?: $card->provider_card_id) . ')',
                'virtual_card_fund'
            );

            if ($reloadFee > 0) {
                $this->recordWalletTransaction(
                    $user,
                    $reloadFee,
                    '-',
                    'Virtual card reload charge (' . ($card->last4 ?: $card->provider_card_id) . ')',
                    'virtual_card_reload_charge'
                );
            }

            $cardTxn = new VirtualCardTransaction();
            $cardTxn->virtual_card_id = $card->id;
            $cardTxn->user_id = $user->id;
            $cardTxn->provider_txn_id = $providerTxnId ?: null;
            $cardTxn->type = 'fund';
            $cardTxn->amount = $amount;
            $cardTxn->balance_after = $card->balance;
            $cardTxn->currency = $card->currency;
            $cardTxn->status = 'posted';
            $cardTxn->description = 'Card funded from merchant balance';
            $cardTxn->meta = ['fund_response' => $response, 'fee' => $reloadFee, 'fee_meta' => $reloadFeeMeta];
            $cardTxn->transacted_at = Carbon::now();
            $cardTxn->save();
        });

        return $card->fresh(['transactions']);
    }

    private function normalizeCreatedCardStatus(string $providerStatus): string
    {
        $status = strtolower(trim($providerStatus));

        if ($status === '' || in_array($status, ['pending', 'processing', 'initiated', 'queued'], true)) {
            return 'active';
        }

        if (in_array($status, ['success', 'succeed', 'live'], true)) {
            return 'active';
        }

        return $status;
    }

    private function calculateCardFee(float $amount, ?VirtualCardSetting $settings, bool $isReload): array
    {
        $amount = max(0, $amount);
        if ($amount <= 0) {
            return [0.0, ['fixed' => 0.0, 'percent' => 0.0]];
        }

        $fixed = (float) ($isReload ? ($settings->reload_fixed_charge ?? 0) : ($settings->fixed_charge ?? 0));
        $percent = (float) ($isReload ? ($settings->reload_percent_charge ?? 0) : ($settings->percent_charge ?? 0));
        $minAmount = (float) ($isReload ? ($settings->reload_min_amount ?? 0) : ($settings->min_amount ?? 0));
        $maxAmount = (float) ($isReload ? ($settings->reload_max_amount ?? 0) : ($settings->max_amount ?? 0));

        if ($minAmount > 0 && $amount < $minAmount) {
            throw new RuntimeException('Amount is below virtual card minimum limit.');
        }
        if ($maxAmount > 0 && $amount > $maxAmount) {
            throw new RuntimeException('Amount exceeds virtual card maximum limit.');
        }

        $fixedPart = max(0, $fixed);
        $percentPart = max(0, ($amount * max(0, $percent) / 100));
        $total = round($fixedPart + $percentPart, 8);

        return [$total, ['fixed' => $fixedPart, 'percent' => $percentPart, 'rate' => $percent, 'is_reload' => $isReload]];
    }

    private function recordWalletTransaction(User $user, float $amount, string $trxType, string $details, string $remark): void
    {
        if ($amount <= 0) {
            return;
        }

        $tx = new Transaction();
        $tx->user_id = $user->id;
        $tx->amount = $amount;
        $tx->post_balance = $user->balance;
        $tx->charge = 0;
        $tx->trx_type = $trxType;
        $tx->details = $details;
        $tx->trx = getTrx();
        $tx->remark = $remark;
        $tx->save();
    }

    private function sanitizeCardColor(string $color, string $seed): string
    {
        $color = ltrim(trim($color), '#');
        if ($color !== '' && preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return '#' . strtolower($color);
        }

        $palette = ['#4f46e5', '#2563eb', '#0ea5e9', '#14b8a6', '#22c55e', '#f59e0b', '#f97316', '#ef4444', '#ec4899'];
        $index = abs(crc32($seed)) % count($palette);
        return $palette[$index];
    }

    public function syncCardTransactions(VirtualCard $card): int
    {
        $this->ensureEnabled();

        $response = [];
        try {
            $response = $this->provider->fullCardHistory([
                'card_id' => $card->provider_card_id,
            ]);
        } catch (\Throwable $e) {
            $response = $this->provider->cardTransactions([
                'card_id' => $card->provider_card_id,
            ]);
        }

        $items = Arr::get($response, 'data.transactions', Arr::get($response, 'data', []));
        if (!is_array($items)) {
            return 0;
        }

        $count = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $providerTxnId = (string) (Arr::get($item, 'transaction_id') ?? Arr::get($item, 'id') ?? '');
            if ($providerTxnId === '') {
                continue;
            }

            $record = VirtualCardTransaction::firstOrNew([
                'virtual_card_id' => $card->id,
                'provider_txn_id' => $providerTxnId,
            ]);

            $record->user_id = $card->user_id;
            $record->type = (string) (Arr::get($item, 'type') ?? 'card_txn');
            $record->amount = (float) (Arr::get($item, 'amount') ?? 0);
            $record->balance_after = Arr::get($item, 'balance_after') !== null ? (float) Arr::get($item, 'balance_after') : null;
            $record->currency = (string) (Arr::get($item, 'currency') ?? $card->currency);
            $record->status = (string) (Arr::get($item, 'status') ?? 'posted');
            $record->description = (string) (Arr::get($item, 'description') ?? Arr::get($item, 'narration') ?? '');
            $record->meta = $item;
            $record->transacted_at = Arr::get($item, 'created_at') ? Carbon::parse((string) Arr::get($item, 'created_at')) : Carbon::now();
            $record->save();
            $count++;
        }

        $detail = $this->provider->cardDetails(['card_id' => $card->provider_card_id]);
        $newBalance = Arr::get($detail, 'data.balance', Arr::get($detail, 'balance'));
        $newStatus = (string) (
            Arr::get($detail, 'data.card_status')
            ?? Arr::get($detail, 'data.status')
            ?? Arr::get($detail, 'status')
            ?? ''
        );
        if ($newBalance !== null) {
            $card->balance = (float) $newBalance;
        }
        if ($newStatus !== '') {
            $card->status = $this->normalizeCreatedCardStatus($newStatus);
        } elseif (in_array(strtolower((string) $card->status), ['pending', 'processing', 'initiated', 'queued', 'success', 'succeed', 'live'], true)) {
            // Keep dashboard usable when provider returns no explicit status in details response.
            $card->status = 'active';
        }
        $card->save();

        return $count;
    }

    public function freezeCard(VirtualCard $card, bool $freeze): array
    {
        $this->ensureEnabled();

        $response = $this->provider->freezeUnfreeze([
            'card_id' => $card->provider_card_id,
            'action' => $freeze ? 'freeze' : 'unfreeze',
            'status' => $freeze ? 'freeze' : 'unfreeze',
        ]);

        $card->status = $freeze ? 'frozen' : 'active';
        $card->meta = array_merge((array) $card->meta, [
            'freeze_last_response' => $response,
        ]);
        $card->save();

        return $response;
    }

    public function withdrawFromCardToMerchantBalance(User $user, VirtualCard $card, float $amount): array
    {
        $this->ensureEnabled();

        if ($card->user_id !== $user->id) {
            throw new RuntimeException('Card does not belong to merchant.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Withdraw amount must be greater than zero.');
        }

        $response = $this->provider->withdrawFromCard([
            'card_id' => $card->provider_card_id,
            'amount' => $amount,
            'currency' => $card->currency,
        ]);

        $providerTxnId = (string) (Arr::get($response, 'data.transaction_id') ?? Arr::get($response, 'transaction_id') ?? '');
        $providerBalance = Arr::get($response, 'data.balance', Arr::get($response, 'balance'));

        DB::transaction(function () use ($user, $card, $amount, $response, $providerTxnId, $providerBalance) {
            $user->refresh();
            $user->balance = round((float) $user->balance + $amount, 8);
            $user->save();

            if ($providerBalance !== null) {
                $card->balance = (float) $providerBalance;
            } else {
                $card->balance = max(0, round((float) $card->balance - $amount, 8));
            }
            $card->save();

            $tx = new Transaction();
            $tx->user_id = $user->id;
            $tx->amount = $amount;
            $tx->post_balance = $user->balance;
            $tx->charge = 0;
            $tx->trx_type = '+';
            $tx->details = 'Virtual card withdraw to merchant balance (' . ($card->last4 ?: $card->provider_card_id) . ')';
            $tx->trx = getTrx();
            $tx->remark = 'virtual_card_withdraw';
            $tx->save();

            $cardTxn = new VirtualCardTransaction();
            $cardTxn->virtual_card_id = $card->id;
            $cardTxn->user_id = $user->id;
            $cardTxn->provider_txn_id = $providerTxnId ?: null;
            $cardTxn->type = 'withdraw';
            $cardTxn->amount = $amount;
            $cardTxn->balance_after = $card->balance;
            $cardTxn->currency = $card->currency;
            $cardTxn->status = 'posted';
            $cardTxn->description = 'Card withdraw to merchant balance';
            $cardTxn->meta = ['withdraw_response' => $response];
            $cardTxn->transacted_at = Carbon::now();
            $cardTxn->save();
        });

        return $response;
    }

    public function upgradeCardLimit(VirtualCard $card, float $newLimit): array
    {
        $this->ensureEnabled();

        if ($newLimit <= 0) {
            throw new RuntimeException('Limit must be greater than zero.');
        }

        $response = $this->provider->upgradeCardLimit([
            'card_id' => $card->provider_card_id,
            'limit' => $newLimit,
            'new_limit' => $newLimit,
        ]);

        $card->meta = array_merge((array) $card->meta, [
            'last_limit_update' => [
                'new_limit' => $newLimit,
                'response' => $response,
                'updated_at' => Carbon::now()->toDateTimeString(),
            ],
        ]);
        $card->save();

        return $response;
    }
}
