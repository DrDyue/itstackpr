<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Atļauj piekļuvi tikai pilnam administratora skatam.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canManageRequests(), 403);

        return $next($request);
    }
}
