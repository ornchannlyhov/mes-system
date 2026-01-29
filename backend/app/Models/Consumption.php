<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumption extends Model
{
    use \App\Traits\ScopeByOrganization, \App\Traits\Searchable;

    protected $fillable = [
        'organization_id',
        'manufacturing_order_id',
        'product_id',
        'lot_id',
        'location_id',
        'qty_planned',
        'qty_consumed',
        'cost_impact',
    ];

    protected $casts = [
        'qty_planned' => 'decimal:4',
        'qty_consumed' => 'decimal:4',
        'cost_impact' => 'decimal:2',
    ];

    protected $appends = [
        'variance',
    ];

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getVarianceAttribute(): float
    {
        return $this->qty_consumed - $this->qty_planned;
    }
}
