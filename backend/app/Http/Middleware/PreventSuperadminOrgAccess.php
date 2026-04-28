<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSuperadminOrgAccess
{
    /**
     * Prevent superadmin users from accessing organization routes.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuperadmin()) {
            return response()->json([
                'message' => 'Superadmin cannot access organization resources. Use the system admin portal.'
            ], 403);
        }

        return $next($request);
    }
}
