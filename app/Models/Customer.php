<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address',
        'city',
        'district',
        'latitude',
        'longitude',
        'preferences',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'preferences' => 'array',
    ];

    /**
     * Get the user that owns the customer profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get service requests made by this customer
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'customer_id', 'user_id');
    }

    /**
     * Get reviews written by this customer
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'customer_id', 'user_id');
    }
}
