<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, \App\Traits\ScopeByOrganization, \App\Traits\Searchable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_approved',
        'approved_at',
        'approved_by',
        'role_id',
        'organization_id',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_to');
    }

    public function scraps(): HasMany
    {
        return $this->hasMany(Scrap::class, 'reported_by');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'performed_by');
    }

    // Role checks
    public function isAdmin(): bool
    {
        return $this->role && $this->role->name === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role && $this->role->name === 'manager';
    }

    public function isOperator(): bool
    {
        return $this->role && $this->role->name === 'operator';
    }

    public function isSuperadmin(): bool
    {
        return $this->role && $this->role->name === 'superadmin';
    }

    public function belongsToOrganization(): bool
    {
        return $this->organization_id !== null;
    }

    public function canLogin(): array
    {
        // Superadmin can always login
        if ($this->isSuperadmin()) {
            return ['can_login' => true];
        }

        // Organization users need approval and active organization
        if (!$this->is_approved) {
            return ['can_login' => false, 'reason' => 'pending_approval'];
        }

        if (!$this->is_active) {
            return ['can_login' => false, 'reason' => 'account_deactivated'];
        }

        if (!$this->organization || !$this->organization->is_active) {
            return ['can_login' => false, 'reason' => 'organization_suspended'];
        }

        return ['can_login' => true];
    }

    // Approval relationship
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
