<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->unique();
            $table->string('business_name', 255)->nullable();
            $table->string('cin', 20)->unique();
            $table->string('business_license', 100)->nullable();
            $table->enum('subscription_type', ['starter', 'pro', 'premium'])->default('starter');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(100.00);
            $table->integer('response_time_avg')->default(0); // en secondes
            $table->decimal('total_earnings', 12, 2)->default(0.00);
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->text('bio')->nullable();
            $table->integer('years_experience')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['rating_average', 'rating_count'], 'idx_providers_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
