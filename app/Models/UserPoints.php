<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'points_earned',
        'points_spent',
        'points_balance',
        'total_earnings',
        'withdrawn_amount',
        'pending_withdrawal',
    ];

    protected $casts = [
        'points_earned' => 'integer',
        'points_spent' => 'integer',
        'points_balance' => 'integer',
        'total_earnings' => 'decimal:2',
        'withdrawn_amount' => 'decimal:2',
        'pending_withdrawal' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create user points record
     */
    public static function getOrCreateForUser($userId)
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'points_earned' => 0,
                'points_spent' => 0,
                'points_balance' => 0,
                'total_earnings' => 0.00,
                'withdrawn_amount' => 0.00,
                'pending_withdrawal' => 0.00,
            ]
        );
    }

    /**
     * Add points to user
     */
    public function addPoints($points, $source = 'general')
    {
        $this->increment('points_earned', $points);
        $this->increment('points_balance', $points);

        // Create transaction record
        PointsTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'earned',
            'amount' => $points,
            'source' => $source,
            'description' => "Earned {$points} points from {$source}",
            'metadata' => [
                'point_value_at_time' => PointsSetting::getCurrent()->point_value_in_naira,
            ],
        ]);

        return $this;
    }

    /**
     * Calculate total earnings dynamically based on current point value
     */
    public function calculateTotalEarnings()
    {
        $settings = PointsSetting::getCurrent();
        return $settings->pointsToNaira($this->points_earned);
    }

    /**
     * Calculate total earnings from spent points (for withdrawal tracking)
     */
    public function calculateTotalSpentEarnings()
    {
        $settings = PointsSetting::getCurrent();
        return $settings->pointsToNaira($this->points_spent);
    }

    /**
     * Deduct points from user
     */
    public function deductPoints($points, $reason = 'withdrawal')
    {
        if ($this->points_balance < $points) {
            throw new \Exception('Insufficient points balance');
        }

        $this->increment('points_spent', $points);
        $this->decrement('points_balance', $points);

        // Create transaction record
        PointsTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'spent',
            'amount' => $points,
            'source' => $reason,
            'description' => "Spent {$points} points for {$reason}",
        ]);

        return $this;
    }

    /**
     * Get available balance for withdrawal
     */
    public function getAvailableForWithdrawal()
    {
        $settings = PointsSetting::getCurrent();
        return $settings->pointsToNaira($this->points_balance);
    }

    /**
     * Check if user can withdraw
     */
    public function canWithdraw($amount)
    {
        $settings = PointsSetting::getCurrent();
        return $this->getAvailableForWithdrawal() >= $amount && $amount >= $settings->minimum_withdrawal;
    }
} 