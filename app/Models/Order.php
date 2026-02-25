<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'shop_id',
        'rider_id',
        'status',
        'total_amount',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'payment_method',
        'payment_status',
        'rider_latitude',
        'rider_longitude',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function messages()
    {
        return $this->hasMany(OrderMessage::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
