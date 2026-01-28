<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use \App\Traits\ScopeByOrganization;

    protected $fillable = [
        'product_id',
        'location_id',
        'lot_id',
        'quantity',
        'reserved_qty',
        'organization_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'reserved_qty' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function availableQty(): float
    {
        return $this->quantity - $this->reserved_qty;
    }
}
