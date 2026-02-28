<?php

use Illuminate\Support\Facades\Route;

Route::get('/clear', function(){
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});


Route::get('cron', 'CronController@cron')->name('cron');

Route::controller('PaymentLinkController')->group(function () {
    Route::get('payment-link/{code}', 'show')->name('payment.link.show');
    Route::post('payment-link/ipn/{code}', 'ipn')->name('payment.link.ipn');
    Route::get('payment-link/redirect/{code}', 'redirect')->name('payment.link.redirect');
});

// User Support Ticket
Route::controller('TicketController')->prefix('ticket')->name('ticket.')->group(function () {
    Route::get('/', 'supportTicket')->name('index');
    Route::get('new', 'openSupportTicket')->name('open');
    Route::post('create', 'storeSupportTicket')->name('store');
    Route::get('view/{ticket}', 'viewTicket')->name('view');
    Route::post('reply/{id}', 'replyTicket')->name('reply');
    Route::post('close/{id}', 'closeTicket')->name('close');
    Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
});

Route::get('app/deposit/confirm/{hash}', 'Gateway\PaymentController@appDepositConfirm')->name('deposit.app.confirm');

Route::controller('SiteController')->group(function () {

    Route::get('/api-documentation', 'apiDocumentation')->name('api.documentation');

    Route::get('pay', 'applePay')->name('apple.pay');

    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');

    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');
    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');

    Route::get('blogs', 'blogs')->name('blogs');
    Route::get('blog/{slug}', 'blogDetails')->name('blog.details');

    Route::get('policy/{slug}', 'policyPages')->name('policy.pages');

    Route::get('placeholder-image/{size}', 'placeholderImage')->withoutMiddleware('maintenance')->name('placeholder.image');
    Route::post('/subscribe', 'subscribe')->name('subscribe');
    Route::get('maintenance-mode','maintenance')->withoutMiddleware('maintenance')->name('maintenance');

    Route::any('payment/redirect/success/{depositId}', 'successPaymentRedirect')->name('payment.redirect.success');
    Route::any('payment/redirect/cancel/{depositId}', 'cancelPaymentRedirect')->name('payment.redirect.cancel');

    Route::get('/{slug}', 'pages')->name('pages');
    Route::get('/', 'index')->name('home');
});

Route::prefix('payment')->name('deposit.')->controller('Gateway\PaymentController')->group(function(){
    Route::any('/', 'deposit')->name('index');
    Route::post('insert', 'depositInsert')->name('insert');
    Route::get('confirm', 'depositConfirm')->name('confirm');
});

//Live payment
Route::controller('LivePaymentController')->prefix('payment')->name('payment.')->group(function () {
    Route::post('initiate', 'paymentInitiate')->name('initiate');
    Route::get('checkout', 'paymentCheckout')->name('checkout');
    Route::get('cancel/{paymentTrx}', 'paymentCancel')->name('cancel');
});

// Test payment
Route::controller('TestPaymentController')->prefix('test/payment')->name('test.payment.')->group(function () {
    Route::post('initiate', 'paymentInitiate')->name('initiate');
    Route::get('checkout', 'paymentCheckout')->name('checkout'); 
    Route::post('success', 'paymentSuccess')->name('success');
    Route::get('cancel/{paymentTrx}', 'paymentCancel')->name('cancel');
});
