<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\Admin;
use App\Http\Middleware\KepalaToko;
use App\Http\Middleware\CheckActiveShift;
use App\Http\Middleware\CheckShiftBlocking;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\CheckUserActive::class,
        ]);
        $middleware->alias([
            'owner' => \App\Http\Middleware\Owner::class,
            'finance' => \App\Http\Middleware\Finance::class,
            'kepala_toko' => KepalaToko::class,
            'admin' => Admin::class,
            'editor' => \App\Http\Middleware\Editor::class,
            'check.shift' => CheckActiveShift::class,
            'check.shift.blocking' => CheckShiftBlocking::class,
            'check.user.active' => \App\Http\Middleware\CheckUserActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
