<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Searchable;

class Organization extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'owner_id',
        'is_active',
        'activated_at',
        'activated_by',
        'user_limit',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
