<?php

namespace App\Http\Resources\Mobile;

use App\Constants\Status;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileSettingsResource extends JsonResource
{
    public function toArray($request): array
    {
        $name = trim((string) ($this->firstname . ' ' . $this->lastname));
        if ($name === '') {
            $name = (string) ($this->name ?: $this->username ?: $this->email);
        }

        return [
            'id' => (int) $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'name' => $name,
            'email' => (string) $this->email,
            'username' => (string) $this->username,
            'mobile' => $this->mobile,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'address' => $this->address,
            'country' => $this->country_name ?? $this->country,
            'merchant_verified' => (int) $this->kv === Status::KYC_VERIFIED,
            'fixed_charge' => (float) ($this->fixed_charge ?? 0),
            'percent_charge' => (float) ($this->percent_charge ?? 0),
            'two_factor_enabled' => (int) $this->ts === Status::ENABLE,
        ];
    }
}
