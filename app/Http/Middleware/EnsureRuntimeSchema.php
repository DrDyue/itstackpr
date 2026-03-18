<?php

namespace App\Http\Middleware;

use App\Support\RuntimeSchemaBootstrapper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRuntimeSchema
{
    public function __construct(
        private readonly RuntimeSchemaBootstrapper $bootstrapper
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->bootstrapper->ensure();

        return $next($request);
    }
}
