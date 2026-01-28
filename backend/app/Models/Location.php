<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use \App\Traits\ScopeByOrganization;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'organization_id',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
