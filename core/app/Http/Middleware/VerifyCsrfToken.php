<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs that should be excluded from CSRF verification.
     *
     * PSP callbacks/webhooks are server-to-server requests and do not carry
     * browser CSRF tokens, so they must be explicitly exempted.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/webhooks/bictorys',
        'webhook-endpoint',
        'api/webhook-endpoint',
        'bictorys/webhook',
        'ipn/bictorys',
        'ipn/bictorys-checkout',
        'ipn/bictorys-direct',
    ];
}
