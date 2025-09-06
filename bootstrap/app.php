<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
   ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
       'firebase.auth' => \App\Http\Middleware\FirebaseAuth::class,
        'firebase.admin' => \App\Http\Middleware\FirebaseAdmin::class,

        // Alternative short aliases
        'auth' => \App\Http\Middleware\FirebaseAuth::class,
        'admin' => \App\Http\Middleware\FirebaseAdmin::class,    ]);
})

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
