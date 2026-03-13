<?php

use App\Http\Controllers\Api\PluginLicenseApiController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('licenses')->controller(PluginLicenseApiController::class)->group(function () {
    Route::post('validate', 'validateLegacy')->middleware('throttle:120,1');
    Route::post('deactivate', 'deactivateLegacy')->middleware('throttle:60,1');
    Route::post('details', 'detailsLegacy')->middleware('throttle:60,1');
});
