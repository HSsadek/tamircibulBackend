<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Admin login
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)
                ->where('role', User::ROLE_ADMIN)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid admin credentials'
                ], 401);
            }

            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin account is not active'
                ], 403);
            }

            $token = $user->createToken('admin_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Admin login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Admin login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate admin token
     */
    public function validateToken(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid admin token'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
                'data' => [
                    'user' => $user
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get admin dashboard statistics
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $stats = [
                'total_users' => User::count(),
                'total_customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
                'total_service_providers' => User::where('role', User::ROLE_SERVICE)->count(),
                'pending_service_providers' => ServiceProvider::where('status', ServiceProvider::STATUS_PENDING)->count(),
                'active_service_providers' => ServiceProvider::where('status', ServiceProvider::STATUS_ACTIVE)->count(),
                'total_service_requests' => ServiceRequest::count(),
                'pending_requests' => ServiceRequest::where('status', ServiceRequest::STATUS_PENDING)->count(),
                'completed_requests' => ServiceRequest::where('status', ServiceRequest::STATUS_COMPLETED)->count(),
                'total_reviews' => Review::count(),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
            ];

            // Recent activities
            $recentUsers = User::with('serviceProvider')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $recentRequests = ServiceRequest::with(['customer', 'serviceProvider'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_users' => $recentUsers,
                    'recent_requests' => $recentRequests,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users with pagination and filters
     */
    public function getUsers(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $query = User::with('serviceProvider');

            // Filter by role
            if ($request->role) {
                $query->where('role', $request->role);
            }

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Search by name, email, or phone
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                      ->orWhere('phone', 'like', '%' . $request->search . '%');
                });
            }

            $users = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending service provider applications
     */
    public function getPendingServiceProviders(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $pendingProviders = ServiceProvider::with('user')
                ->where('status', ServiceProvider::STATUS_PENDING)
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $pendingProviders->items(),
                'pagination' => [
                    'current_page' => $pendingProviders->currentPage(),
                    'last_page' => $pendingProviders->lastPage(),
                    'per_page' => $pendingProviders->perPage(),
                    'total' => $pendingProviders->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending service providers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve service provider (başvuruyu onayla)
     */
    public function approveServiceProvider(Request $request, $providerId)
    {
        try {
            $user = $request->user();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $serviceProvider = ServiceProvider::with('user')->find($providerId);
            
            if (!$serviceProvider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            // Update service provider status
            $serviceProvider->update([
                'status' => ServiceProvider::STATUS_ACTIVE,
                'is_verified' => true,
            ]);

            // Update user status
            $serviceProvider->user->update([
                'status' => User::STATUS_ACTIVE,
            ]);

            \Log::info('Service provider approved', [
                'provider_id' => $providerId,
                'company_name' => $serviceProvider->company_name,
                'admin_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service provider approved successfully',
                'data' => $serviceProvider
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to approve service provider', [
                'provider_id' => $providerId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve service provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject service provider (başvuruyu reddet)
     */
    public function rejectServiceProvider(Request $request, $providerId)
    {
        try {
            $user = $request->user();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            // Reason is optional for now
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceProvider = ServiceProvider::with('user')->find($providerId);
            
            if (!$serviceProvider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            // Update service provider status
            $serviceProvider->update([
                'status' => ServiceProvider::STATUS_SUSPENDED,
                'rejection_reason' => $request->reason ?? 'Başvuru admin tarafından reddedildi',
            ]);

            // Update user status
            $serviceProvider->user->update([
                'status' => User::STATUS_INACTIVE,
            ]);

            \Log::info('Service provider rejected', [
                'provider_id' => $providerId,
                'company_name' => $serviceProvider->company_name,
                'admin_id' => $user->id,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service provider rejected successfully',
                'data' => $serviceProvider
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to reject service provider', [
                'provider_id' => $providerId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject service provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, $userId)
    {
        try {
            $adminUser = $request->user();
            
            if (!$adminUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:active,inactive,suspended',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Don't allow admin to change their own status
            if ($user->id === $adminUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change your own status'
                ], 422);
            }

            $user->update(['status' => $request->status]);

            // If user is service provider, update service provider status too
            if ($user->isServiceProvider() && $user->serviceProvider) {
                $serviceProviderStatus = $request->status === User::STATUS_ACTIVE 
                    ? ServiceProvider::STATUS_ACTIVE 
                    : ServiceProvider::STATUS_SUSPENDED;
                
                $user->serviceProvider->update(['status' => $serviceProviderStatus]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => $user->load('serviceProvider')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all service requests for admin review
     */
    public function getServiceRequests(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            // Get pending service providers (başvurular)
            $query = ServiceProvider::with('user');

            // Filter by status - default to pending
            $status = $request->status ?? ServiceProvider::STATUS_PENDING;
            $query->where('status', $status);

            // Filter by service type
            if ($request->service_type) {
                $query->where('service_type', $request->service_type);
            }

            $providers = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            // Transform data for frontend
            $transformedData = $providers->getCollection()->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'company_name' => $provider->company_name,
                    'service_type' => $provider->service_type,
                    'description' => $provider->description,
                    'address' => $provider->address,
                    'city' => $provider->city,
                    'district' => $provider->district,
                    'phone' => $provider->user->phone ?? null,
                    'email' => $provider->user->email ?? null,
                    'working_hours' => $provider->working_hours,
                    'status' => $provider->status,
                    'created_at' => $provider->created_at,
                    'updated_at' => $provider->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $providers->currentPage(),
                    'last_page' => $providers->lastPage(),
                    'per_page' => $providers->perPage(),
                    'total' => $providers->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
