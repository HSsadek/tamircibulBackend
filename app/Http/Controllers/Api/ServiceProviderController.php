<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
                'total_requests' => ServiceRequest::where('service_provider_id', $user->id)->count(),
                'pending_requests' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_PENDING)->count(),
                'completed_jobs' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_COMPLETED)->count(),
                'earnings' => 0, // This would be calculated based on completed jobs and pricing
                'rating' => $serviceProvider->rating ?? 0,
                'total_reviews' => $serviceProvider->total_reviews ?? 0,
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

            $query = ServiceRequest::with(['customer'])
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
                'latitude' => 'sometimes|numeric',
                'longitude' => 'sometimes|numeric',
                'working_hours' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceProvider = $user->serviceProvider;
            $serviceProvider->update($request->only([
                'company_name', 'description', 'city', 'district', 
                'address', 'latitude', 'longitude', 'working_hours'
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
                'total_requests' => ServiceRequest::where('service_provider_id', $user->id)->count(),
                'pending_requests' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_PENDING)->count(),
                'accepted_requests' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_ACCEPTED)->count(),
                'completed_jobs' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_COMPLETED)->count(),
                'cancelled_requests' => ServiceRequest::where('service_provider_id', $user->id)
                    ->where('status', ServiceRequest::STATUS_CANCELLED)->count(),
                'rating' => $serviceProvider->rating ?? 0,
                'total_reviews' => $serviceProvider->total_reviews ?? 0,
                'earnings' => 0, // This would be calculated based on completed jobs
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
}
