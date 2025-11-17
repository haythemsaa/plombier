<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_number', 50)->unique();
            $table->uuid('client_id');
            $table->uuid('provider_id');
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('address_id')->constrained('addresses');
            $table->timestamp('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])
                ->default('pending')->index();
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->decimal('commission', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->text('instructions')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('cancelled_by', 20)->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('users');
            $table->foreign('provider_id')->references('id')->on('users');
            $table->index('client_id');
            $table->index('provider_id');
            $table->index('scheduled_at');
            $table->index('booking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
