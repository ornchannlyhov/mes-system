<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
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


        // Generate OTP
        $otp = rand(100000, 999999);

        // Clean up expired registration attempts
        \App\Models\RegistrationAttempt::where('expires_at', '<', now())->delete();

        // Store registration attempt (deferred creation)
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

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
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
            // 1. Create Organization
            $organization = \App\Models\Organization::create([
                'name' => $attempt->name . "'s Organization",
            ]);

            // 2. Create Admin Role
            $adminRole = \App\Models\Role::create([
                'name' => 'admin',
                'label' => 'Administrator',
                'organization_id' => $organization->id,
            ]);

            // 3. Assign Permissions
            $permissionIds = [];
            foreach (\App\Models\Permission::DEFAULT_PERMISSIONS as $name => $label) {
                $permission = \App\Models\Permission::firstOrCreate(['name' => $name], ['label' => $label]);
                $permissionIds[] = $permission->id;
            }
            $adminRole->permissions()->sync($permissionIds);

            // 4. Create User
            $user = User::create([
                'name' => $attempt->name,
                'email' => $attempt->email,
                'password' => $attempt->password, // Already hashed in register
                'role_id' => $adminRole->id,
                'organization_id' => $organization->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $organization->update(['owner_id' => $user->id]);

            // 5. Delete Attempt
            $attempt->delete();

            // 6. Generate Token
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->load('role.permissions'),
                'token' => $token,
            ];
        });

        return response()->json([
            'message' => 'Email verified and account created successfully.',
            'user' => $data['user'],
            'token' => $data['token'],
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

    public function index()
    {
        return response()->json(User::with('role')->get());
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        $validated = $request->validated();

        $user->update(['role_id' => $validated['role_id']]);

        return response()->json($user->load('role'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $userData['avatar_url'] = '/storage/' . $path;
        }

        $user->update($userData);

        return response()->json($user->load('role'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $validated['role_id'],
            'organization_id' => $request->user()->organization_id ?? 1, // Default or current user's org
            'is_active' => true,
        ]);

        return response()->json($user->load('role'), 201);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 403);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}
