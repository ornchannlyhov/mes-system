<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

class Permission extends Model
{
    public const DEFAULT_PERMISSIONS = [
        // Inventory
        'inventory:read' => 'View Inventory',
        'inventory:write' => 'Manage Inventory',
        'inventory:adjust' => 'Adjust Stock',
        'inventory:transfer' => 'Transfer Stock',
        'inventory:scrap' => 'Scrap Items',

        // Manufacturing
        'manufacturing:read' => 'View Manufacturing Orders',
        'manufacturing:write' => 'Manage Manufacturing Orders',
        'manufacturing:plan' => 'Plan Production',
        'manufacturing:execute' => 'Execute Work Orders',

        // Quality
        'quality:read' => 'View Quality Checks',
        'quality:write' => 'Perform Quality Checks',
        'quality:approve' => 'Approve Quality Results',

        // Maintenance
        'maintenance:read' => 'View Maintenance',
        'maintenance:write' => 'Manage Maintenance',

        // Engineering
        'products:read' => 'View Products',
        'products:manage' => 'Manage Products',
        'boms:read' => 'View BOMs',
        'boms:manage' => 'Manage BOMs',
        'work_centers:read' => 'View Work Centers',
        'work_centers:manage' => 'Manage Work Centers',

        // Traceability
        'lots:read' => 'View Lots',
        'lots:manage' => 'Manage Lots',
        'serials:read' => 'View Serials',
        'serials:manage' => 'Manage Serials',

        // Reporting
        'reporting:view' => 'View Reports', // Cost, OEE

        // Admin
        'users:manage' => 'Manage Users',
        'roles:assign' => 'Assign Roles',
        'settings:manage' => 'System Settings',
    ];
    protected $fillable = ['name', 'label'];

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }
}
