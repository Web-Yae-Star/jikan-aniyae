<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class RequestTimingMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);

        $durationMs = (microtime(true) - $start) * 1000;
        $method = strtoupper($request->method());
        $path = $request->path();

        Log::info('request_timing', [
            'method' => $method,
            'path' => $path,
            'status' => $response->status(),
            'duration_ms' => round($durationMs, 2),
            'query' => $request->query(),
        ]);

        return $response;
    }
}
