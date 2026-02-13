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
        $organizationId = \Auth::user()->organization_id;

        $query = Role::select(['id', 'name', 'label', 'organization_id'])
            ->with(['permissions:id,label']);

        if ($organizationId) {
            // Get names of roles specifically defined for this organization
            $localNames = Role::where('organization_id', $organizationId)->pluck('name');

            if ($localNames->isNotEmpty()) {
                $query->where(function ($q) use ($organizationId, $localNames) {
                    $q->where('organization_id', $organizationId)
                        ->orWhere(function ($q2) use ($localNames) {
                            $q2->whereNull('organization_id')
                                ->whereNotIn('name', $localNames);
                        });
                });
            }
        }

        $query->applyStandardFilters(
            $request,
            ['name', 'label'], // Searchable
            [] // Filterable
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
        if (in_array($role->name, ['admin', 'manager', 'operator'])) {
            return $this->error('Cannot delete system roles', 403);
        }

        $role->permissions()->detach();
        $role->delete();

        return $this->success(null, ['message' => 'Role deleted successfully']);
    }
}
