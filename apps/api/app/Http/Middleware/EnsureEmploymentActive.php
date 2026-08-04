<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Revokes access the moment an employment is terminated. Without this, a
 * terminated employee's existing Sanctum session/token keeps working
 * indefinitely — TerminationService deletes tokens/invalidates the session
 * at the moment of termination, but this middleware is the backstop for any
 * token/session that predates that (e.g. a second device already logged in).
 */
class EnsureEmploymentActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Query fresh via the relation method (not the cached ->employment
        // property) — a long-lived request cycle or a test harness that
        // reuses the same resolved User instance across calls would
        // otherwise see a stale, pre-termination status.
        $employment = $user?->employment()->first();

        if ($user && $employment && $employment->employment_status === 'terminated') {
            $user->tokens()->delete();

            if ($request->hasSession()) {
                $request->session()->invalidate();
            }

            abort(401, 'This account no longer has access.');
        }

        return $next($request);
    }
}
