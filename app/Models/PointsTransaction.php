<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'source',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for earned transactions
     */
    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    /**
     * Scope for spent transactions
     */
    public function scopeSpent($query)
    {
        return $query->where('type', 'spent');
    }

    /**
     * Scope for transactions by source
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Get total points earned by user
     */
    public static function getTotalEarned($userId)
    {
        return static::where('user_id', $userId)
            ->where('type', 'earned')
            ->sum('amount');
    }

    /**
     * Get total points spent by user
     */
    public static function getTotalSpent($userId)
    {
        return static::where('user_id', $userId)
            ->where('type', 'spent')
            ->sum('amount');
    }

    /**
     * Get current balance for user
     */
    public static function getBalance($userId)
    {
        return static::getTotalEarned($userId) - static::getTotalSpent($userId);
    }

    /**
     * Get transactions for user with pagination
     */
    public static function getForUser($userId, $limit = 20)
    {
        return static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get earnings summary by source
     */
    public static function getEarningsSummary($userId)
    {
        return static::where('user_id', $userId)
            ->where('type', 'earned')
            ->selectRaw('source, SUM(amount) as total_points, COUNT(*) as count')
            ->groupBy('source')
            ->get();
    }
} 