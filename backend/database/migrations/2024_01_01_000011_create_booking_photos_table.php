<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_photos', function (Blueprint $table) {
            $table->id();
            $table->uuid('booking_id');
            $table->enum('type', ['before', 'after', 'issue'])->nullable();
            $table->string('url', 500);
            $table->uuid('uploaded_by');
            $table->timestamp('created_at');

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_photos');
    }
};
