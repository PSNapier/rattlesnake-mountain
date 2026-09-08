<?php

use App\Http\Middleware\DevPasswordProtection;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RateLimitUploads;
use App\Http\Middleware\SkipEmailVerification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->alias([
            'rate.limit.uploads' => RateLimitUploads::class,
            'verified' => SkipEmailVerification::class,
        ]);

        $middleware->web(append: [
            DevPasswordProtection::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
