<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    use \App\Traits\ScopeByOrganization;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'type',
        'qty_produced',
        'is_active',
        'organization_id',
    ];

    protected $casts = [
        'qty_produced' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BomLine::class)->orderBy('sequence');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class)->orderBy('sequence');
    }

    public function manufacturingOrders(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class);
    }
}
