<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $requestId = $request->headers->get('X-Request-Id') ?: Str::uuid()->toString();
        $request->headers->set('X-Request-Id', $requestId);
        Log::withContext([
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->path(),
        ]);
        /** @var Response $response */
        $response = $next($request);
        $durationMs = (microtime(true) - $start) * 1000;

        $response->headers->set('X-Response-Time', sprintf('%.0fms', $durationMs));
        $response->headers->set('X-Request-Id', $requestId);

        if ($durationMs > 700) {
            Log::warning('Slow request', [
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'duration_ms' => (int) $durationMs,
                'ip' => $request->ip(),
                'user_id' => optional($request->user())->id,
                'request_id' => $requestId,
            ]);
        }

        return $response;
    }
}
