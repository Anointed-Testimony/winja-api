<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'age_group',
        'geo_location',
        'academic_level',
        'interests',
        'notification_preferences',
        'referral_code',
        'referred_by',
        'profile_image',
        'is_premium',
        'last_login_at',
        'status',
        'user_type',
        'company_name',
        'company_description',
        'company_website',
        'company_logo',
        'partner_since',
        'partner_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'interests' => 'array',
        'notification_preferences' => 'array',
        'is_premium' => 'boolean',
        'last_login_at' => 'datetime',
        'partner_since' => 'datetime',
    ];

    public function savedOpportunities()
    {
        return $this->hasMany(SavedOpportunity::class);
    }

    public function referrals() {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy() {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function badges() {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('earned_at', 'metadata')
            ->withTimestamps();
    }

    public function getReferralStats() {
        return [
            'total_referrals' => $this->referrals()->count(),
            'completed_referrals' => $this->referrals()->where('status', 'completed')->count(),
            'pending_referrals' => $this->referrals()->where('status', 'pending')->count(),
            'total_points' => $this->badges()->sum('points_value'),
        ];
    }

    public function getReferralProgress() {
        $total = $this->referrals()->count();
        $completed = $this->referrals()->where('status', 'completed')->count();
        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? ($completed / $total) * 100 : 0
        ];
    }

    public function getTotalPoints() {
        return $this->badges()->sum('points_value');
    }

    public function checkAndAwardBadges() {
        $badges = Badge::whereDoesntHave('users', function ($query) {
            $query->where('users.id', $this->id);
        })->get();

        foreach ($badges as $badge) {
            if ($badge->checkRequirements($this)) {
                $this->badges()->attach($badge->id, [
                    'earned_at' => now(),
                    'metadata' => [
                        'earned_through' => 'automatic_check',
                        'requirements_met' => $badge->requirements
                    ]
                ]);
            }
        }
    }

    public function applicationTrackers()
    {
        return $this->hasMany(ApplicationTracker::class);
    }

    public function isBanned() {
        return $this->status === 'banned';
    }

    public function isActive() {
        return $this->status === 'active';
    }

    public function scopeFilter($query, $filters) {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['referred'])) {
            $query->where('referred_by', '!=', null);
        }
        if (isset($filters['interests'])) {
            $query->whereJsonContains('interests', $filters['interests']);
        }
        return $query;
    }

    public function sponsoredOpportunities() {
        return $this->hasMany(SponsoredOpportunity::class, 'partner_id');
    }

    public function otps() {
        return $this->hasMany(Otp::class);
    }

    public function isPartner() {
        return $this->user_type === 'partner';
    }

    public function isActivePartner() {
        return $this->isPartner() && $this->partner_status === 'active';
    }

    public function getPartnerMetrics() {
        return [
            'total_sponsored' => $this->sponsoredOpportunities()->count(),
            'active_sponsored' => $this->sponsoredOpportunities()->where('status', 'approved')->count(),
            'total_spend' => $this->sponsoredOpportunities()->where('payment_status', 'paid')->count(),
            'partner_since' => $this->partner_since,
        ];
    }
}
