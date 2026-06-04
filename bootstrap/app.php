<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $isLocal = ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production') === 'local';

        if (!$isLocal) {
            $middleware->web(append: [
                \Spatie\ResponseCache\Middlewares\CacheResponse::class,
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, $request) {
            if ($exception->getStatusCode() !== 403) {
                return null;
            }

            Log::warning('HTTP 403 detected', [
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'method' => $request->method(),
                'route_name' => $request->route()?->getName(),
                'user_id' => $request->user()?->id,
                'is_admin' => (bool) $request->user()?->is_admin,
                'email_verified_at' => $request->user()?->email_verified_at,
                'message' => $exception->getMessage(),
            ]);

            return null;
        });
    })->create();
