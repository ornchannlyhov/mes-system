<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    use \App\Traits\ScopeByOrganization;

    protected $fillable = [
        'organization_id',
        'work_order_id',
        'user_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'log_type',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'decimal:2',
    ];

    public function workOrder()
    {
        return $this->belongsTo(\App\Models\WorkOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

