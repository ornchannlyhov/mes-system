<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DebounceMiddleware
{
    /**
     * Debounce time in seconds
     */
    protected int $debounceSeconds = 1;

    /**
     * Handle an incoming request.
     * Prevents duplicate requests within debounce window.
     */
    public function handle(Request $request, Closure $next, int $seconds = 1): Response
    {
        $this->debounceSeconds = $seconds;

        // Create unique key based on user, method, path, and body
        $key = $this->generateKey($request);

        if (Cache::has($key)) {
            return response()->json([
                'message' => 'Request debounced',
                'error' => 'Please wait before making another request',
            ], 429);
        }

        // Set debounce lock
        Cache::put($key, true, $this->debounceSeconds);

        return $next($request);
    }

    /**
     * Generate unique cache key for request
     */
    protected function generateKey(Request $request): string
    {
        $userId = $request->user()?->id ?? $request->ip();
        $method = $request->method();
        $path = $request->path();
        $body = md5(json_encode($request->all()));

        return "debounce:{$userId}:{$method}:{$path}:{$body}";
    }
}
