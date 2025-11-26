<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'service_type',
        'description',
        'city',
        'district',
        'address',
        'phone',
        'latitude',
        'longitude',
        'working_hours',
        'logo',
        'rating',
        'total_reviews',
        'total_jobs',
        'is_verified',
        'verification_documents',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:2',
        'is_verified' => 'boolean',
        'verification_documents' => 'array',
    ];

    /**
     * Service types
     */
    const SERVICE_TYPES = [
        'plumbing' => 'Tesisatçı',
        'electrical' => 'Elektrikçi',
        'cleaning' => 'Temizlik',
        'appliance' => 'Beyaz Eşya',
        'computer' => 'Bilgisayar',
        'phone' => 'Telefon',
        'other' => 'Diğer',
    ];

    /**
     * Service provider statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Get the user that owns the service provider profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get service requests for this provider
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'service_provider_id', 'user_id');
    }

    /**
     * Get reviews for this service provider
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'service_provider_id', 'user_id');
    }

    /**
     * Get the service type name
     */
    public function getServiceTypeNameAttribute()
    {
        return self::SERVICE_TYPES[$this->service_type] ?? $this->service_type;
    }

    /**
     * Check if service provider is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * Check if service provider is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Update rating based on reviews
     */
    public function updateRating()
    {
        $avgRating = $this->reviews()->avg('rating');
        $totalReviews = $this->reviews()->count();
        
        $this->update([
            'rating' => $avgRating ? round($avgRating, 2) : 0,
            'total_reviews' => $totalReviews,
        ]);
    }
}
