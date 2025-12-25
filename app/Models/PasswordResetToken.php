<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasswordResetToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'user_type',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * Check if token is expired
     */
    public function isExpired()
    {
        return Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * Generate a new token
     */
    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Create a new password reset token
     */
    public static function createToken($email, $userType = 'customer')
    {
        // Delete existing tokens for this email
        self::where('email', $email)->where('user_type', $userType)->delete();

        $token = self::generateToken();
        
        return self::create([
            'email' => $email,
            'token' => hash('sha256', $token),
            'user_type' => $userType,
            'expires_at' => Carbon::now()->addHours(1) // 1 hour expiry
        ]);
    }

    /**
     * Find valid token
     */
    public static function findValidToken($email, $token, $userType = 'customer')
    {
        $hashedToken = hash('sha256', $token);
        
        $resetToken = self::where('email', $email)
            ->where('token', $hashedToken)
            ->where('user_type', $userType)
            ->first();

        if (!$resetToken || $resetToken->isExpired()) {
            return null;
        }

        return $resetToken;
    }
}
