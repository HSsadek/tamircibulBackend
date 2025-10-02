<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Get all active service providers
     */
    public function index(Request $request)
    {
        try {
            // Debug: Check total count
            $totalCount = ServiceProvider::count();
            $activeCount = ServiceProvider::where('status', ServiceProvider::STATUS_ACTIVE)->count();
            $verifiedCount = ServiceProvider::where('is_verified', true)->count();
            
            \Log::info("ServiceProvider counts - Total: $totalCount, Active: $activeCount, Verified: $verifiedCount");

            $query = ServiceProvider::with('user')
                ->where('status', ServiceProvider::STATUS_ACTIVE);

            // Filter by service type
            if ($request->service_type && $request->service_type !== 'all') {
                $query->where('service_type', $request->service_type);
            }

            // Filter by city
            if ($request->city) {
                $query->where('city', $request->city);
            }

            // Filter by district
            if ($request->district) {
                $query->where('district', $request->district);
            }

            // Search by name or description
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('company_name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhereHas('user', function($userQuery) use ($request) {
                          $userQuery->where('name', 'like', '%' . $request->search . '%');
                      });
                });
            }

            // Location-based filtering for better performance
            if ($request->has('lat') && $request->has('lng') && $request->has('radius')) {
                $lat = $request->lat;
                $lng = $request->lng;
                $radius = $request->radius ?? 50; // Default 50km radius
                
                // Use Haversine formula for distance calculation
                $query->selectRaw("
                    *, 
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                ", [$lat, $lng, $lat])
                ->having('distance', '<=', $radius)
                ->orderBy('distance');
            } else {
                // Sort by rating or distance (if coordinates provided)
                if ($request->sort === 'rating') {
                    $query->orderBy('rating', 'desc');
                } elseif ($request->sort === 'reviews') {
                    $query->orderBy('total_reviews', 'desc');
                } else {
                    $query->orderBy('created_at', 'desc');
                }
            }

            // Pagination for better performance
            $perPage = $request->per_page ?? 20; // Default 20 items per page
            $services = $query->paginate($perPage);

            // Transform data for frontend
            $transformedServices = $services->getCollection()->map(function ($provider) {
                // Service type icons
                $icons = [
                    'plumbing' => '🚰',
                    'electrical' => '⚡',
                    'cleaning' => '🧹',
                    'appliance' => '🔌',
                    'computer' => '💻',
                    'phone' => '📱',
                    'other' => '🛠️'
                ];

                // Service type names in Turkish
                $typeNames = [
                    'plumbing' => 'Tesisatçı',
                    'electrical' => 'Elektrikçi',
                    'cleaning' => 'Temizlik',
                    'appliance' => 'Beyaz Eşya',
                    'computer' => 'Bilgisayar',
                    'phone' => 'Telefon',
                    'other' => 'Diğer'
                ];

                $serviceData = [
                    'id' => $provider->id,
                    'name' => $provider->company_name ?: $provider->user->name,
                    'description' => $provider->description,
                    'service_type' => $provider->service_type,
                    'service_type_name' => $typeNames[$provider->service_type] ?? 'Diğer',
                    'image' => $icons[$provider->service_type] ?? '🛠️',
                    'rating' => $provider->rating,
                    'reviews' => $provider->total_reviews,
                    'total_reviews' => $provider->total_reviews,
                    'city' => $provider->city,
                    'district' => $provider->district,
                    'price' => '₺' . rand(50, 300) . '-' . rand(300, 800), // Mock price range
                    'working_hours' => $provider->working_hours,
                    'latitude' => $provider->latitude,
                    'longitude' => $provider->longitude,
                    'user' => [
                        'name' => $provider->user->name,
                        'phone' => $provider->user->phone,
                    ]
                ];

                // Add calculated distance if available
                if (isset($provider->distance)) {
                    $serviceData['distance'] = round($provider->distance, 1) . ' km';
                    $serviceData['distanceKm'] = round($provider->distance, 1);
                } else {
                    $serviceData['distance'] = rand(1, 10) . ' km'; // Mock distance
                }

                return $serviceData;
            });

            $response = [
                'success' => true,
                'data' => $transformedServices,
                'pagination' => [
                    'current_page' => $services->currentPage(),
                    'last_page' => $services->lastPage(),
                    'per_page' => $services->perPage(),
                    'total' => $services->total(),
                    'from' => $services->firstItem(),
                    'to' => $services->lastItem(),
                ],
                'debug' => [
                    'query_count' => $services->count(),
                    'transformed_count' => $transformedServices->count(),
                    'request_params' => $request->all()
                ]
            ];
            
            \Log::info('API Response', ['data_count' => $transformedServices->count()]);
            
            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service provider details
     */
    public function show($id)
    {
        try {
            $service = ServiceProvider::with(['user', 'reviews.customer'])
                ->where('id', $id)
                ->where('status', ServiceProvider::STATUS_ACTIVE)
                ->first();

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a service request
     */
    public function createRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_provider_id' => 'nullable|exists:users,id',
                'service_type' => 'required|string',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'address' => 'required|string',
                'city' => 'required|string',
                'district' => 'required|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'preferred_date' => 'nullable|date|after:today',
                'preferred_time' => 'nullable|string',
                'budget_min' => 'nullable|numeric|min:0',
                'budget_max' => 'nullable|numeric|min:0|gte:budget_min',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'images' => 'nullable|array',
                'images.*' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceRequest = ServiceRequest::create([
                'customer_id' => $request->user()->id,
                'service_provider_id' => $request->service_provider_id,
                'service_type' => $request->service_type,
                'title' => $request->title,
                'description' => $request->description,
                'address' => $request->address,
                'city' => $request->city,
                'district' => $request->district,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'preferred_date' => $request->preferred_date,
                'preferred_time' => $request->preferred_time,
                'budget_min' => $request->budget_min,
                'budget_max' => $request->budget_max,
                'priority' => $request->priority ?? ServiceRequest::PRIORITY_MEDIUM,
                'images' => $request->images,
                'status' => ServiceRequest::STATUS_PENDING,
            ]);

            $serviceRequest->load(['customer', 'serviceProvider']);

            return response()->json([
                'success' => true,
                'message' => 'Service request created successfully',
                'data' => $serviceRequest
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service requests for customer
     */
    public function getCustomerRequests(Request $request)
    {
        try {
            $requests = ServiceRequest::with(['serviceProvider.user'])
                ->where('customer_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
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
     * Get service types
     */
    public function getServiceTypes()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => ServiceProvider::SERVICE_TYPES
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service types',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
