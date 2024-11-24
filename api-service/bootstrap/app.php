<?php

use App\Exceptions\ServiceException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('auth')
                ->group(base_path('routes/auth.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        //        $middleware->redirectGuestsTo('/login');
        $middleware->append(\App\Http\Middleware\SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //Service Exception
        $exceptions->renderable(function (ServiceException $e, $request) {
            if (property_exists($e, 'render')) {
                return $e->render($request);
            }

            return response([
                'error' => $e->getMessage(),
            ], $e->getCode());
        });

    })->create();
