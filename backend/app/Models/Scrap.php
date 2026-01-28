<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scrap extends Model
{
    use \App\Traits\ScopeByOrganization;

    protected $fillable = [
        'manufacturing_order_id',
        'work_order_id',
        'product_id',
        'lot_id',
        'location_id',
        'quantity',
        'reason',
        'reported_by',
        'organization_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
