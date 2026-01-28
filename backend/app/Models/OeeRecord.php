<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OeeRecord extends Model
{
    use \App\Traits\ScopeByOrganization;

    protected $fillable = [
        'organization_id',
        'work_center_id',
        'record_date',
        'planned_time_minutes',
        'actual_runtime_minutes',
        'total_standard_minutes',
        'downtime_minutes',
        'ideal_cycle_time',
        'total_units_produced',
        'good_units',
        'defect_units',
        'availability_score',
        'performance_score',
        'quality_score',
        'oee_score',
    ];

    protected $casts = [
        'record_date' => 'date',
        'planned_time_minutes' => 'decimal:2',
        'actual_runtime_minutes' => 'decimal:2',
        'total_standard_minutes' => 'decimal:4',
        'downtime_minutes' => 'decimal:2',
        'ideal_cycle_time' => 'decimal:4',
        'availability_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'oee_score' => 'decimal:2',
    ];

    public function workCenter()
    {
        return $this->belongsTo(\App\Models\WorkCenter::class);
    }
}

