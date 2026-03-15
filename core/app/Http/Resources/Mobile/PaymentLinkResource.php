<?php

namespace App\Http\Resources\Mobile;

use App\Models\PaymentLink;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentLinkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => (string) $this->code,
            'description' => (string) ($this->description ?? ''),
            'amount' => (float) $this->amount,
            'currency' => strtoupper((string) ($this->currency ?: 'USD')),
            'status' => (int) $this->status,
            'status_text' => $this->statusText(),
            'views' => (int) ($this->views ?? 0),
            'sales' => (int) ($this->deposits_count ?? $this->deposits()->count()),
            'expires_at' => optional($this->expires_at)->toIso8601String(),
            'link_url' => route('payment.link.show', $this->code),
            'redirect_url' => $this->redirect_url,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }

    private function statusText(): string
    {
        return match ((int) $this->status) {
            PaymentLink::STATUS_ACTIVE => 'Active',
            PaymentLink::STATUS_PAID => 'Paid',
            PaymentLink::STATUS_EXPIRED => 'Expired',
            PaymentLink::STATUS_DISABLED => 'Disabled',
            default => 'Unknown',
        };
    }
}
