<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory, \App\Traits\ScopeByOrganization, \App\Traits\Searchable;

    protected $fillable = ['name', 'label', 'organization_id', 'is_system'];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function allowGlobalRecords(): bool
    {
        return true;
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
