<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Serial extends Model
{
    use \App\Traits\ScopeByOrganization;

    protected $fillable = [
        'name',
        'product_id',
        'lot_id',
        'manufacturing_order_id',
        'status',
        'component_serials',
        'organization_id',
    ];

    protected $casts = [
        'component_serials' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }
}
