<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends Model
{
    use \App\Traits\ScopeByOrganization;
    use \App\Traits\Searchable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'location',
        'cost_per_hour',
        'overhead_per_hour',
        'efficiency_percent',
        'status',
        'organization_id',
    ];

    protected $casts = [
        'cost_per_hour' => 'decimal:2',
        'overhead_per_hour' => 'decimal:2',
        'efficiency_percent' => 'decimal:2',
    ];

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}

