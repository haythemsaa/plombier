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
        Schema::create('provider_availability', function (Blueprint $table) {
            $table->id();
            $table->uuid('provider_id')->index();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            // Foreign key
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');

            // Composite index for quick lookups
            $table->index(['provider_id', 'day_of_week', 'is_available']);
        });

        // Blocked time slots (for specific dates/times when provider is not available)
        Schema::create('provider_blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->uuid('provider_id')->index();
            $table->dateTime('start_datetime')->index();
            $table->dateTime('end_datetime')->index();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');

            // Composite index for range queries
            $table->index(['provider_id', 'start_datetime', 'end_datetime']);
        });

        // Add instant_booking flag to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('instant_booking_enabled')->default(false)->after('is_verified');
            $table->index('instant_booking_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['instant_booking_enabled']);
            $table->dropColumn('instant_booking_enabled');
        });

        Schema::dropIfExists('provider_blocked_slots');
        Schema::dropIfExists('provider_availability');
    }
};
