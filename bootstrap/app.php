<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Support\UserFacingDatabaseError;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\EnsureRuntimeSchema::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'manager' => \App\Http\Middleware\EnsureManager::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $exception, Request $request) {
            $message = UserFacingDatabaseError::message($exception);
            $status = UserFacingDatabaseError::status($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], $status);
            }

            if (! $request->isMethod('get') && $request->headers->has('referer')) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                    ->with('error', $message);
            }

            return response()->view('errors.database', [
                'title' => UserFacingDatabaseError::title($exception),
                'message' => $message,
            ], $status);
        });
    })->create();
