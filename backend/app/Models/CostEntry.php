<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostEntry extends Model
{
    use \App\Traits\ScopeByOrganization, \App\Traits\Searchable;

    protected $fillable = [
        'organization_id',
        'manufacturing_order_id',
        'work_order_id',
        'consumption_id', // Linked source
        'scrap_id',       // Linked source
        'cost_type',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    public function consumption()
    {
        return $this->belongsTo(Consumption::class);
    }

    public function scrap()
    {
        return $this->belongsTo(Scrap::class);
    }

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function manufacturingOrder()
    {
        return $this->belongsTo(\App\Models\ManufacturingOrder::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(\App\Models\WorkOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}

