<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Equipment extends Model
{
    use \App\Traits\ScopeByOrganization;
    use SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'name',
        'code',
        'work_center_id',
        'last_maintenance',
        'next_maintenance',
        'maintenance_interval_days',
        'status',
        'notes',
        'organization_id',
    ];

    protected $casts = [
        'last_maintenance' => 'datetime',
        'next_maintenance' => 'datetime',
    ];


    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    // Check if maintenance is due
    public function isMaintenanceDue(): bool
    {
        return $this->next_maintenance && $this->next_maintenance->lte(now());
    }

    // Schedule next maintenance after completing current
    public function scheduleNextMaintenance(): void
    {
        $this->update([
            'last_maintenance' => now(),
            'next_maintenance' => now()->addDays($this->maintenance_interval_days),
            'status' => 'operational',
        ]);
    }
}
