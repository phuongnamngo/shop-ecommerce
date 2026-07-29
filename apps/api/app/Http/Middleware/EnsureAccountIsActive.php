<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $user = $guard
            ? $request->user($guard)
            : $request->user();

        if ($user === null) {
            abort(401);
        }

        if (! method_exists($user, 'isActive') || ! $user->isActive()) {
            abort(403, 'Account is not active.');
        }

        return $next($request);
    }
}
