<?php

use App\Http\Controllers\User\PlanController as UserPlanController;
use Illuminate\Support\Facades\Route;

Route::namespace('User\Auth')->name('user.')->middleware('guest')->group(function () {
    Route::controller('LoginController')->group(function(){
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login');
        Route::get('logout', 'logout')->middleware('auth')->withoutMiddleware('guest')->name('logout');
    });

    Route::controller('RegisterController')->middleware(['guest'])->group(function(){
        Route::get('register', 'showRegistrationForm')->name('register');
        Route::post('register', 'register');
        Route::post('check-user', 'checkUser')->name('checkUser')->withoutMiddleware('guest');
    });

    Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function(){
        Route::get('reset', 'showLinkRequestForm')->name('request');
        Route::post('email', 'sendResetCodeEmail')->name('email');
        Route::get('code-verify', 'codeVerify')->name('code.verify');
        Route::post('verify-code', 'verifyCode')->name('verify.code');
    });

    Route::controller('ResetPasswordController')->group(function(){
        Route::post('password/reset', 'reset')->name('password.update');
        Route::get('password/reset/{token}', 'showResetForm')->name('password.reset');
    });

    Route::controller('SocialiteController')->group(function () {
        Route::get('social-login/{provider}', 'socialLogin')->name('social.login');
        Route::get('social-login/callback/{provider}', 'callback')->name('social.login.callback');
    });
});

Route::middleware('auth')->name('user.')->group(function () {

    Route::get('user-data', 'User\UserController@userData')->name('data');
    Route::post('user-data-submit', 'User\UserController@userDataSubmit')->name('data.submit');

    //authorization
    Route::middleware('registration.complete')->namespace('User')->controller('AuthorizationController')->group(function(){
        Route::get('authorization', 'authorizeForm')->name('authorization');
        Route::get('resend-verify/{type}', 'sendVerifyCode')->name('send.verify.code');
        Route::post('authorization/update-email', 'updateEmail')->name('authorization.email.update');
        Route::post('verify-email', 'emailVerification')->name('verify.email');
        Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile');
        Route::post('verify-g2fa', 'g2faVerification')->name('2fa.verify');
    });

    Route::middleware(['check.status','registration.complete'])->group(function () {

        Route::namespace('User')->group(function () {

            Route::controller('UserController')->group(function(){
                Route::get('dashboard', 'home')->name('home');
                Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');
                Route::get('notifications', 'notifications')->name('notifications');
                Route::get('notification/read/{id}', 'notificationRead')->name('notification.read');
                Route::post('notifications/read-all', 'notificationReadAll')->name('notifications.read.all');
                Route::get('notifications/poll', 'notificationPoll')->name('notifications.poll');

                Route::get('calculate-charge', 'calculateCharge')->name('calculate.charge')->middleware('user.restricted');
                Route::get('dashboard/statistics', 'dashboardStatistics')->name('dashboard.statistics');

                Route::get('api-key', 'apiKey')->name('api.key')->middleware('user.restricted');
                Route::post('api-key', 'generateApiKey')->name('generate.key')->middleware('user.restricted');

                Route::get('gateway/methods', 'gatewayMethods')->name('gateway.methods')->middleware('user.restricted');

                //2FA
                Route::get('twofactor', 'show2faForm')->name('twofactor');
                Route::post('twofactor/enable', 'create2fa')->name('twofactor.enable');
                Route::post('twofactor/disable', 'disable2fa')->name('twofactor.disable');

                //KYC
                Route::get('merchant-form','kycForm')->name('kyc.form');
                Route::get('merchant-data','kycData')->name('kyc.data');
                Route::post('merchant-submit','kycSubmit')->name('kyc.submit');

                //Report
                Route::any('deposit/history', 'depositHistory')->name('deposit.history');
                Route::get('transactions','transactions')->name('transactions');

                Route::post('add-device-token','addDeviceToken')->name('add.device.token');
            });

            Route::controller('PaymentLinkController')->prefix('payment-links')->name('payment.links.')->middleware('user.restricted')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('create', 'store')->name('store');
                Route::get('edit/{id}', 'edit')->name('edit');
                Route::post('edit/{id}', 'update')->name('update');
            });

            Route::controller('RewardController')->prefix('rewards')->name('rewards.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('overview-data', 'overview')->name('overview');
                Route::get('ledger-data', 'ledger')->name('ledger');
                Route::post('referral-code/regenerate', 'regenerateCode')->name('code.regenerate');
            });

            //Profile setting
            Route::controller('ProfileController')->group(function(){
                Route::get('profile-setting', 'profile')->name('profile.setting');
                Route::post('profile-setting', 'submitProfile');
                Route::get('change-password', 'changePassword')->name('change.password');
                Route::post('change-password', 'submitPassword');
            });

            Route::prefix('plan-billing')->name('plan.')->group(function () {
                Route::get('/', [UserPlanController::class, 'billing'])->name('billing');
                Route::post('change', [UserPlanController::class, 'change'])->name('change');
                Route::post('request-change', [UserPlanController::class, 'requestChange'])->name('request.change');
            });

            // Withdraw
            Route::controller('WithdrawController')->group(function(){
                Route::middleware('kyc')->group(function(){ 
                    Route::get('/withdraw/method', 'withdrawMethod')->name('withdraw.method')->middleware('user.restricted');
                    Route::post('/withdraw/method', 'withdrawMethodSubmit')->name('withdraw.method.submit')->middleware('user.restricted');
                    Route::get('/download/withdraw/attachments/{fileHash}', 'downloadAttachment')->name('withdraw.download.attachment');
                });
                Route::post('/withdraw/request', 'requestPayout')->name('withdraw.request');
                Route::get('/withdraws', 'withdraws')->name('withdraws');
            });
        });
    });
});
