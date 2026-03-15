<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $name = trim((string) ($this->firstname . ' ' . $this->lastname));
        if ($name === '') {
            $name = (string) ($this->name ?: $this->username ?: $this->email);
        }

        return [
            'id' => (int) $this->id,
            'name' => $name,
            'email' => (string) $this->email,
            'username' => $this->username,
            'balance' => (float) $this->balance,
            'currency' => strtoupper((string) ($this->currency ?? config('app.currency', 'USD'))),
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'mobile' => $this->mobile,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'address' => $this->address,
            'country' => $this->country_name ?? $this->country,
            'ev' => (int) $this->ev,
            'sv' => (int) $this->sv,
            'tv' => (int) $this->tv,
            'status' => (int) $this->status,
        ];
    }
}
