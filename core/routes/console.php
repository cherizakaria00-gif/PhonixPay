<?php

use App\Http\Controllers\CronController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    // Prevent duplicate execution when multiple server cron entries trigger schedule:run.
    if (!Cache::add('flujipay_cron_bridge_lock', now()->timestamp, 55)) {
        return;
    }

    app(CronController::class)->cron();
})->name('flujipay-cron-bridge')->everyMinute();
