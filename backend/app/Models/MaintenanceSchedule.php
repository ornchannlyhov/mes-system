<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceSchedule extends Model
{
    use \App\Traits\ScopeByOrganization;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'equipment_id',
        'name',
        'trigger_type',
        'interval_days',
        'interval_cycles',
        'last_maintenance',
        'next_maintenance',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'last_maintenance' => 'date',
        'next_maintenance' => 'date',
        'is_active' => 'boolean',
    ];

    public function equipment()
    {
        return $this->belongsTo(\App\Models\Equipment::class);
    }
}
