<?php

namespace App\Http\Resources\Mobile;

use App\Constants\Status;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $plainMessage = $this->cleanMessage((string) ($this->message ?? ''));

        return [
            'id' => (int) $this->id,
            'subject' => (string) ($this->subject ?: 'Notification'),
            'message' => Str::limit($plainMessage, 120),
            'full_message' => $plainMessage !== '' ? $plainMessage : null,
            'time' => $this->created_at ? diffForHumans($this->created_at) : null,
            'unread' => (int) $this->user_read === Status::NO,
            'url' => route('user.notification.read', $this->id),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }

    private function cleanMessage(string $message): string
    {
        $clean = preg_replace('/<(style|script)\\b[^>]*>.*?<\\/\\1>/is', ' ', $message);
        $clean = strip_tags((string) $clean);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\\s+/u', ' ', $clean));
    }
}
