<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_type',
        'license_plate',
        'current_latitude',
        'current_longitude',
        'is_available',
        'is_verified',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
