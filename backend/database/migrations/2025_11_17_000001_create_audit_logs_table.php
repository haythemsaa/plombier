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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable()->index();
            $table->string('action', 100)->index(); // e.g., 'user.login', 'booking.created'
            $table->string('entity_type', 100)->nullable(); // e.g., 'Booking', 'User'
            $table->string('entity_id', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable(); // Before changes
            $table->json('new_values')->nullable(); // After changes
            $table->json('metadata')->nullable(); // Additional context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('severity', 20)->default('info'); // info, warning, critical
            $table->timestamp('created_at')->index();

            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Composite indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
