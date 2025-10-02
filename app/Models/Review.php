<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'customer_id',
        'service_provider_id',
        'rating',
        'comment',
        'images',
        'is_verified',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'is_verified' => 'boolean',
    ];

    /**
     * Get the service request this review belongs to
     */
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Get the customer who wrote the review
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the service provider being reviewed
     */
    public function serviceProvider()
    {
        return $this->belongsTo(User::class, 'service_provider_id');
    }
}
