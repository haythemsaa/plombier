<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->unique();
            $table->enum('subscription_type', ['free', 'plus'])->default('free');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->string('preferred_payment_method', 50)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
