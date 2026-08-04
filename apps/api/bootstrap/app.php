<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'active.employment' => \App\Http\Middleware\EnsureEmploymentActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // This is a JSON-only API (no login view exists) — without this, an
        // unauthenticated request that doesn't send Accept: application/json
        // makes Laravel's default Authenticate middleware try to redirect to
        // a "login" route that was never defined, producing a 500 instead of
        // a clean 401.
        $exceptions->shouldRenderJsonWhen(fn () => true);

        // Domain-rule violations (wage compliance, invalid rehire state, etc.)
        // are client errors (422), not server errors — DomainException and its
        // subclasses are how every Domain\* service signals "this action is
        // not allowed right now" as opposed to an actual bug.
        $exceptions->render(function (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
    })->create();
