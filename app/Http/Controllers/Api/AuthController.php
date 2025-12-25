<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Models\Customer;
use App\Models\PasswordResetToken;
use App\Mail\PasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Translate validation messages to Turkish
     */
    private function translateValidationMessage($field, $message)
    {
        $translations = [
            'required' => 'zorunludur',
            'email' => 'geçerli bir e-posta adresi olmalıdır',
            'unique' => 'zaten kullanılıyor',
            'min' => 'en az :min karakter olmalıdır',
            'max' => 'en fazla :max karakter olmalıdır',
            'confirmed' => 'eşleşmiyor',
            'string' => 'metin olmalıdır',
            'in' => 'geçersiz değer',
        ];
        
        $fieldNames = [
            'name' => 'Ad Soyad',
            'email' => 'E-posta',
            'phone' => 'Telefon',
            'password' => 'Şifre',
            'password_confirmation' => 'Şifre Tekrarı',
            'role' => 'Rol',
            'company_name' => 'Firma Adı',
            'service_type' => 'Hizmet Türü',
            'description' => 'Açıklama',
        ];
        
        $fieldName = $fieldNames[$field] ?? $field;
        
        // Check for specific validation rules
        if (strpos($message, 'required') !== false) {
            return "$fieldName alanı zorunludur";
        }
        if (strpos($message, 'email') !== false) {
            return "$fieldName geçerli bir e-posta adresi olmalıdır";
        }
        if (strpos($message, 'unique') !== false) {
            return "$fieldName zaten kullanılıyor";
        }
        if (strpos($message, 'min') !== false) {
            preg_match('/\d+/', $message, $matches);
            $min = $matches[0] ?? '6';
            return "$fieldName en az $min karakter olmalıdır";
        }
        if (strpos($message, 'confirmed') !== false) {
            return "$fieldName eşleşmiyor";
        }
        if (strpos($message, 'required_if') !== false) {
            return "$fieldName alanı zorunludur";
        }
        
        return $message;
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'phone' => 'nullable|string|unique:users,phone',
                'password' => 'required|string|min:6|confirmed',
                'role' => 'required|in:customer,service',
                'company_name' => 'required_if:role,service|string|max:255',
                'service_type' => 'required_if:role,service|string',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ensure at least email or phone is provided
            if (!$request->email && !$request->phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either email or phone is required'
                ], 422);
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => $request->role === 'service' ? User::STATUS_PENDING : User::STATUS_ACTIVE,
            ]);

            // Create role-specific profile
            if ($request->role === 'service') {
                ServiceProvider::create([
                    'user_id' => $user->id,
                    'company_name' => $request->company_name,
                    'service_type' => $request->service_type,
                    'description' => $request->description,
                    'phone' => $request->phone, // Store phone in service provider too
                    'status' => ServiceProvider::STATUS_PENDING,
                ]);
            } else {
                Customer::create([
                    'user_id' => $user->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Kayıt başarıyla tamamlandı',
                'data' => [
                    'user' => $user,
                    'requires_approval' => $request->role === 'service'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kayıt işlemi başarısız oldu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'nullable|email',
                'phone' => 'nullable|string',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                // Türkçe hata mesajları
                $errors = [];
                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $translatedMessage = $this->translateValidationMessage($field, $message);
                        $errors[$field][] = $translatedMessage;
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Girdiğiniz bilgilerde hata var',
                    'errors' => $errors
                ], 422);
            }

            // Ensure at least email or phone is provided
            if (!$request->email && !$request->phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-posta veya telefon numarası gereklidir',
                    'errors' => ['identifier' => ['E-posta veya telefon numarası girmelisiniz']]
                ], 422);
            }

            // Find user by email or phone
            $user = null;
            if ($request->email) {
                $user = User::where('email', $request->email)->first();
            } elseif ($request->phone) {
                $user = User::where('phone', $request->phone)->first();
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-posta veya şifre hatalı',
                    'errors' => ['credentials' => ['Girdiğiniz bilgiler hatalı. Lütfen kontrol edin.']]
                ], 401);
            }

            // Check if user is active
            if (!$user->isActive()) {
                $message = $user->status === User::STATUS_PENDING 
                    ? 'Hesabınız onay bekliyor' 
                    : 'Hesabınız aktif değil';
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'status' => $user->status
                ], 403);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Load related data
            if ($user->isServiceProvider()) {
                $user->load('serviceProvider');
            } elseif ($user->isCustomer()) {
                $user->load('customer');
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Giriş işlemi başarısız oldu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            // Load related data
            if ($user->isServiceProvider()) {
                $user->load('serviceProvider');
            } elseif ($user->isCustomer()) {
                $user->load('customer');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
                'current_password' => 'required_with:password',
                'password' => 'sometimes|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify current password if changing password
            if ($request->password && !Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }

            // Update user data
            $updateData = $request->only(['name', 'email', 'phone']);
            if ($request->password) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Update customer-specific data if user is a customer
            if ($user->isCustomer()) {
                // Eğer customer kaydı yoksa oluştur
                if (!$user->customer) {
                    Customer::create([
                        'user_id' => $user->id,
                    ]);
                    $user->load('customer');
                }
                
                $customerData = [];
                
                if ($request->has('address')) {
                    $customerData['address'] = $request->address;
                }
                if ($request->has('city')) {
                    $customerData['city'] = $request->city;
                }
                if ($request->has('district')) {
                    $customerData['district'] = $request->district;
                }
                if ($request->has('latitude')) {
                    $customerData['latitude'] = $request->latitude;
                }
                if ($request->has('longitude')) {
                    $customerData['longitude'] = $request->longitude;
                }
                if ($request->has('profile_image')) {
                    $customerData['profile_image'] = $request->profile_image;
                }
                if ($request->has('email_notifications')) {
                    $customerData['email_notifications'] = $request->email_notifications;
                }
                if ($request->has('sms_notifications')) {
                    $customerData['sms_notifications'] = $request->sms_notifications;
                }
                if ($request->has('push_notifications')) {
                    $customerData['push_notifications'] = $request->push_notifications;
                }
                
                if (!empty($customerData)) {
                    $user->customer->update($customerData);
                }
            }

            // Reload user with relationships
            $user->load('customer');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Profile update error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isCustomer() || !$user->customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can update notification preferences'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'email_notifications' => 'sometimes|boolean',
                'sms_notifications' => 'sometimes|boolean',
                'push_notifications' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];
            if ($request->has('email_notifications')) {
                $updateData['email_notifications'] = $request->email_notifications;
            }
            if ($request->has('sms_notifications')) {
                $updateData['sms_notifications'] = $request->sms_notifications;
            }
            if ($request->has('push_notifications')) {
                $updateData['push_notifications'] = $request->push_notifications;
            }

            $user->customer->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'data' => [
                    'preferences' => $user->customer->only(['email_notifications', 'sms_notifications', 'push_notifications'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'user_type' => 'sometimes|in:customer,service_provider'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-posta adresi geçerli değil',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            $userType = $request->user_type ?? 'customer';

            // Find user by email
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu e-posta adresi ile kayıtlı kullanıcı bulunamadı'
                ], 404);
            }

            // Check user type matches
            if ($userType === 'service_provider' && !$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu e-posta adresi servis sağlayıcı hesabı ile eşleşmiyor'
                ], 404);
            }

            if ($userType === 'customer' && !$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu e-posta adresi müşteri hesabı ile eşleşmiyor'
                ], 404);
            }

            // Delete existing tokens for this email
            PasswordResetToken::where('email', $email)->where('user_type', $userType)->delete();

            // Generate plain token for URL
            $plainToken = bin2hex(random_bytes(32));
            
            // Store hashed version in database
            PasswordResetToken::create([
                'email' => $email,
                'token' => hash('sha256', $plainToken),
                'user_type' => $userType,
                'expires_at' => now()->addHours(1)
            ]);

            // Create reset URL (use frontend URL)
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $resetUrl = $frontendUrl . "/#/reset-password?token={$plainToken}&email={$email}&type={$userType}";

            // Send email
            Mail::to($email)->send(new PasswordResetMail($resetUrl, $user->name));

            return response()->json([
                'success' => true,
                'message' => 'Şifre sıfırlama linki e-posta adresinize gönderildi'
            ]);

        } catch (\Exception $e) {
            \Log::error('Password reset email error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'E-posta gönderilirken bir hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
                'user_type' => 'sometimes|in:customer,service_provider'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Girilen bilgiler geçersiz',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            $token = $request->token;
            $userType = $request->user_type ?? 'customer';

            // Find and validate token
            $resetToken = PasswordResetToken::findValidToken($email, $token, $userType);

            if (!$resetToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Şifre sıfırlama linki geçersiz veya süresi dolmuş'
                ], 400);
            }

            // Find user
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı'
                ], 404);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Delete used token
            $resetToken->delete();

            // Delete all other tokens for this user
            PasswordResetToken::where('email', $email)->where('user_type', $userType)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Şifreniz başarıyla güncellendi'
            ]);

        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Şifre sıfırlama işlemi başarısız oldu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify password reset token
     */
    public function verifyResetToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string',
                'user_type' => 'sometimes|in:customer,service_provider'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz parametreler',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            $token = $request->token;
            $userType = $request->user_type ?? 'customer';

            // Find and validate token
            $resetToken = PasswordResetToken::findValidToken($email, $token, $userType);

            if (!$resetToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Şifre sıfırlama linki geçersiz veya süresi dolmuş'
                ], 400);
            }

            // Find user
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token geçerli',
                'data' => [
                    'user_name' => $user->name,
                    'email' => $email,
                    'expires_at' => $resetToken->expires_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token doğrulama hatası',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
