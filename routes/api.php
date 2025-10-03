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
});

// Admin routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/service-providers/pending', [AdminController::class, 'getPendingServiceProviders']);
        Route::post('/service-providers/{id}/approve', [AdminController::class, 'approveServiceProvider']);
        Route::post('/service-providers/{id}/reject', [AdminController::class, 'rejectServiceProvider']);
        Route::put('/users/{id}/status', [AdminController::class, 'updateUserStatus']);
        Route::get('/service-requests', [AdminController::class, 'getServiceRequests']);
    });
});

// Public service routes
Route::prefix('services')->group(function () {
    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/types', [ServiceController::class, 'getServiceTypes']);
    Route::get('/{id}', [ServiceController::class, 'show']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // Service routes for customers
    Route::prefix('services')->group(function () {
        Route::post('/request', [ServiceController::class, 'createRequest']);
        Route::get('/my-requests', [ServiceController::class, 'getCustomerRequests']);
    });

    // Service provider routes
    Route::prefix('service')->group(function () {
        Route::get('/dashboard', [ServiceProviderController::class, 'dashboard']);
        Route::get('/requests', [ServiceProviderController::class, 'getRequests']);
        Route::get('/stats', [ServiceProviderController::class, 'getStats']);
        Route::post('/requests/{id}/accept', [ServiceProviderController::class, 'acceptRequest']);
        Route::post('/requests/{id}/complete', [ServiceProviderController::class, 'completeRequest']);
        Route::put('/profile', [ServiceProviderController::class, 'updateProfile']);
    });
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now(),
        'service' => 'TamirciBul API'
    ]);
});
