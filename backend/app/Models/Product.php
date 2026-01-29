<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use \App\Traits\ScopeByOrganization;
    use \App\Traits\Searchable;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'image_url',
        'type',
        'tracking',
        'uom',
        'cost',
        'is_active',
        'version',
        'organization_id',
        'product_template_id',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function template(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class, 'product_template_id');
    }

    public function variantAttributes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attributes', 'product_id', 'attribute_value_id');
    }

    public function boms(): HasMany
    {
        return $this->hasMany(Bom::class);
    }

    public function bomLines(): HasMany
    {
        return $this->hasMany(BomLine::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(Serial::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function manufacturingOrders(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(Consumption::class);
    }

    public function scraps(): HasMany
    {
        return $this->hasMany(Scrap::class);
    }
}
