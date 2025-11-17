<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| Current stable API version. All routes here are versioned as v1.
|
*/

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
Route::get('/providers/{id}/reviews', [ReviewController::class, 'providerReviews']);

// Public reviews
Route::get('/reviews/featured', [ReviewController::class, 'featured']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // FCM Token update
    Route::post('/auth/fcm-token', [AuthController::class, 'updateFcmToken']);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::get('/bookings/{id}/can-review', [ReviewController::class, 'canReview']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{id}/start', [BookingController::class, 'start']);
    Route::post('/bookings/{id}/complete', [BookingController::class, 'complete']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::post('/reviews/{id}/response', [ReviewController::class, 'respond']);
    Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
    Route::get('/reviews-about-me', [ReviewController::class, 'reviewsAboutMe']);

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
    Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);

    // Refunds
    Route::post('/payments/{id}/refund', [App\Http\Controllers\Api\PaymentController::class, 'refund']);

    // Provider specific routes
    Route::middleware('provider')->group(function () {
        Route::get('/provider/dashboard', [ProviderController::class, 'dashboard']);
        Route::get('/provider/earnings', [ProviderController::class, 'earnings']);
        Route::post('/provider/services', [ProviderController::class, 'updateServices']);
        Route::post('/provider/zones', [ProviderController::class, 'updateZones']);
        Route::get('/provider/bookings', [BookingController::class, 'providerBookings']);
    });

    // Monitoring routes (admin only)
    Route::prefix('monitoring')->group(function () {
        Route::get('/stats', [App\Http\Controllers\Api\MonitoringController::class, 'stats']);
        Route::get('/performance', [App\Http\Controllers\Api\MonitoringController::class, 'performance']);
        Route::post('/cache/clear', [App\Http\Controllers\Api\MonitoringController::class, 'clearCache']);
    });

    // Audit logs (admin only)
    Route::get('/audit-logs', [App\Http\Controllers\Api\AuditController::class, 'index']);
    Route::get('/audit-logs/user/{id}', [App\Http\Controllers\Api\AuditController::class, 'userLogs']);
    Route::get('/audit-logs/entity/{type}/{id}', [App\Http\Controllers\Api\AuditController::class, 'entityLogs']);
});
