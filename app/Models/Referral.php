<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'status',
        'rewards_claimed',
        'completed_at',
        'expired_at',
    ];

    protected $casts = [
        'rewards_claimed' => 'array',
        'completed_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isExpired()
    {
        return $this->status === 'expired';
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Award referral points to the referrer
        $settings = \App\Models\PointsSetting::getCurrent();
        $referrer = $this->referrer;
        
        if ($referrer) {
            $userPoints = $referrer->userPoints;
            if (!$userPoints) {
                $userPoints = \App\Models\UserPoints::getOrCreateForUser($referrer->id);
            }
            
            // Award referral points
            $userPoints->addPoints($settings->referral_points, 'referral');
        }
    }

    public function expire()
    {
        $this->update([
            'status' => 'expired',
            'expired_at' => now(),
        ]);
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'expired' => 'Expired',
            default => 'Unknown'
        };
    }

    public function getRewardsClaimed()
    {
        return $this->rewards_claimed ?? [];
    }
} 