<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperadminController extends Controller
{
    /**
     * Dashboard statistics.
     */
    public function dashboard(Request $request)
    {
        // Only count organizations whose owner is approved
        $approvedOrgIds = User::withoutGlobalScope('organization')
            ->where('is_approved', true)
            ->whereNotNull('organization_id')
            ->pluck('organization_id');

        $stats = [
            'pending_approvals_count' => User::withoutGlobalScope('organization')
                ->where('is_approved', false)
                ->count(),
            'active_organizations_count' => Organization::whereIn('id', $approvedOrgIds)->where('is_active', true)->count(),
            'inactive_organizations_count' => Organization::whereIn('id', $approvedOrgIds)->where('is_active', false)->count(),
            'total_users_count' => User::withoutGlobalScope('organization')
                ->whereNotNull('organization_id')
                ->count(),
        ];

        // Recent pending approvals (last 5)
        $recentPending = User::withoutGlobalScope('organization')
            ->where('is_approved', false)
            ->with('organization')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_pending' => $recentPending,
        ]);
    }

    /**
     * List all pending user approvals.
     */
    public function pendingUsers(Request $request)
    {
        $query = User::withoutGlobalScope('organization')
            ->where('is_approved', false)
            ->with(['organization', 'role'])
            ->applyStandardFilters(
                $request,
                ['name', 'email'],
                []
            );

        $paginator = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Approve a user and activate their organization.
     */
    public function approveUser(Request $request, $userId)
    {
        $user = User::withoutGlobalScope('organization')->find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Validate this is a pending user
        if ($user->is_approved) {
            return response()->json(['message' => 'User is already approved.'], 400);
        }

        $superadmin = $request->user();

        DB::transaction(function () use ($user, $superadmin) {
            // Handle both old flow (user has org) and new flow (user has no org)
            if ($user->organization_id) {
                // Old flow: User already has organization, just approve and activate
                $user->update([
                    'is_approved' => true,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => $superadmin->id,
                ]);

                // Activate the organization
                $user->organization->update([
                    'is_active' => true,
                    'activated_at' => now(),
                    'activated_by' => $superadmin->id,
                    'user_limit' => 3, // Set default user limit
                ]);
            } else {
                // New flow: Create organization and assign user
                // 1. Create Organization
                $organization = \App\Models\Organization::create([
                    'name' => $user->name . "'s Organization",
                    'is_active' => true, // Active by default after approval
                    'activated_at' => now(),
                    'activated_by' => $superadmin->id,
                    'user_limit' => 3, // Default user limit
                ]);

                // 2. Create Default Roles for the Organization
                $adminRole = \App\Models\Role::create([
                    'name' => 'admin',
                    'label' => 'Administrator',
                    'organization_id' => $organization->id,
                ]);

                $managerRole = \App\Models\Role::create([
                    'name' => 'manager',
                    'label' => 'Manager',
                    'organization_id' => $organization->id,
                ]);

                $operatorRole = \App\Models\Role::create([
                    'name' => 'operator',
                    'label' => 'Operator',
                    'organization_id' => $organization->id,
                ]);

                // 3. Assign Permissions
                $allPermissions = \App\Models\Permission::all();

                // Admin: All permissions
                $adminRole->permissions()->sync($allPermissions->pluck('id'));

                // Manager: All except some admin functions
                $managerPermissions = \App\Models\Permission::where('name', 'not like', 'roles:%')
                    ->where('name', 'not like', 'settings:%')
                    ->where('name', 'not like', 'users:%')
                    ->get();
                $managerRole->permissions()->sync($managerPermissions->pluck('id'));

                // Operator: Execution, View, and simple operations
                $operatorPermissions = \App\Models\Permission::where(function ($query) {
                    $query->where('name', 'like', '%:read') // View everything
                        ->orWhereIn('name', [
                            'manufacturing:execute',
                            'quality:write',
                            'inventory:transfer',
                            'inventory:adjust',
                            'maintenance:read'
                        ]);
                })->get();
                $operatorRole->permissions()->sync($operatorPermissions->pluck('id'));

                // 4. Approve the user and assign organization and role
                $user->update([
                    'is_approved' => true,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => $superadmin->id,
                    'organization_id' => $organization->id,
                    'role_id' => $adminRole->id, // User becomes admin of their org
                ]);

                // 5. Set user as organization owner
                $organization->update([
                    'owner_id' => $user->id,
                ]);
            }
        });

        // TODO: Send approval email notification to user

        return response()->json([
            'message' => 'User approved and organization activated successfully.',
            'user' => $user->load('organization'),
        ]);
    }

    /**
     * Reject a user (delete user and their organization).
     */
    public function rejectUser(Request $request, $userId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::withoutGlobalScope('organization')->find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->is_approved) {
            return response()->json(['message' => 'Cannot reject already approved user.'], 400);
        }

        // Delete the user
        $user->delete();

        // If user had an organization, delete it if it has no other members
        if ($user->organization_id) {
            $organization = $user->organization;
            if ($organization && $organization->members()->count() === 0) {
                $organization->delete();
            }
        }

        // TODO: Send rejection email notification with reason

        return response()->json([
            'message' => 'User registration rejected and removed.',
        ]);
    }

    /**
     * List all organizations (only those with approved owners).
     */
    public function organizations(Request $request)
    {
        // Only get organizations whose owner is approved
        $approvedOrgIds = User::withoutGlobalScope('organization')
            ->where('is_approved', true)
            ->whereNotNull('organization_id')
            ->pluck('organization_id');

        $query = Organization::whereIn('id', $approvedOrgIds)
            ->with(['owner', 'activatedBy'])
            ->withCount(['members' => function ($query) {
                $query->withoutGlobalScope('organization');
            }])
            ->applyStandardFilters(
                $request,
                ['name'],
                ['is_active']
            );

        $paginator = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Show organization details with all members.
     */
    public function showOrganization(Request $request, Organization $organization)
    {
        $organization->load(['owner', 'activatedBy']);
        
        // Load members without global scope to see all users in the organization
        $organization->load(['members' => function ($query) {
            $query->withoutGlobalScope('organization')->with('role');
        }]);

        return response()->json($organization);
    }

    /**
     * Update organization user limit.
     */
    public function updateUserLimit(Request $request, Organization $organization)
    {
        $request->validate([
            'user_limit' => 'required|integer|min:1|max:100',
        ]);

        $organization->update([
            'user_limit' => $request->user_limit,
        ]);

        return response()->json([
            'message' => 'User limit updated successfully.',
            'organization' => $organization->fresh(),
        ]);
    }

    /**
     * Activate an organization.
     */
    public function activateOrganization(Request $request, Organization $organization)
    {
        if ($organization->is_active) {
            return response()->json(['message' => 'Organization is already active.'], 400);
        }

        $superadmin = $request->user();

        $organization->update([
            'is_active' => true,
            'activated_at' => now(),
            'activated_by' => $superadmin->id,
        ]);

        return response()->json([
            'message' => 'Organization activated successfully.',
            'organization' => $organization->load(['owner', 'activatedBy']),
        ]);
    }

    /**
     * Deactivate an organization.
     */
    public function deactivateOrganization(Request $request, Organization $organization)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if (!$organization->is_active) {
            return response()->json(['message' => 'Organization is already inactive.'], 400);
        }

        $organization->update([
            'is_active' => false,
            'activated_at' => null,
            'activated_by' => null,
        ]);

        // TODO: Notify organization owner of deactivation with reason

        return response()->json([
            'message' => 'Organization deactivated successfully.',
            'organization' => $organization->load(['owner']),
        ]);
    }

    /**
     * List all users across all organizations.
     */
    public function allUsers(Request $request)
    {
        $query = User::withoutGlobalScope('organization')
            ->whereNotNull('organization_id')
            ->with(['organization', 'role'])
            ->applyStandardFilters(
                $request,
                ['name', 'email'],
                ['is_active', 'is_approved', 'organization_id', 'role_id']
            );

        $paginator = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Show user details.
     */
    public function showUser(Request $request, $userId)
    {
        $user = User::withoutGlobalScope('organization')->find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (!$user->organization_id) {
            return response()->json(['message' => 'Cannot view superadmin users.'], 403);
        }

        $user->load(['organization', 'role.permissions', 'approvedBy']);

        return response()->json($user);
    }
}
