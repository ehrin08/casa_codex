<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->redirectUsersTo(
            fn (Request $request): string => route($request->user()?->dashboardRouteName() ?? 'home'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidSignatureException $exception, Request $request) {
            if ($request->routeIs('verification.verify')) {
                return redirect()
                    ->route('verification.notice')
                    ->withErrors([
                        'verification' => 'That verification link is invalid or has expired. Please send a new verification link.',
                    ]);
            }

            return null;
        });
    })->create();
