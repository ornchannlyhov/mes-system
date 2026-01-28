<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Valid API keys (in production, store in database or config)
     */
    protected array $validKeys;

    public function __construct()
    {
        $this->validKeys = explode(',', config('app.api_keys', ''));
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'message' => 'API key missing',
                'error' => 'X-API-Key header is required',
            ], 401);
        }

        if (!in_array($apiKey, $this->validKeys)) {
            return response()->json([
                'message' => 'Invalid API key',
                'error' => 'The provided API key is not valid',
            ], 403);
        }

        return $next($request);
    }
}
