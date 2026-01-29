<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    /**
     * @unauthenticated
     */
    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
