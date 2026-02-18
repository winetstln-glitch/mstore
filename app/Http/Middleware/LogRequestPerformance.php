<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class LogRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $durationMs = (microtime(true) - $start) * 1000;

        $response->headers->set('X-Response-Time', sprintf('%.0fms', $durationMs));

        if ($durationMs > 700) {
            Log::warning('Slow request', [
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'duration_ms' => (int) $durationMs,
                'ip' => $request->ip(),
                'user_id' => optional($request->user())->id,
            ]);
        }

        return $response;
    }
}

