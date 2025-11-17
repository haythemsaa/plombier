<?php

namespace App\Services;

use App\Models\User;
use App\Models\Referral;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * Referral reward amounts
     */
    const REFERRER_REWARD = 20.00; // 20 TND for referrer
    const REFERRED_REWARD = 10.00; // 10 TND for referred user

    /**
     * Minimum booking amount to qualify referral
     */
    const MIN_BOOKING_AMOUNT = 50.00;

    /**
     * Generate unique referral code for user.
     */
    public static function generateReferralCode(User $user): string
    {
        do {
            // Generate code from name + random string
            $code = strtoupper(substr($user->name, 0, 3) . Str::random(5));
        } while (User::where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Record a referral when user signs up.
     */
    public static function recordReferral(User $newUser, string $referralCode): ?Referral
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer || $referrer->id === $newUser->id) {
            return null;
        }

        // Create referral record
        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $newUser->id,
            'referral_code' => $referralCode,
            'status' => 'pending',
            'referrer_reward' => self::REFERRER_REWARD,
            'referred_reward' => self::REFERRED_REWARD,
        ]);

        // Update user's referred_by
        $newUser->update(['referred_by' => $referrer->id]);

        return $referral;
    }

    /**
     * Complete referral when referred user makes qualifying booking.
     */
    public static function completeReferral(User $referredUser, float $bookingAmount): bool
    {
        if ($bookingAmount < self::MIN_BOOKING_AMOUNT) {
            return false;
        }

        $referral = Referral::where('referred_id', $referredUser->id)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            return false;
        }

        // Mark as completed
        $referral->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Reward both referrer and referred user.
     */
    public static function rewardReferral(Referral $referral): bool
    {
        if ($referral->status !== 'completed') {
            return false;
        }

        // Get or create wallets
        $referrerWallet = self::getOrCreateWallet($referral->referrer_id);
        $referredWallet = self::getOrCreateWallet($referral->referred_id);

        // Credit referrer
        self::creditWallet(
            $referrerWallet,
            $referral->referrer_reward,
            'referral',
            "Referral bonus for inviting {$referral->referred->name}",
            ['referral_id' => $referral->id]
        );

        // Credit referred user
        self::creditWallet(
            $referredWallet,
            $referral->referred_reward,
            'referral',
            "Welcome bonus for joining via referral",
            ['referral_id' => $referral->id]
        );

        // Mark as rewarded
        $referral->update([
            'status' => 'rewarded',
            'rewarded_at' => now(),
        ]);

        return true;
    }

    /**
     * Get or create wallet for user.
     */
    protected static function getOrCreateWallet(string $userId): UserWallet
    {
        return UserWallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
        );
    }

    /**
     * Credit wallet.
     */
    public static function creditWallet(
        UserWallet $wallet,
        float $amount,
        string $source,
        string $description,
        ?array $metadata = null
    ): WalletTransaction {
        $wallet->increment('balance', $amount);
        $wallet->increment('total_earned', $amount);

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'source' => $source,
            'amount' => $amount,
            'balance_after' => $wallet->fresh()->balance,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Debit wallet.
     */
    public static function debitWallet(
        UserWallet $wallet,
        float $amount,
        string $source,
        string $description,
        ?array $metadata = null
    ): ?WalletTransaction {
        if ($wallet->balance < $amount) {
            return null; // Insufficient balance
        }

        $wallet->decrement('balance', $amount);
        $wallet->increment('total_spent', $amount);

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'source' => $source,
            'amount' => $amount,
            'balance_after' => $wallet->fresh()->balance,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get referral statistics for user.
     */
    public static function getReferralStats(User $user): array
    {
        $totalReferrals = Referral::where('referrer_id', $user->id)->count();
        $completedReferrals = Referral::where('referrer_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $rewardedReferrals = Referral::where('referrer_id', $user->id)
            ->where('status', 'rewarded')
            ->count();
        $totalEarned = Referral::where('referrer_id', $user->id)
            ->where('status', 'rewarded')
            ->sum('referrer_reward');

        return [
            'total_referrals' => $totalReferrals,
            'pending_referrals' => $totalReferrals - $completedReferrals,
            'completed_referrals' => $completedReferrals,
            'rewarded_referrals' => $rewardedReferrals,
            'total_earned' => $totalEarned,
            'referral_code' => $user->referral_code,
        ];
    }

    /**
     * Get wallet balance for user.
     */
    public static function getWalletBalance(string $userId): float
    {
        $wallet = UserWallet::where('user_id', $userId)->first();
        return $wallet ? $wallet->balance : 0;
    }

    /**
     * Apply wallet balance to booking payment.
     */
    public static function applyWalletToBooking(User $user, float $bookingAmount): array
    {
        $wallet = self::getOrCreateWallet($user->id);
        $walletBalance = $wallet->balance;

        if ($walletBalance <= 0) {
            return [
                'wallet_used' => 0,
                'remaining_amount' => $bookingAmount,
                'wallet_balance_after' => $walletBalance,
            ];
        }

        $walletUsed = min($walletBalance, $bookingAmount);
        $remainingAmount = $bookingAmount - $walletUsed;

        return [
            'wallet_used' => $walletUsed,
            'remaining_amount' => $remainingAmount,
            'wallet_balance_after' => $walletBalance - $walletUsed,
        ];
    }
}
