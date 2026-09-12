<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'owner_name',
        'username',
        'password',
        'phone',
        'store_image',
        'status',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
    ];

    public function addresses()
    {
        return $this->hasMany(MerchantAddress::class);
    }
}
