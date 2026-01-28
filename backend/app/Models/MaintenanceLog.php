<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use \App\Traits\ScopeByOrganization;

    protected $fillable = [
        'equipment_id',
        'type',
        'description',
        'actions_taken',
        'cost',
        'performed_by',
        'performed_at',
        'organization_id',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'performed_at' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
