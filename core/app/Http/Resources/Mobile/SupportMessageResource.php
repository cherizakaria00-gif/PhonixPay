<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'is_admin' => !is_null($this->admin_id),
            'admin_name' => optional($this->admin)->name,
            'message' => (string) $this->message,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
