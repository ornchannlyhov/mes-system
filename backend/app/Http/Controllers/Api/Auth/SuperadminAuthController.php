<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SuperadminAuthController extends Controller
{
    /**
     * Login for superadmin users only.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->with('role.permissions')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Only allow superadmin role
        if (!$user->isSuperadmin()) {
            throw ValidationException::withMessages([
                'email' => ['This login is for system administrators only.'],
            ]);
        }

        $token = $user->createToken('superadmin_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout superadmin.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get current superadmin user.
     */
    public function user(Request $request)
    {
        return response()->json($request->user()->load('role.permissions'));
    }
}
