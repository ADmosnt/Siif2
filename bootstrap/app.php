<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->alias([
            'report.access' => \App\Http\Middleware\CheckReportAccess::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        $middleware->statefulApi();

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //de esta manera se excluyen las rutas del middleware csrf, solamente para pruebas en local
        /*$middleware->validateCsrfTokens(except: [
            'login',
            'tmp-planificaciones',       // Esto cubre: POST /tmp-planificaciones
            'tmp-planificaciones/*',     // Esto cubre: PUT, DELETE,
        ]);*/
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
