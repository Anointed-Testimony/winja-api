<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointsSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'point_value_in_naira',
        'minimum_withdrawal',
        'signup_points',
        'referral_points',
    ];

    protected $casts = [
        'point_value_in_naira' => 'decimal:2',
        'minimum_withdrawal' => 'decimal:2',
        'signup_points' => 'integer',
        'referral_points' => 'integer',
    ];

    /**
     * Get the current points settings (singleton pattern)
     */
    public static function getCurrent()
    {
        return static::first() ?? static::create([
            'point_value_in_naira' => 1.00,
            'minimum_withdrawal' => 1000.00,
            'signup_points' => 100,
            'referral_points' => 500,
        ]);
    }

    /**
     * Convert points to Naira
     */
    public function pointsToNaira($points)
    {
        return $points * $this->point_value_in_naira;
    }

    /**
     * Convert Naira to points
     */
    public function nairaToPoints($naira)
    {
        return $naira / $this->point_value_in_naira;
    }

    /**
     * Check if points meet minimum withdrawal requirement
     */
    public function canWithdraw($points)
    {
        return $this->pointsToNaira($points) >= $this->minimum_withdrawal;
    }
} 