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
}
