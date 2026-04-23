<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\TrustProxies::class,
        ], append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\LogRequestPerformance::class,
        ]);

        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            try {
                $request = request();
                $errorId = (string) Str::uuid();
                Log::error('Unhandled exception', [
                    'error_id' => $errorId,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile().':'.$e->getLine(),
                    'url' => $request?->fullUrl(),
                    'method' => $request?->method(),
                    'ip' => $request?->ip(),
                    'user_id' => optional($request?->user())->id,
                    'request_id' => $request?->headers->get('X-Request-Id'),
                ]);
                app()->instance('last_error_id', $errorId);
            } catch (\Throwable $logError) {
                // Fallback: avoid breaking the exception flow
            }
        });
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (config('app.debug')) {
                return null;
            }
            if (
                $e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Illuminate\Auth\AuthenticationException ||
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            ) {
                return null;
            }
            $errorId = app()->bound('last_error_id') ? app('last_error_id') : (string) Str::uuid();
            $acceptsJson = $request->expectsJson();
            if ($acceptsJson) {
                return response()->json([
                    'message' => 'Terjadi kesalahan pada server',
                ], 500)->header('X-Error-Id', $errorId);
            }
            $html = '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Server Error</title><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8f9fa;color:#212529"><div style="text-align:center"><h1 style="font-size:22px;margin:0 0 8px">Terjadi kesalahan pada server</h1><p style="margin:0 0 10px">Silakan coba beberapa saat lagi.</p></div></body></html>';

            return response($html, 500)->header('X-Error-Id', $errorId);
        });
    })->create();
