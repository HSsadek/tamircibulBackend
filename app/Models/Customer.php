<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_image',
        'address',
        'city',
        'district',
        'latitude',
        'longitude',
        'preferences',
        'email_notifications',
        'sms_notifications',
        'push_notifications',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'preferences' => 'array',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'push_notifications' => 'boolean',
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
