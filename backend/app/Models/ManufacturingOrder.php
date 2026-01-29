<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManufacturingOrder extends Model
{
    use \App\Traits\ScopeByOrganization, \App\Traits\Searchable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'product_id',
        'bom_id',
        'qty_to_produce',
        'qty_produced',
        'status',
        'priority',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'lot_id',
        'notes',
        'organization_id',
    ];

    protected $casts = [
        'qty_to_produce' => 'decimal:4',
        'qty_produced' => 'decimal:4',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class)->orderBy('sequence');
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(Consumption::class);
    }

    public function scraps(): HasMany
    {
        return $this->hasMany(Scrap::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(Serial::class);
    }

    // Generate unique MO name
    public static function generateName(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('MO/%s/%05d', $year, $count);
    }
}
