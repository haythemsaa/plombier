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
        // Add referral code to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 10)->unique()->nullable()->after('fcm_token');
            $table->uuid('referred_by')->nullable()->after('referral_code');
            $table->index('referral_code');
            $table->index('referred_by');
        });

        // Referrals table for tracking
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->uuid('referrer_id')->index(); // Who sent the invite
            $table->uuid('referred_id')->index(); // Who signed up
            $table->string('referral_code', 10);
            $table->enum('status', ['pending', 'completed', 'rewarded'])->default('pending')->index();
            $table->decimal('referrer_reward', 10, 2)->default(0);
            $table->decimal('referred_reward', 10, 2)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');

            // Composite indexes
            $table->index(['referrer_id', 'status']);
            $table->index(['referred_id', 'status']);
        });

        // Rewards/wallet table
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->unique();
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Wallet transactions
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('user_wallets')->onDelete('cascade');
            $table->enum('type', ['credit', 'debit'])->index();
            $table->enum('source', ['referral', 'cashback', 'bonus', 'refund', 'payment'])->index();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['wallet_id', 'created_at']);
            $table->index(['wallet_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('user_wallets');
        Schema::dropIfExists('referrals');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['referral_code']);
            $table->dropIndex(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by']);
        });
    }
};
