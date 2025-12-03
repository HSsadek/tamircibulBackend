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

            // Search by name, description, or service type
            if ($request->search) {
                $searchTerm = $request->search;
                
                // Service type mapping for Turkish search
                $serviceTypeMapping = [
                    'tesisatçı' => 'plumbing',
                    'tesisatci' => 'plumbing',
                    'tesisat' => 'plumbing',
                    'elektrikçi' => 'electrical',
                    'elektrikci' => 'electrical',
                    'elektrik' => 'electrical',
                    'temizlik' => 'cleaning',
                    'temizlikçi' => 'cleaning',
                    'temizlikci' => 'cleaning',
                    'beyaz eşya' => 'appliance',
                    'beyaz esya' => 'appliance',
                    'beyazeşya' => 'appliance',
                    'beyazesya' => 'appliance',
                    'bilgisayar' => 'computer',
                    'bilgisayarci' => 'computer',
                    'bilgisayarcı' => 'computer',
                    'telefon' => 'phone',
                    'telefoncu' => 'phone',
                    'telefoncı' => 'phone',
                    'tamir' => 'other',
                    'tamirci' => 'other',
                    'tamircı' => 'other',
                ];
                
                $query->where(function($q) use ($searchTerm, $serviceTypeMapping) {
                    $q->where('company_name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                          $userQuery->where('name', 'like', '%' . $searchTerm . '%');
                      });
                    
                    // Check if search term matches a service type
                    $lowerSearchTerm = strtolower(trim($searchTerm));
                    if (isset($serviceTypeMapping[$lowerSearchTerm])) {
                        $q->orWhere('service_type', $serviceTypeMapping[$lowerSearchTerm]);
                    }
                    
                    // Also check direct service type match
                    $q->orWhere('service_type', 'like', '%' . $searchTerm . '%');
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

                // Gerçek değerlendirme sayısını hesapla
                $realReviewCount = \App\Models\ServiceRequest::where('service_provider_id', $provider->user_id)
                    ->whereNotNull('rating')
                    ->where('rating', '>', 0)
                    ->count();
                
                // Gerçek ortalama rating'i hesapla
                $realAvgRating = \App\Models\ServiceRequest::where('service_provider_id', $provider->user_id)
                    ->whereNotNull('rating')
                    ->where('rating', '>', 0)
                    ->avg('rating');

                $serviceData = [
                    'id' => $provider->id,
                    'name' => $provider->company_name ?: $provider->user->name,
                    'company_name' => $provider->company_name,
                    'description' => $provider->description,
                    'service_type' => $provider->service_type,
                    'service_type_name' => $typeNames[$provider->service_type] ?? 'Diğer',
                    'image' => $icons[$provider->service_type] ?? '🛠️',
                    'logo' => $provider->logo ? asset('storage/' . $provider->logo) : null,
                    'rating' => $realAvgRating ? round($realAvgRating, 1) : ($provider->rating ?: 5.0),
                    'reviews' => $realReviewCount,
                    'total_reviews' => $realReviewCount,
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

            // Get reviews for this service provider
            $reviews = ServiceRequest::with(['customer', 'customer.customer'])
                ->where('service_provider_id', $service->user_id)
                ->whereNotNull('rating')
                ->where('rating', '>', 0)
                ->orderBy('rated_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'rating' => $request->rating,
                        'comment' => $request->rating_comment,
                        'title' => $request->title,
                        'service_type' => $request->service_type,
                        'rated_at' => $request->rated_at,
                        'customer' => [
                            'name' => $request->customer->name ?? 'Müşteri',
                            'profile_image' => $request->customer->customer->profile_image ?? null,
                        ],
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $service,
                    'reviews' => $reviews,
                    'average_rating' => $reviews->avg('rating'),
                    'total_reviews' => $reviews->count(),
                ]
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
                'service_provider_id' => 'nullable|exists:service_providers,id',
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

            // Convert ServiceProvider ID to User ID
            $userProviderId = null;
            if ($request->service_provider_id) {
                $serviceProvider = ServiceProvider::find($request->service_provider_id);
                if ($serviceProvider) {
                    $userProviderId = $serviceProvider->user_id;
                }
            }

            $serviceRequest = ServiceRequest::create([
                'customer_id' => $request->user()->id,
                'service_provider_id' => $userProviderId,
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
            \Log::info('Fetching requests for customer: ' . $request->user()->id);
            
            $requests = ServiceRequest::with(['customer', 'serviceProvider.serviceProvider'])
                ->where('customer_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('Found ' . $requests->count() . ' requests');

            // Transform data for frontend
            $transformedRequests = $requests->map(function ($request) {
                $serviceProviderData = null;
                
                if ($request->serviceProvider) {
                    $serviceProviderData = [
                        'id' => $request->serviceProvider->id,
                        'name' => $request->serviceProvider->name,
                        'email' => $request->serviceProvider->email,
                        'phone' => $request->serviceProvider->phone,
                    ];
                    
                    // Add logo if service provider profile exists
                    if ($request->serviceProvider->serviceProvider) {
                        $serviceProviderData['logo'] = $request->serviceProvider->serviceProvider->logo 
                            ? asset('storage/' . $request->serviceProvider->serviceProvider->logo) 
                            : null;
                        $serviceProviderData['company_name'] = $request->serviceProvider->serviceProvider->company_name;
                    }
                }
                
                // Service type names in Turkish
                $serviceTypeNames = [
                    'plumbing' => 'Tesisatçı',
                    'electrical' => 'Elektrikçi',
                    'cleaning' => 'Temizlik',
                    'appliance' => 'Beyaz Eşya',
                    'computer' => 'Bilgisayar',
                    'phone' => 'Telefon',
                    'other' => 'Diğer'
                ];
                
                // Priority names in Turkish
                $priorityNames = [
                    'low' => 'Düşük',
                    'medium' => 'Orta',
                    'high' => 'Yüksek',
                    'urgent' => 'Acil'
                ];
                
                return [
                    'id' => $request->id,
                    'title' => $request->title,
                    'description' => $request->description,
                    'service_type' => $request->service_type,
                    'service_type_name' => $serviceTypeNames[$request->service_type] ?? 'Diğer',
                    'status' => $request->status,
                    'priority' => $request->priority,
                    'priority_name' => $priorityNames[$request->priority] ?? 'Orta',
                    'address' => $request->address,
                    'city' => $request->city,
                    'district' => $request->district,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'budget_min' => $request->budget_min,
                    'budget_max' => $request->budget_max,
                    'preferred_date' => $request->preferred_date,
                    'preferred_time' => $request->preferred_time,
                    'cancellation_reason' => $request->cancellation_reason,
                    'cancelled_at' => $request->cancelled_at,
                    'completed_at' => $request->completed_at,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                    'images' => $request->images,
                    'service_provider' => $serviceProviderData,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedRequests
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch service requests: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service requests',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

    /**
     * Cancel a service request
     */
    public function cancelRequest(Request $request, $id)
    {
        try {
            $serviceRequest = ServiceRequest::where('id', $id)
                ->where('customer_id', $request->user()->id)
                ->first();

            if (!$serviceRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Talep bulunamadı veya size ait değil'
                ], 404);
            }

            // Check if request can be cancelled
            if ($serviceRequest->status === ServiceRequest::STATUS_COMPLETED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tamamlanmış talepler iptal edilemez'
                ], 400);
            }

            if ($serviceRequest->status === ServiceRequest::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu talep zaten iptal edilmiş'
                ], 400);
            }

            // Cancel the request
            $serviceRequest->cancel('Müşteri tarafından iptal edildi');

            return response()->json([
                'success' => true,
                'message' => 'Talep başarıyla iptal edildi',
                'data' => $serviceRequest
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to cancel service request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Talep iptal edilirken hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a service request (only rejected ones)
     */
    public function deleteRequest(Request $request, $id)
    {
        try {
            $serviceRequest = ServiceRequest::find($id);
            
            if (!$serviceRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Talep bulunamadı'
                ], 404);
            }
            
            if ($serviceRequest->customer_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu talep size ait değil'
                ], 403);
            }

            // Only allow deletion of rejected or cancelled requests
            if ($serviceRequest->status !== ServiceRequest::STATUS_REJECTED && 
                $serviceRequest->status !== ServiceRequest::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sadece reddedilmiş veya iptal edilmiş talepler silinebilir'
                ], 422);
            }

            $serviceRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Talep başarıyla silindi'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete service request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Talep silinirken hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rate a service request
     */
    public function rateRequest(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz veri',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceRequest = ServiceRequest::find($id);
            
            if (!$serviceRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Talep bulunamadı'
                ], 404);
            }
            
            if ($serviceRequest->customer_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu talep size ait değil'
                ], 403);
            }

            // Only allow rating of accepted requests
            if ($serviceRequest->status !== ServiceRequest::STATUS_ACCEPTED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sadece kabul edilmiş talepler değerlendirilebilir'
                ], 422);
            }

            // Update the service request with rating
            $serviceRequest->rating = $request->rating;
            $serviceRequest->rating_comment = $request->comment;
            $serviceRequest->rated_at = now();
            $serviceRequest->save();

            // Update service provider's average rating
            if ($serviceRequest->service_provider_id) {
                $serviceProvider = ServiceProvider::find($serviceRequest->service_provider_id);
                if ($serviceProvider) {
                    $avgRating = ServiceRequest::where('service_provider_id', $serviceProvider->id)
                        ->whereNotNull('rating')
                        ->avg('rating');
                    
                    $ratingCount = ServiceRequest::where('service_provider_id', $serviceProvider->id)
                        ->whereNotNull('rating')
                        ->count();
                    
                    $serviceProvider->rating = round($avgRating, 2);
                    $serviceProvider->rating_count = $ratingCount;
                    $serviceProvider->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Değerlendirmeniz başarıyla kaydedildi',
                'data' => [
                    'request' => $serviceRequest
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to rate service request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Değerlendirme kaydedilirken hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a complaint for a service request
     */
    public function createComplaint(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:255',
                'description' => 'required|string|max:2000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz veri',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceRequest = ServiceRequest::find($id);
            
            if (!$serviceRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Talep bulunamadı'
                ], 404);
            }
            
            if ($serviceRequest->customer_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu talep size ait değil'
                ], 403);
            }

            // Only allow complaints for accepted requests
            if ($serviceRequest->status !== ServiceRequest::STATUS_ACCEPTED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sadece kabul edilmiş talepler için şikayet oluşturulabilir'
                ], 422);
            }

            // Update the service request with complaint
            $serviceRequest->has_complaint = true;
            $serviceRequest->complaint_reason = $request->reason;
            $serviceRequest->complaint_description = $request->description;
            $serviceRequest->complaint_date = now();
            $serviceRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Şikayetiniz başarıyla kaydedildi',
                'data' => [
                    'request' => $serviceRequest
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create complaint: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Şikayet kaydedilirken hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
