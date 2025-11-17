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
        // Conversations table
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('booking_id')->index();
            $table->uuid('client_id')->index();
            $table->uuid('provider_id')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->uuid('last_message_by')->nullable();
            $table->integer('client_unread_count')->default(0);
            $table->integer('provider_unread_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');

            // Composite indexes
            $table->index(['client_id', 'is_active']);
            $table->index(['provider_id', 'is_active']);
            $table->unique(['booking_id']); // Une conversation par réservation
        });

        // Messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->uuid('sender_id')->index();
            $table->enum('sender_type', ['client', 'provider'])->index();
            $table->enum('type', ['text', 'image', 'location', 'system'])->default('text');
            $table->text('content');
            $table->json('metadata')->nullable(); // For images, location data, etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
