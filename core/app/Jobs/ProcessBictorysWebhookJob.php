<?php

namespace App\Jobs;

use App\Services\BictorysWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBictorysWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [1, 2, 5];

    public function __construct(
        public array $payload,
        public string $rawPayload = '',
        public array $context = []
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(BictorysWebhookService $service): void
    {
        $result = $service->processWebhook($this->payload, $this->rawPayload, $this->context);

        Log::info('Bictorys webhook processed', [
            'result' => $result,
            'attempt' => $this->attempts(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Bictorys webhook job failed', [
            'error' => $exception->getMessage(),
            'context' => $this->context,
            'attempt' => $this->attempts(),
        ]);
    }
}
