<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use \App\Traits\ScopeByOrganization;
    use SoftDeletes;

    protected $fillable = [
        'manufacturing_order_id',
        'operation_id',
        'work_center_id',
        'assigned_to',
        'sequence',
        'status',
        'duration_expected',
        'duration_actual',
        'quantity_produced',
        'started_at',
        'actual_start',
        'finished_at',
        'notes',
        'qa_status',
        'qa_comments',
        'qa_by',
        'qa_at',
        'organization_id',
    ];

    protected $casts = [
        'duration_expected' => 'decimal:2',
        'duration_actual' => 'decimal:2',
        'quantity_produced' => 'decimal:4',
        'started_at' => 'datetime',
        'actual_start' => 'datetime',
        'finished_at' => 'datetime',
        'qa_at' => 'datetime',
    ];

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function qaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qa_by');
    }

    public function scraps(): HasMany
    {
        return $this->hasMany(Scrap::class);
    }

    // Start timer
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    // Stop timer
    public function finish(): void
    {
        $duration = $this->started_at->diffInMinutes(now());
        $this->update([
            'status' => 'done',
        ]);
    }

}
