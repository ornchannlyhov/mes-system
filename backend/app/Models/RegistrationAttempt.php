<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationAttempt extends Model
{
    protected $fillable = [
        'email',
        'name',
        'password',
        'otp',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
