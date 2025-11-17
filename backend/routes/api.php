<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-phone', [AuthController::class, 'verifyPhone']);

// Service routes (public)
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/service-categories', [ServiceController::class, 'categories']);

// Provider routes (public)
Route::post('/providers/search', [ProviderController::class, 'search']);
Route::get('/providers/{id}', [ProviderController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{id}/start', [BookingController::class, 'start']);
    Route::post('/bookings/{id}/complete', [BookingController::class, 'complete']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::post('/reviews/{id}/response', [ReviewController::class, 'respond']);

    // Addresses
    Route::get('/addresses', [App\Http\Controllers\Api\AddressController::class, 'index']);
    Route::post('/addresses', [App\Http\Controllers\Api\AddressController::class, 'store']);
    Route::put('/addresses/{id}', [App\Http\Controllers\Api\AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [App\Http\Controllers\Api\AddressController::class, 'destroy']);

    // Payments
    Route::post('/payments/initiate', [App\Http\Controllers\Api\PaymentController::class, 'initiate']);
    Route::get('/payments/{id}/status', [App\Http\Controllers\Api\PaymentController::class, 'status']);

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);

    // Provider specific routes
    Route::middleware('provider')->group(function () {
        Route::get('/provider/dashboard', [ProviderController::class, 'dashboard']);
        Route::get('/provider/earnings', [ProviderController::class, 'earnings']);
        Route::post('/provider/services', [ProviderController::class, 'updateServices']);
        Route::post('/provider/zones', [ProviderController::class, 'updateZones']);
        Route::get('/provider/bookings', [BookingController::class, 'providerBookings']);
    });
});

// Webhooks
Route::post('/webhooks/paytech', [App\Http\Controllers\Webhooks\PayTechWebhookController::class, 'handleIPN']);
