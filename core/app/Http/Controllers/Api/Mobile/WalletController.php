<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Http\Resources\Mobile\TransactionResource;
use App\Models\Deposit;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WalletController extends ApiMobileController
{
    public function balance(Request $request)
    {
        $user = $request->user();

        return $this->ok([
            'balance' => (float) $user->balance,
            'currency' => $this->appCurrency(),
            'payout_available' => round((float) $user->balance * 0.7, 8),
        ]);
    }

    public function transactions(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:deposit,withdrawal,payment,refund',
            'status' => 'nullable|in:pending,success,failed',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $typeFilter = $request->input('type');
        $statusFilter = $request->input('status');

        $items = $this->depositItems($user->id)
            ->merge($this->withdrawalItems($user->id))
            ->sortByDesc('created_at')
            ->values();

        if ($typeFilter) {
            $items = $items->where('type', $typeFilter)->values();
        }

        if ($statusFilter) {
            $items = $items->where('status', $statusFilter)->values();
        }

        $paginator = $this->paginateCollection($items, (int) $request->input('per_page', 10), (int) $request->input('page', 1));

        return $this->ok([
            'data' => TransactionResource::collection(collect($paginator->items()))->resolve(),
            'meta' => $this->meta($paginator),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $item = $this->depositItems($user->id)->firstWhere('id', $id)
            ?? $this->withdrawalItems($user->id)->firstWhere('id', $id);

        if (!$item) {
            return response()->json([
                'message' => 'Transaction not found.',
                'errors' => [
                    'id' => ['Transaction not found.'],
                ],
            ], 404);
        }

        return $this->ok([
            'transaction' => (new TransactionResource($item))->resolve(),
        ]);
    }

    private function depositItems(int $userId): Collection
    {
        return Deposit::query()
            ->where('user_id', $userId)
            ->with('apiPayment')
            ->latest('id')
            ->get()
            ->map(function (Deposit $deposit) {
                $fee = (float) ($deposit->charge + ($deposit->payment_charge ?? 0));
                $amount = (float) $deposit->amount;
                $customer = $deposit->apiPayment?->customer;
                $customerName = trim(implode(' ', array_filter([
                    data_get($customer, 'first_name'),
                    data_get($customer, 'last_name'),
                ])));

                return collect([
                    'id' => (int) $deposit->id,
                    'type' => (int) $deposit->status === Status::PAYMENT_REFUNDED ? 'refund' : 'payment',
                    'status' => $this->mapDepositStatus((int) $deposit->status),
                    'amount' => $amount,
                    'currency' => strtoupper((string) ($deposit->method_currency ?: $this->appCurrency())),
                    'fee' => $fee,
                    'net' => (float) ($deposit->net_amount ?? max(0, $amount - $fee)),
                    'reference' => (string) ($deposit->trx ?: $deposit->btc_wallet),
                    'description' => (string) data_get($deposit, 'apiPayment.details', ''),
                    'customer' => $customerName ?: null,
                    'email' => data_get($customer, 'email'),
                    'phone' => data_get($customer, 'mobile'),
                    'created_at' => $deposit->created_at,
                ]);
            });
    }

    private function withdrawalItems(int $userId): Collection
    {
        return Withdrawal::query()
            ->where('user_id', $userId)
            ->where('status', '!=', Status::PAYMENT_INITIATE)
            ->latest('id')
            ->get()
            ->map(function (Withdrawal $withdrawal) {
                $amount = (float) $withdrawal->amount;
                $fee = (float) $withdrawal->charge;

                return collect([
                    'id' => (int) $withdrawal->id,
                    'type' => 'withdrawal',
                    'status' => $this->mapWithdrawStatus((int) $withdrawal->status),
                    'amount' => $amount,
                    'currency' => strtoupper((string) ($withdrawal->currency ?: $this->appCurrency())),
                    'fee' => $fee,
                    'net' => (float) max(0, $amount - $fee),
                    'reference' => (string) $withdrawal->trx,
                    'description' => (string) ('Withdraw via ' . (optional($withdrawal->method)->name ?: 'method')),
                    'created_at' => $withdrawal->created_at,
                ]);
            });
    }

    private function mapDepositStatus(int $status): string
    {
        return match ($status) {
            Status::PAYMENT_SUCCESS, Status::PAYMENT_REFUNDED => 'success',
            Status::PAYMENT_PENDING, Status::PAYMENT_INITIATE => 'pending',
            default => 'failed',
        };
    }

    private function mapWithdrawStatus(int $status): string
    {
        return match ($status) {
            Status::PAYMENT_SUCCESS => 'success',
            Status::PAYMENT_PENDING => 'pending',
            default => 'failed',
        };
    }

    private function paginateCollection(Collection $items, int $perPage, int $currentPage): LengthAwarePaginator
    {
        $total = $items->count();
        $offset = max(0, ($currentPage - 1) * $perPage);

        return new LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
