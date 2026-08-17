<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use App\Http\Middleware\CheckActiveStatus;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // En produccion la app corre detras de un reverse proxy que termina el
        // HTTPS (el Apache de cPanel -> 127.0.0.1:8080 -> nginx de Docker).
        // Sin esto Laravel solo ve la peticion interna, que es HTTP, y genera
        // URLs "http://" dentro de una pagina "https://": el navegador las
        // bloquea por contenido mixto y la pagina queda en blanco. Con
        // trustProxies, Laravel respeta el X-Forwarded-Proto que envia Apache.
        // Se confia en cualquier proxy ('*') porque el contenedor no se publica
        // a internet: nginx escucha solo en 127.0.0.1 (docker-compose.prod.yml),
        // asi que la unica forma de llegar es a traves de ese proxy local.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->alias([
            'report.access' => \App\Http\Middleware\CheckReportAccess::class,
            'active.status' => CheckActiveStatus::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        $middleware->statefulApi();

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CheckActiveStatus::class,
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
