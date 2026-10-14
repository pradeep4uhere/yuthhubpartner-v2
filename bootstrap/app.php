<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\SetLocale;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
                     SetLocale::class,
    ]   );
        $middleware->alias([
                'event.transaction.auth' =>
                    \App\Http\Middleware\EventTransactionAuth::class,
            ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin*')) {
                return route('admin.login');
            }

            if ($request->is('partner-center*')) {
                return route('partner-center.login');
            }

            if ($request->is('partner-placement*')) {
                return route('partner-placement.login');
            }

            if ($request->is('field-agent*')) {
                return route('field-agent.login');
            }

            if ($request->is('partner*')) {
                return route('partner.login');
            }

            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
