<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'merchant_id',
        'merchant_address_id',
        'merchant_address',
        'merchant_latitude',
        'merchant_longitude',
        'customer_address',
        'customer_latitude',
        'customer_longitude',
        'customer_phone',
        'order_description',
        'total_amount',
        'delivery_fee',
        'net_payout',
        'status',
        'driver_id',
    ];

    protected $appends = [
        'status_arabic',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function merchantAddress()
    {
        return $this->belongsTo(MerchantAddress::class, 'merchant_address_id');
    }

    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'قيد الانتظار',
            'driver_assigned' => 'المندوب قبل الطلب',
            'in_delivery' => 'قيد التوصيل',
            'delivered' => 'تم التسليم',
            'returned' => 'مسترجع',
            'cancelled' => 'ملغي',
            default => 'قيد الانتظار',
        };
    }
}
