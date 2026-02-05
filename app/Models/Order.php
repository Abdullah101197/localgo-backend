<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'shop_id',
        'rider_id',
        'status',
        'total_amount',
        'payment_method',
        'payment_status',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
