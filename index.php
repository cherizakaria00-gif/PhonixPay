<?php

use Illuminate\Http\Request;

// Hide noisy PHP 8.5 deprecation notices from legacy vendor packages in local runtime.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/core/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/core/bootstrap/app.php')
    ->handleRequest(Request::capture());
