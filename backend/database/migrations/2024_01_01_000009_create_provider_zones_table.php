<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->onDelete('cascade');
            $table->string('governorate', 100);
            $table->json('cities')->nullable();
            $table->integer('radius_km')->default(10);
            $table->timestamps();

            $table->index('governorate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_zones');
    }
};
