<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_fee',
        'about_us',
        'terms_and_conditions',
        'privacy_policy',
        'support_phone',
        'whatsapp_phone',
    ];

    protected $casts = [
        'delivery_fee' => 'float',
    ];
}
