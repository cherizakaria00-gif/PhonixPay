<?php

namespace App\Helpers;

use App\Models\Deposit;
use App\Models\StripeAccount;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Account Helper Class
 * Handles dynamic selection of Stripe accounts based on deposit amount and fallback logic
 */
class StripeAccountHelper
{
    /**
     * Select the appropriate Stripe account for a deposit
     *
     * @param Deposit $deposit
     * @return StripeAccount|null
     */
    public static function selectStripeAccount(Deposit $deposit): ?StripeAccount
    {
        // Get the deposit amount and currency
        $amount = $deposit->final_amount;
        $currency = $deposit->method_currency;

        try {
            $activeCount = StripeAccount::active()->count();
            if ($activeCount === 1) {
                return StripeAccount::active()->first();
            }

            // First, try to find an account that matches the amount range (only if ranges are configured)
            $account = self::findAccountByAmountRange($amount);

            if ($account) {
                Log::info('Stripe account selected by amount range', [
                    'deposit_id' => $deposit->id,
                    'amount' => $amount,
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                ]);
                return $account;
            }

            // Fallback: Use round-robin by customer if no amount match
            $account = self::selectByRoundRobin($deposit);

            if ($account) {
                Log::info('Stripe account selected by round-robin (customer)', [
                    'deposit_id' => $deposit->id,
                    'amount' => $amount,
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                ]);
                return $account;
            }

            Log::warning('No active Stripe account found', [
                'deposit_id' => $deposit->id,
                'amount' => $amount,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error selecting Stripe account', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find a Stripe account that matches the deposit amount range
     *
     * @param float $amount
     * @return StripeAccount|null
     */
    private static function findAccountByAmountRange(float $amount): ?StripeAccount
    {
        $hasRange = StripeAccount::active()
            ->where(function ($query) {
                $query->where('min_amount', '>', 0)
                    ->orWhere('max_amount', '>', 0);
            })
            ->exists();

        if (!$hasRange) {
            return null;
        }

        return StripeAccount::active()
            ->where(function ($query) {
                $query->where('min_amount', '>', 0)
                    ->orWhere('max_amount', '>', 0);
            })
            ->where(function ($query) use ($amount) {
                $query->where('min_amount', '<=', $amount)
                    ->where(function ($q) use ($amount) {
                        $q->where('max_amount', '>=', $amount)
                            ->orWhere('max_amount', 0); // 0 means no maximum limit
                    });
            })
            ->orderBy('min_amount', 'DESC')
            ->orderBy('max_amount', 'ASC')
            ->first();
    }

    /**
     * Select a Stripe account using round-robin method
     * Uses the customer id to distribute load evenly
     *
     * @param Deposit $deposit
     * @return StripeAccount|null
     */
    private static function selectByRoundRobin(Deposit $deposit): ?StripeAccount
    {
        $accounts = StripeAccount::active()
            ->orderBy('id')
            ->get();

        if ($accounts->isEmpty()) {
            return null;
        }

        // Round-robin by customer id (stable assignment per customer)
        $keyBase = (int) ($deposit->user_id ?? $deposit->id ?? 1);
        if ($keyBase <= 0) {
            $keyBase = 1;
        }
        $accountIndex = ($keyBase - 1) % $accounts->count();

        return $accounts[$accountIndex] ?? $accounts->first();
    }

    /**
     * Get all active Stripe accounts
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveAccounts()
    {
        return StripeAccount::active()->orderBy('name')->get();
    }

    /**
     * Get a Stripe account by ID
     *
     * @param int $accountId
     * @return StripeAccount|null
     */
    public static function getAccountById(int $accountId): ?StripeAccount
    {
        return StripeAccount::find($accountId);
    }

    /**
     * Validate Stripe account credentials
     *
     * @param StripeAccount $account
     * @return bool
     */
    public static function validateCredentials(StripeAccount $account): bool
    {
        try {
            \Stripe\Stripe::setApiKey($account->secret_key);
            $account = \Stripe\Account::retrieve();
            return true;
        } catch (\Exception $e) {
            Log::error('Stripe account validation failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
