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
        // Service packages table
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->enum('type', ['one_time', 'monthly', 'quarterly', 'annual'])->index();
            $table->integer('sessions_included'); // Number of service sessions
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2); // For showing savings
            $table->integer('validity_days')->nullable(); // How long package is valid
            $table->json('features')->nullable(); // Additional features/benefits
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['service_id', 'type', 'is_active']);
        });

        // User package subscriptions
        Schema::create('package_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->index();
            $table->foreignId('package_id')->constrained('service_packages')->onDelete('restrict');
            $table->enum('status', ['active', 'paused', 'cancelled', 'expired'])->default('active')->index();
            $table->integer('sessions_remaining');
            $table->integer('sessions_used')->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->decimal('price_paid', 10, 2);
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'next_billing_date']);
            $table->index('end_date');
        });

        // Advanced loyalty tiers
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bronze, Silver, Gold, Platinum
            $table->integer('level')->unique(); // 1, 2, 3, 4
            $table->integer('min_points_required');
            $table->integer('min_bookings_required')->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0); // e.g., 5%, 10%, 15%
            $table->integer('priority_support')->default(0); // 0=normal, 1=high, 2=highest
            $table->json('benefits')->nullable(); // Additional perks
            $table->string('badge_color', 20);
            $table->string('badge_icon', 50)->nullable();
            $table->timestamps();
        });

        // Update clients table with tier
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('loyalty_tier_id')->nullable()->after('loyalty_points')->constrained()->onDelete('set null');
            $table->integer('total_bookings_count')->default(0)->after('loyalty_tier_id');
            $table->decimal('total_spent', 10, 2)->default(0)->after('total_bookings_count');
            $table->index('loyalty_tier_id');
        });

        // Recurring bookings
        Schema::create('recurring_booking_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_id')->index();
            $table->foreignId('provider_service_id')->constrained()->onDelete('cascade');
            $table->uuid('preferred_provider_id')->nullable();
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly'])->index();
            $table->json('days_of_week')->nullable(); // For weekly: [1,3,5] = Mon, Wed, Fri
            $table->integer('day_of_month')->nullable(); // For monthly: 1-31
            $table->time('preferred_time');
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active')->index();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_booking_date')->nullable()->index();
            $table->integer('bookings_created')->default(0);
            $table->integer('max_bookings')->nullable(); // Limit number of bookings
            $table->timestamps();

            // Foreign keys
            $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('preferred_provider_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['client_id', 'status']);
            $table->index(['status', 'next_booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_booking_schedules');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['loyalty_tier_id']);
            $table->dropIndex(['loyalty_tier_id']);
            $table->dropColumn(['loyalty_tier_id', 'total_bookings_count', 'total_spent']);
        });

        Schema::dropIfExists('loyalty_tiers');
        Schema::dropIfExists('package_subscriptions');
        Schema::dropIfExists('service_packages');
    }
};
