<?php

use App\Exceptions\ServiceException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/v1/auth')
                ->group(base_path('routes/auth_v1.php'));

            Route::middleware(['api', 'auth:sanctum', 'verified', 'check.user.status'])
                ->prefix('api/v1')
                ->group(base_path('routes/api_v1.php'));

            Route::middleware(['api'])->prefix('/api/accounting/v1')
                ->group(base_path('routes/accounting_v1.php'));
        }
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:sanctum', 'verified']]
    )
    ->withMiddleware(function (Middleware $middleware) {
        //        $middleware->redirectGuestsTo('/login');
        $middleware->append(\App\Http\Middleware\SetLocale::class)
            ->throttleWithRedis()
            ->alias([
                'check.user.status' => \App\Http\Middleware\CheckUserStatus::class,
                'jwt.auth' => \App\Http\Middleware\JwtAuthMiddleware::class,
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (ThrottleRequestsException $e) {
            return response([
                'message' => __('auth.too_many_attempts'),
            ], 429, $e->getHeaders());
        });
        // Service Exception
        $exceptions->renderable(function (ServiceException $e, $request) {
            if (property_exists($e, 'render')) {
                return $e->render($request);
            }

            return response([
                'error' => $e->getMessage(),
            ], $e->getCode());
        });

    })->create();
