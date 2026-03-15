<?php

namespace App\Http\Resources\Mobile;

use App\Constants\Status;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'ticket' => (string) $this->ticket,
            'subject' => (string) $this->subject,
            'priority' => (int) $this->priority,
            'status' => (int) $this->status,
            'status_text' => $this->statusText((int) $this->status),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'last_reply' => optional($this->last_reply)->toIso8601String(),
        ];
    }

    private function statusText(int $status): string
    {
        return match ($status) {
            Status::TICKET_OPEN => 'Open',
            Status::TICKET_ANSWER => 'Answered',
            Status::TICKET_REPLY => 'Customer Reply',
            Status::TICKET_CLOSE => 'Closed',
            default => 'Unknown',
        };
    }
}
