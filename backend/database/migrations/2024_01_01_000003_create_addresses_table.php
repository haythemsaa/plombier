<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('label', 50);
            $table->string('street', 255);
            $table->string('building', 100)->nullable();
            $table->string('floor', 50)->nullable();
            $table->string('governorate', 100);
            $table->string('city', 100);
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index(['latitude', 'longitude'], 'idx_addresses_geo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
