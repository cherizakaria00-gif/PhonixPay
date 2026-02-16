<?php

/**
 * Stripe Webhook Routes
 * Add these routes to your routes/api.php or routes/web.php
 *
 * For Laravel 11+:
 * Route::post('/webhooks/stripe', [App\Http\Controllers\Gateway\StripeWebhookController::class, 'handleWebhook']);
 *
 * For older versions:
 * Route::post('/webhooks/stripe', 'Gateway\StripeWebhookController@handleWebhook');
 */

// Example implementation in routes/web.php or routes/api.php:
Route::post('/webhooks/stripe', [\App\Http\Controllers\Gateway\StripeWebhookController::class, 'handleWebhook'])->middleware('throttle:60,1');

// Or in a group
Route::middleware('api')->group(function () {
    Route::post('/webhooks/stripe', [\App\Http\Controllers\Gateway\StripeWebhookController::class, 'handleWebhook']);
});
