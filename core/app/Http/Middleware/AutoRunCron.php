<?php

namespace App\Http\Middleware;

use App\Http\Controllers\CronController;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoRunCron
{
    public function handle(Request $request, Closure $next)
    {
        $this->runCronIfStale($request);

        return $next($request);
    }

    private function runCronIfStale(Request $request): void
    {
        if (app()->runningInConsole() || app()->runningUnitTests()) {
            return;
        }

        if ($request->is('cron') || $request->is('ipn/*')) {
            return;
        }

        $lastCron = gs('last_cron');

        if ($lastCron) {
            try {
                if (now()->diffInSeconds(Carbon::parse($lastCron)) < 300) {
                    return;
                }
            } catch (\Throwable $e) {
                Log::warning('AutoRunCron failed to parse last_cron', [
                    'last_cron' => $lastCron,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Avoid duplicate execution under concurrent traffic.
        if (!Cache::add('flujipay_auto_cron_lock', now()->timestamp, 55)) {
            return;
        }

        try {
            app(CronController::class)->cron();
        } catch (\Throwable $e) {
            Log::error('AutoRunCron execution failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

