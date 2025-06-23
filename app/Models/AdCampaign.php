<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'partner_id',
        'opportunity_id',
        'ad_type',
        'duration_type',
        'duration_value',
        'amount_paid',
        'status',
        'payment_status',
        'start_date',
        'end_date',
        'admin_notes',
        'partner_notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    // Relationships
    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function placements()
    {
        return $this->hasMany(AdPlacement::class);
    }

    public function sponsoredOpportunity()
    {
        return $this->hasOne(SponsoredOpportunity::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('payment_status', 'paid')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('ad_type', $type);
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && 
               $this->payment_status === 'paid' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date < now();
    }

    public function getRemainingDaysAttribute()
    {
        if ($this->end_date && $this->end_date > now()) {
            return now()->diffInDays($this->end_date);
        }
        return 0;
    }

    // Methods
    public function canBeActivated()
    {
        return $this->payment_status === 'paid' && $this->status === 'approved';
    }

    public function activate()
    {
        if (!$this->canBeActivated()) {
            return false;
        }

        $this->update([
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addDays($this->duration_value * ($this->duration_type === 'weekly' ? 7 : 1)),
        ]);

        return true;
    }

    public function deactivate()
    {
        $this->update(['status' => 'expired']);
    }

    public function approve()
    {
        $this->update(['status' => 'approved']);
    }

    public function reject($notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
        ]);
    }
} 