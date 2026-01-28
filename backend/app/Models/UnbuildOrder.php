<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnbuildOrder extends Model
{
    use \App\Traits\ScopeByOrganization;

    protected $fillable = [
        'name',
        'product_id',
        'bom_id',
        'quantity',
        'status',
        'lot_id',
        'serial_number_id',
        'manufacturing_order_id',
        'reason',
        'created_by',
        'organization_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function bom()
    {
        return $this->belongsTo(\App\Models\Bom::class);
    }
}

