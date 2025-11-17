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
        // Provider real-time location tracking
        Schema::create('provider_locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('provider_id')->index();
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable(); // in meters
            $table->decimal('speed', 8, 2)->nullable(); // in km/h
            $table->decimal('heading', 5, 2)->nullable(); // direction in degrees
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            // Foreign key
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');

            // Composite indexes for spatial queries
            $table->index(['provider_id', 'recorded_at']);
            $table->index(['booking_id', 'recorded_at']);
            $table->index(['latitude', 'longitude']);
        });

        // Add tracking fields to bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('provider_en_route_at')->nullable()->after('confirmed_at');
            $table->decimal('estimated_arrival_minutes', 5, 2)->nullable()->after('provider_en_route_at');
            $table->boolean('tracking_enabled')->default(false)->after('estimated_arrival_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['provider_en_route_at', 'estimated_arrival_minutes', 'tracking_enabled']);
        });

        Schema::dropIfExists('provider_locations');
    }
};
