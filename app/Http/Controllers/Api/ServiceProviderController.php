<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ServiceProviderController extends Controller
{
    /**
     * Get service provider dashboard data
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $serviceProvider = $user->serviceProvider;
            
            // Get statistics
            $stats = [
                'totalRequests' => ServiceRequest::where('service_provider_id', $user->id)->count(),
                'pendingRequests' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_PENDING)->count(),
                'completedJobs' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_COMPLETED)->count(),
                'complaints' => 0, // This would be calculated from complaints table
                'rating' => $serviceProvider->rating ?? 0,
                'totalReviews' => $serviceProvider->total_reviews ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'service_provider' => $serviceProvider
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
     * Get service requests for service provider
     */
    public function getRequests(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $query = ServiceRequest::with(['customer', 'customer.customer'])
                ->where('service_provider_id', $user->id);

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            $requests = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $requests->items(),
                'pagination' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
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

    /**
     * Accept a service request
     */
    public function acceptRequest(Request $request, $requestId)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $serviceRequest = ServiceRequest::find($requestId);
            
            if (!$serviceRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            if ($serviceRequest->service_provider_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to accept this request'
                ], 403);
            }

            if (!$serviceRequest->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request cannot be accepted in current status'
                ], 422);
            }

            $serviceRequest->accept($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Service request accepted successfully',
                'data' => $serviceRequest->load(['customer', 'serviceProvider'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a service request
     */
    public function rejectRequest(Request $request, $requestId)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|min:10|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceRequest = ServiceRequest::find($requestId);
            
            if (!$serviceRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            if ($serviceRequest->service_provider_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reject this request'
                ], 403);
            }

            if (!$serviceRequest->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be rejected'
                ], 422);
            }

            // Update request status to rejected
            $serviceRequest->status = ServiceRequest::STATUS_REJECTED;
            $serviceRequest->cancellation_reason = $request->reason;
            $serviceRequest->cancelled_at = now();
            $serviceRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Service request rejected successfully',
                'data' => $serviceRequest->load(['customer', 'serviceProvider'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a service request
     */
    public function completeRequest(Request $request, $requestId)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $serviceRequest = ServiceRequest::find($requestId);
            
            if (!$serviceRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            if ($serviceRequest->service_provider_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to complete this request'
                ], 403);
            }

            if (!$serviceRequest->isAccepted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request must be accepted before completion'
                ], 422);
            }

            $serviceRequest->complete();

            return response()->json([
                'success' => true,
                'message' => 'Service request completed successfully',
                'data' => $serviceRequest->load(['customer', 'serviceProvider'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update service provider profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'company_name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'city' => 'sometimes|string|max:100',
                'district' => 'sometimes|string|max:100',
                'address' => 'sometimes|string',
                'phone' => 'sometimes|string|max:20',
                'latitude' => 'sometimes|numeric',
                'longitude' => 'sometimes|numeric',
                'working_hours' => 'sometimes|string',
                'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceProvider = $user->serviceProvider;
            
            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($serviceProvider->logo) {
                    Storage::disk('public')->delete($serviceProvider->logo);
                }
                
                $logoPath = $request->file('logo')->store('logos', 'public');
                $serviceProvider->logo = $logoPath;
            }
            
            // Update other fields
            $serviceProvider->update($request->only([
                'company_name', 'description', 'city', 'district', 
                'address', 'phone', 'latitude', 'longitude', 'working_hours'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $serviceProvider
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service provider profile
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $serviceProvider = $user->serviceProvider;

            return response()->json([
                'success' => true,
                'data' => $serviceProvider
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request)
    {
        try {
            $user = $request->user();
            
            \Log::info('Upload logo request', [
                'user_id' => $user->id,
                'has_file' => $request->hasFile('logo')
            ]);
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceProvider = $user->serviceProvider;
            
            if (!$serviceProvider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider profile not found'
                ], 404);
            }
            
            \Log::info('Service provider found', [
                'sp_id' => $serviceProvider->id,
                'current_logo' => $serviceProvider->logo
            ]);
            
            // Delete old logo if exists
            if ($serviceProvider->logo) {
                Storage::disk('public')->delete($serviceProvider->logo);
            }
            
            // Store new logo
            $logoPath = $request->file('logo')->store('logos', 'public');
            
            \Log::info('Logo stored', ['path' => $logoPath]);
            
            // Update using update method to ensure it's saved
            $serviceProvider->update(['logo' => $logoPath]);
            
            // Refresh to get updated data
            $serviceProvider->refresh();
            
            \Log::info('Logo updated in database', [
                'sp_id' => $serviceProvider->id,
                'new_logo' => $serviceProvider->logo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully',
                'data' => [
                    'logo' => $logoPath,
                    'logo_url' => Storage::url($logoPath)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Logo upload error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete logo
     */
    public function deleteLogo(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $serviceProvider = $user->serviceProvider;
            
            if ($serviceProvider->logo) {
                Storage::disk('public')->delete($serviceProvider->logo);
                $serviceProvider->logo = null;
                $serviceProvider->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logo deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service provider statistics
     */
    public function getStats(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $serviceProvider = $user->serviceProvider;
            
            $stats = [
                'totalRequests' => ServiceRequest::where('service_provider_id', $user->id)->count(),
                'pendingRequests' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_PENDING)->count(),
                'acceptedRequests' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_ACCEPTED)->count(),
                'completedJobs' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_COMPLETED)->count(),
                'cancelledRequests' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_CANCELLED)->count(),
                'complaints' => 0, // This would be calculated from complaints table
                'rating' => $serviceProvider->rating ?? 0,
                'totalReviews' => $serviceProvider->total_reviews ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a service request (only rejected ones)
     */
    public function deleteRequest(Request $request, $requestId)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            $serviceRequest = ServiceRequest::find($requestId);
            
            if (!$serviceRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            if ($serviceRequest->service_provider_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this request'
                ], 403);
            }

            // Only allow deletion of rejected or cancelled requests
            if ($serviceRequest->status !== ServiceRequest::STATUS_REJECTED && 
                $serviceRequest->status !== ServiceRequest::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only rejected or cancelled requests can be deleted'
                ], 422);
            }

            $serviceRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service request deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notifications for service provider
     */
    public function getNotifications(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            // Generate sample notifications based on recent requests
            $recentRequests = ServiceRequest::where('service_provider_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $notifications = [];
            
            foreach ($recentRequests as $req) {
                $notification = [
                    'id' => $req->id,
                    'type' => 'new_request',
                    'title' => 'Yeni Talep',
                    'message' => $req->customer_name . ' tarafından yeni bir talep oluşturuldu: ' . $req->service_type,
                    'created_at' => $req->created_at,
                    'read' => $req->status !== ServiceRequest::STATUS_PENDING
                ];
                
                if ($req->status === ServiceRequest::STATUS_ACCEPTED) {
                    $notification['type'] = 'request_accepted';
                    $notification['title'] = 'Talep Kabul Edildi';
                    $notification['message'] = 'Talebi kabul ettiniz. Müşteri ile iletişime geçebilirsiniz.';
                } elseif ($req->status === ServiceRequest::STATUS_COMPLETED) {
                    $notification['type'] = 'request_completed';
                    $notification['title'] = 'İş Tamamlandı';
                    $notification['message'] = $req->customer_name . ' için iş başarıyla tamamlandı.';
                }
                
                $notifications[] = $notification;
            }

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Request $request, $notificationId)
    {
        try {
            $user = $request->user();
            
            if (!$user->isServiceProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Service provider role required.'
                ], 403);
            }

            // In a real implementation, you would update the notification in the database
            // For now, we'll just return success
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
