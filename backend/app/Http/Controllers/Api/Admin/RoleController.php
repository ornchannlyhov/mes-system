<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    public function index(Request $request)
    {
        // ScopeByOrganization + allowGlobalRecords() already returns:
        //   rows WHERE organization_id = current_org OR organization_id IS NULL
        // We only need to exclude the superadmin role (is_system = true) which has
        // organization_id = NULL and must never appear in an org's role management UI.
        $query = Role::select(['id', 'name', 'label', 'organization_id', 'is_system'])
            ->with(['permissions:id,label'])
            ->where('is_system', false);

        $query->applyStandardFilters(
            $request,
            ['name', 'label'],
            []
        );

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 100))
        );
    }

    public function permissions()
    {
        return $this->success(Permission::all());
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        // Roles usually don't have many other fields to update based on current file, 
        // but if name/label were updatable they would be here. The request validates it.
        $role->update($validated);

        return $this->success($role->load('permissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => strtolower($validated['name']),
            'label' => $validated['label'],
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return $this->success($role->load('permissions'), [], 201);
    }

    public function destroy(Role $role)
    {
        if ($role->is_system || in_array($role->name, ['admin', 'manager', 'operator'])) {
            return $this->error('Cannot delete system roles', 403);
        }

        $role->permissions()->detach();
        $role->delete();

        return $this->success(null, ['message' => 'Role deleted successfully']);
    }
}
