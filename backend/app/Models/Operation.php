<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operation extends Model
{
    use \App\Traits\ScopeByOrganization;

    protected $fillable = [
        'organization_id',
        'bom_id',
        'work_center_id',
        'name',
        'sequence',
        'duration_minutes',
        'needs_quality_check',
        'instruction_file_url',
    ];

    protected $casts = [
        'duration_minutes' => 'decimal:2',
        'needs_quality_check' => 'boolean',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

}
