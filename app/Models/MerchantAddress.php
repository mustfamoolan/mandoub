<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'address_name',
        'address_details',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
