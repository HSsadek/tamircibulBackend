<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceProviderController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AIChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('/ai/chat', [AIChatController::class, 'chat']);

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password/forgot', [AuthController::class, 'sendPasswordResetEmail']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    Route::post('/password/verify-token', [AuthController::class, 'verifyResetToken']);
});

// Admin routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/validate-token', [AdminController::class, 'validateToken']);
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/service-providers/pending', [AdminController::class, 'getPendingServiceProviders']);
        Route::post('/service-providers/{id}/approve', [AdminController::class, 'approveServiceProvider']);
        Route::post('/service-providers/{id}/reject', [AdminController::class, 'rejectServiceProvider']);
        Route::put('/users/{id}/status', [AdminController::class, 'updateUserStatus']);
        Route::get('/service-requests', [AdminController::class, 'getServiceRequests']);
    });
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/notifications', [AuthController::class, 'updateNotificationPreferences']);
    });

    // Service routes for customers - MUST be BEFORE public services routes
    Route::prefix('services')->group(function () {
        Route::get('/my-requests', [ServiceController::class, 'getCustomerRequests']);
        Route::post('/request', [ServiceController::class, 'createRequest']);
        Route::post('/request/{id}/cancel', [ServiceController::class, 'cancelRequest']);
        Route::delete('/request/{id}', [ServiceController::class, 'deleteRequest']);
        Route::post('/request/{id}/rate', [ServiceController::class, 'rateRequest']);
        Route::post('/request/{id}/complaint', [ServiceController::class, 'createComplaint']);
    });

    // Service provider routes
    Route::prefix('service')->group(function () {
        Route::get('/dashboard', [ServiceProviderController::class, 'dashboard']);
        Route::get('/requests', [ServiceProviderController::class, 'getRequests']);
        Route::get('/stats', [ServiceProviderController::class, 'getStats']);
        Route::post('/requests/{id}/accept', [ServiceProviderController::class, 'acceptRequest']);
        Route::post('/requests/{id}/reject', [ServiceProviderController::class, 'rejectRequest']);
        Route::post('/requests/{id}/complete', [ServiceProviderController::class, 'completeRequest']);
        Route::delete('/requests/{id}', [ServiceProviderController::class, 'deleteRequest']);
        Route::get('/profile', [ServiceProviderController::class, 'getProfile']);
        Route::put('/profile', [ServiceProviderController::class, 'updateProfile']);
        Route::post('/profile/logo', [ServiceProviderController::class, 'uploadLogo']);
        Route::delete('/profile/logo', [ServiceProviderController::class, 'deleteLogo']);
        
        // Notifications
        Route::get('/notifications', [ServiceProviderController::class, 'getNotifications']);
        Route::post('/notifications/{id}/read', [ServiceProviderController::class, 'markNotificationAsRead']);
    });
});

// Public service routes - MUST be AFTER protected routes to avoid conflicts
Route::prefix('services')->group(function () {
    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/types', [ServiceController::class, 'getServiceTypes']);
    // {id} route MUST be last
    Route::get('/{id}', [ServiceController::class, 'show']);
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now(),
        'service' => 'TamirciBul API'
    ]);
});
