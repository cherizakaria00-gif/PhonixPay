<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) data_get($this, 'id'),
            'type' => (string) data_get($this, 'type'),
            'status' => (string) data_get($this, 'status'),
            'amount' => (float) data_get($this, 'amount', 0),
            'currency' => (string) data_get($this, 'currency', 'USD'),
            'fee' => (float) data_get($this, 'fee', 0),
            'net' => (float) data_get($this, 'net', 0),
            'reference' => (string) data_get($this, 'reference', ''),
            'description' => (string) data_get($this, 'description', ''),
            'customer' => data_get($this, 'customer'),
            'email' => data_get($this, 'email'),
            'phone' => data_get($this, 'phone'),
            'created_at' => optional(data_get($this, 'created_at'))->toIso8601String(),
        ];
    }
}
