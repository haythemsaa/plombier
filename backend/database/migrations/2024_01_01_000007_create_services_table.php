<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('service_categories');
            $table->string('name_ar', 255);
            $table->string('name_fr', 255);
            $table->text('description_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->string('icon', 255)->nullable();
            $table->enum('unit', ['hour', 'task', 'sqm'])->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('commission_rate', 5, 2)->default(18.00);
            $table->boolean('is_active')->default(true)->index();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
