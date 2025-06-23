<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPoints;
use App\Models\PointsSetting;
use App\Models\PointsTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointsService
{
    /**
     * Award points to a user
     */
    public static function awardPoints($userId, $points, $source, $description = null)
    {
        try {
            DB::beginTransaction();

            $userPoints = UserPoints::getOrCreateForUser($userId);
            $userPoints->addPoints($points, $source);

            DB::commit();

            Log::info("Points awarded to user {$userId}: {$points} points from {$source}");

            return $userPoints;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to award points to user {$userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Award signup points to a new user
     */
    public static function awardSignupPoints($userId)
    {
        $settings = PointsSetting::getCurrent();
        $signupPoints = $settings->signup_points;

        if ($signupPoints > 0) {
            return self::awardPoints(
                $userId,
                $signupPoints,
                'signup',
                "Welcome bonus for signing up"
            );
        }

        return null;
    }

    /**
     * Award referral points to a user when their referral completes
     */
    public static function awardReferralPoints($referrerId, $referredUserId)
    {
        $settings = PointsSetting::getCurrent();
        $referralPoints = $settings->referral_points;

        if ($referralPoints > 0) {
            return self::awardPoints(
                $referrerId,
                $referralPoints,
                'referral',
                "Referral bonus for user {$referredUserId}"
            );
        }

        return null;
    }

    /**
     * Get user's points summary
     */
    public static function getUserPointsSummary($userId)
    {
        $userPoints = UserPoints::getOrCreateForUser($userId);
        $settings = PointsSetting::getCurrent();

        return [
            'points_earned' => $userPoints->points_earned,
            'points_spent' => $userPoints->points_spent,
            'points_balance' => $userPoints->points_balance,
            'total_earnings' => $userPoints->calculateTotalEarnings(),
            'withdrawn_amount' => $userPoints->withdrawn_amount,
            'pending_withdrawal' => $userPoints->pending_withdrawal,
            'available_for_withdrawal' => $userPoints->getAvailableForWithdrawal(),
            'point_value_in_naira' => $settings->point_value_in_naira,
            'minimum_withdrawal' => $settings->minimum_withdrawal,
            'can_withdraw' => $userPoints->canWithdraw($userPoints->getAvailableForWithdrawal()),
        ];
    }

    /**
     * Get user's points transactions
     */
    public static function getUserTransactions($userId, $limit = 20)
    {
        return PointsTransaction::getForUser($userId, $limit);
    }

    /**
     * Get user's earnings summary by source
     */
    public static function getUserEarningsSummary($userId)
    {
        return PointsTransaction::getEarningsSummary($userId);
    }

    /**
     * Calculate total earnings for a user
     */
    public static function calculateTotalEarnings($userId)
    {
        $userPoints = UserPoints::getOrCreateForUser($userId);
        return $userPoints->calculateTotalEarnings();
    }

    /**
     * Get points settings
     */
    public static function getPointsSettings()
    {
        return PointsSetting::getCurrent();
    }

    /**
     * Update points settings
     */
    public static function updatePointsSettings($data)
    {
        $settings = PointsSetting::getCurrent();
        $settings->update($data);
        return $settings;
    }

    /**
     * Get leaderboard by points
     */
    public static function getPointsLeaderboard($limit = 10)
    {
        return UserPoints::with('user')
            ->orderBy('points_balance', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($userPoints) {
                return [
                    'user_id' => $userPoints->user_id,
                    'name' => $userPoints->user->name,
                    'points_balance' => $userPoints->points_balance,
                    'total_earnings' => $userPoints->calculateTotalEarnings(),
                    'profile_image' => $userPoints->user->profile_image,
                ];
            });
    }

    /**
     * Get system-wide points statistics
     */
    public static function getSystemStats()
    {
        $totalUsers = UserPoints::count();
        $totalPointsAwarded = UserPoints::sum('points_earned');
        $totalPointsSpent = UserPoints::sum('points_spent');
        
        // Calculate total earnings dynamically
        $settings = PointsSetting::getCurrent();
        $totalEarnings = $settings->pointsToNaira($totalPointsAwarded);
        $totalWithdrawn = UserPoints::sum('withdrawn_amount');

        return [
            'total_users_with_points' => $totalUsers,
            'total_points_awarded' => $totalPointsAwarded,
            'total_points_spent' => $totalPointsSpent,
            'total_points_in_circulation' => $totalPointsAwarded - $totalPointsSpent,
            'total_earnings' => $totalEarnings,
            'total_withdrawn' => $totalWithdrawn,
            'pending_withdrawals' => UserPoints::sum('pending_withdrawal'),
        ];
    }

    /**
     * Validate if user can make withdrawal request
     */
    public static function canMakeWithdrawalRequest($userId, $amount)
    {
        $userPoints = UserPoints::getOrCreateForUser($userId);
        $settings = PointsSetting::getCurrent();

        // Check if amount meets minimum withdrawal
        if ($amount < $settings->minimum_withdrawal) {
            return [
                'can_withdraw' => false,
                'message' => "Minimum withdrawal amount is ₦{$settings->minimum_withdrawal}",
            ];
        }

        // Check if user has enough balance
        $availableBalance = $userPoints->getAvailableForWithdrawal();
        if ($availableBalance < $amount) {
            return [
                'can_withdraw' => false,
                'message' => "Insufficient balance. Available: ₦{$availableBalance}",
            ];
        }

        return [
            'can_withdraw' => true,
            'message' => 'Withdrawal request can be made',
            'points_required' => $settings->nairaToPoints($amount),
        ];
    }
} 