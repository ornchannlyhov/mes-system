<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();


        // Generate OTP — random_int() is cryptographically secure unlike rand()
        $otp = random_int(100000, 999999);

        // Clean up expired registration attempts
        \App\Models\RegistrationAttempt::where('expires_at', '<', now())->delete();

        // Hash password before storing so registration_attempts never holds plaintext credentials.
        // Laravel's `hashed` cast on User detects the bcrypt prefix and will NOT re-hash on User::create().
        \App\Models\RegistrationAttempt::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5),
            ]
        );

        // Send OTP Email
        \Illuminate\Support\Facades\Mail::to($validated['email'])->send(new \App\Mail\OtpMail((string) $otp));

        return response()->json([
            'message' => 'Please verify your email address.',
            'requires_verification' => true
        ], 200);
    }


    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::withoutGlobalScope('organization')
            ->where('email', $validated['email'])
            ->with(['role.permissions', 'organization'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Superadmins must use the separate superadmin login
        if ($user->isSuperadmin()) {
            throw ValidationException::withMessages([
                'email' => ['Superadmin must use the system admin login portal.'],
            ]);
        }

        // Check login eligibility using the canLogin method
        $loginCheck = $user->canLogin();
        if (!$loginCheck['can_login']) {
            $messages = [
                'pending_approval' => 'Your account is pending approval. You will be notified when approved.',
                'account_deactivated' => 'This account has been deactivated.',
                'organization_suspended' => 'Organization access has been suspended. Please contact support.',
            ];

            throw ValidationException::withMessages([
                'email' => [$messages[$loginCheck['reason']] ?? 'Account access restricted.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('role.permissions'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function refreshToken(Request $request)
    {
        $user = $request->user();

        // Delete the current token
        $request->user()->currentAccessToken()->delete();

        // Create a new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_in' => 7200, // 2 hours in seconds
        ]);
    }


    public function user(Request $request)
    {
        return response()->json($request->user()->load('role.permissions'));
    }

    public function verifyEmail(VerifyEmailRequest $request)
    {
        $validated = $request->validated();

        $attempt = \App\Models\RegistrationAttempt::where('email', $validated['email'])
            ->where('otp', $validated['code'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$attempt) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 400);
        }

        // Transaction to ensure atomicity
        $data = \Illuminate\Support\Facades\DB::transaction(function () use ($attempt) {
            // 1. Create User (unapproved, pending superadmin approval)
            $user = User::create([
                'name' => $attempt->name,
                'email' => $attempt->email,
                'password' => $attempt->password, 
                'email_verified_at' => now(),
                'is_active' => true,
                'is_approved' => false, 
                'organization_id' => null,  
                'role_id' => null, 
            ]);

            // 2. Delete Attempt
            $attempt->delete();

            // Note: No token generated - user must wait for superadmin approval
            return [
                'user' => $user,
                'requires_approval' => true,
            ];
        });

        return response()->json([
            'message' => 'Email verified successfully. Your account is pending approval. You will receive an email when your account is activated.',
            'requires_approval' => true,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $validated = $request->validated();

        $data = [];
        if (isset($validated['name'])) {
            $data['name'] = $validated['name'];
        }
        if (isset($validated['email'])) {
            $data['email'] = $validated['email'];
        }

        if ($request->hasFile('avatar')) {
            // Store file in public/avatars
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = '/storage/' . $path;
        }

        $request->user()->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $request->user()->fresh()->load('role.permissions'),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password incorrect'], 422);
        }

        $user->update(['password' => Hash::make($validated['new_password'])]);

        return response()->json(['message' => 'Password updated successfully']);
    }

}
