<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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
                    'service_type' => $request->service_type,
                    'description' => $request->description,
                    'status' => ServiceProvider::STATUS_PENDING,
                ]);
            } else {
                Customer::create([
                    'user_id' => $user->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'requires_approval' => $request->role === 'service'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
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
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if user is active
            if (!$user->isActive()) {
                $message = $user->status === User::STATUS_PENDING 
                    ? 'Your account is pending approval' 
                    : 'Your account is not active';
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'status' => $user->status
                ], 403);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Load related data
            $userData = $user->toArray();
            if ($user->isServiceProvider()) {
                $user->load('serviceProvider');
                $userData['service_provider'] = $user->serviceProvider;
            } elseif ($user->isCustomer()) {
                $user->load('customer');
                $userData['customer'] = $user->customer;
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $userData,
                    'token' => $token,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
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

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
