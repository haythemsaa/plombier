<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('type'); // For filtering by user type
            $table->index('is_verified'); // For filtering verified users
            $table->index(['type', 'is_verified']); // Composite for providers list
            $table->index('created_at'); // For sorting
        });

        // Providers table indexes
        Schema::table('providers', function (Blueprint $table) {
            $table->index('average_rating'); // For sorting by rating
            $table->index('total_bookings'); // For sorting by popularity
            $table->index(['is_verified', 'average_rating']); // For verified + highly rated
            $table->index('created_at');
        });

        // Bookings table indexes
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('status'); // For filtering by status
            $table->index('scheduled_at'); // For date range queries
            $table->index(['client_id', 'status']); // Client's bookings by status
            $table->index(['provider_id', 'status']); // Provider's bookings by status
            $table->index(['status', 'scheduled_at']); // Status + date filtering
            $table->index('created_at');
        });

        // Reviews table indexes
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('overall_rating'); // For filtering high-rated
            $table->index(['provider_id', 'overall_rating']); // Provider's ratings
            $table->index('created_at');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('status'); // For filtering by payment status
            $table->index('gateway'); // For gateway-specific queries
            $table->index(['booking_id', 'status']); // Booking payment status
            $table->index('created_at');
        });

        // Provider services table indexes
        Schema::table('provider_services', function (Blueprint $table) {
            $table->index('is_active'); // For active services only
            $table->index(['provider_id', 'is_active']); // Provider's active services
            $table->index('price'); // For price range filtering
            $table->index(['service_id', 'price']); // Service comparison
        });

        // Addresses table indexes
        Schema::table('addresses', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']); // For geolocation queries
            $table->index('governorate'); // For filtering by region
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('read_at'); // For unread notifications
            $table->index(['user_id', 'read_at']); // User's unread notifications
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['type', 'is_verified']);
            $table->dropIndex(['created_at']);
        });

        // Providers
        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex(['average_rating']);
            $table->dropIndex(['total_bookings']);
            $table->dropIndex(['is_verified', 'average_rating']);
            $table->dropIndex(['created_at']);
        });

        // Bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
            $table->dropIndex(['client_id', 'status']);
            $table->dropIndex(['provider_id', 'status']);
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['created_at']);
        });

        // Reviews
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['overall_rating']);
            $table->dropIndex(['provider_id', 'overall_rating']);
            $table->dropIndex(['created_at']);
        });

        // Payments
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['gateway']);
            $table->dropIndex(['booking_id', 'status']);
            $table->dropIndex(['created_at']);
        });

        // Provider services
        Schema::table('provider_services', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['provider_id', 'is_active']);
            $table->dropIndex(['price']);
            $table->dropIndex(['service_id', 'price']);
        });

        // Addresses
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['governorate']);
        });

        // Notifications
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['read_at']);
            $table->dropIndex(['user_id', 'read_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
