<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\AuthorizationController;
use App\Http\Controllers\Api\Mobile\DashboardController;
use App\Http\Controllers\Api\Mobile\NotificationController;
use App\Http\Controllers\Api\Mobile\PaymentController;
use App\Http\Controllers\Api\Mobile\PaymentLinkController;
use App\Http\Controllers\Api\Mobile\PayoutController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\SupportTicketController;
use App\Http\Controllers\Api\Mobile\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register-mobile', [AuthController::class, 'registerMobile']);
    Route::post('social-login/{provider}', [AuthController::class, 'socialLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('authorization')->group(function () {
        Route::get('status', [AuthorizationController::class, 'status']);
        Route::post('resend', [AuthorizationController::class, 'resend']);
        Route::post('update-email', [AuthorizationController::class, 'updateEmail']);
        Route::post('verify-email', [AuthorizationController::class, 'verifyEmail']);
        Route::post('verify-mobile', [AuthorizationController::class, 'verifyMobile']);
        Route::post('verify-2fa', [AuthorizationController::class, 'verifyTwoFactor']);
    });

    Route::prefix('profile')->group(function () {
        Route::get('settings', [ProfileController::class, 'settings']);
        Route::post('settings', [ProfileController::class, 'updateSettings']);
        Route::post('change-password', [ProfileController::class, 'changePassword']);
        Route::get('twofactor', [ProfileController::class, 'twoFactorStatus']);
        Route::post('twofactor/enable', [ProfileController::class, 'enableTwoFactor']);
        Route::post('twofactor/disable', [ProfileController::class, 'disableTwoFactor']);
    });

    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    Route::prefix('wallet')->group(function () {
        Route::get('balance', [WalletController::class, 'balance']);
        Route::get('transactions', [WalletController::class, 'transactions']);
        Route::get('transactions/{id}', [WalletController::class, 'show']);
    });

    Route::post('payments/intent', [PaymentController::class, 'intent']);

    Route::prefix('payment-links')->group(function () {
        Route::get('', [PaymentLinkController::class, 'index']);
        Route::post('', [PaymentLinkController::class, 'store']);
        Route::get('{id}', [PaymentLinkController::class, 'show']);
        Route::put('{id}', [PaymentLinkController::class, 'update']);
        Route::post('{id}/toggle', [PaymentLinkController::class, 'toggle']);
    });

    Route::post('payouts/request', [PayoutController::class, 'requestPayout']);

    Route::prefix('notifications')->group(function () {
        Route::get('', [NotificationController::class, 'index']);
        Route::get('poll', [NotificationController::class, 'poll']);
        Route::post('{id}/read', [NotificationController::class, 'read']);
        Route::post('read-all', [NotificationController::class, 'readAll']);
    });

    Route::prefix('support/tickets')->group(function () {
        Route::get('', [SupportTicketController::class, 'index']);
        Route::post('', [SupportTicketController::class, 'store']);
        Route::get('{ticket}', [SupportTicketController::class, 'show']);
        Route::post('{id}/reply', [SupportTicketController::class, 'reply']);
        Route::post('{id}/close', [SupportTicketController::class, 'close']);
    });
});
