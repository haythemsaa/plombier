<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('booking_id')->unique();
            $table->uuid('client_id');
            $table->uuid('provider_id');
            $table->integer('rating'); // 1-5
            $table->integer('professionalism_rating')->nullable(); // 1-5
            $table->integer('quality_rating')->nullable(); // 1-5
            $table->integer('value_rating')->nullable(); // 1-5
            $table->text('comment')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings');
            $table->foreign('client_id')->references('id')->on('users');
            $table->foreign('provider_id')->references('id')->on('users');
            $table->index('provider_id');
            $table->index('rating');

            // Add check constraints
            $table->check('rating >= 1 AND rating <= 5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
