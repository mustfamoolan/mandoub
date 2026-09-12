<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'username',
        'password',
        'avatar',
        'rating',
        'status',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'rating' => 'float',
    ];

    /**
     * Get the orders accepted/assigned to the driver.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }
}
