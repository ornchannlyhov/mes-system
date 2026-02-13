<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use \App\Traits\ScopeByOrganization, \App\Traits\Searchable;

    protected $fillable = ['name', 'label', 'organization_id'];

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
}
