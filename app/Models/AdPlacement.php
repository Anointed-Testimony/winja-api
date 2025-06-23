<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdPlacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_campaign_id',
        'placement_type',
        'impressions',
        'clicks',
        'last_displayed',
    ];

    protected $casts = [
        'last_displayed' => 'datetime',
        'impressions' => 'integer',
        'clicks' => 'integer',
    ];

    // Relationships
    public function campaign()
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('placement_type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('last_displayed', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('last_displayed', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('last_displayed', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    // Accessors
    public function getClickThroughRateAttribute()
    {
        if ($this->impressions > 0) {
            return round(($this->clicks / $this->impressions) * 100, 2);
        }
        return 0;
    }

    public function getFormattedImpressionsAttribute()
    {
        return number_format($this->impressions);
    }

    public function getFormattedClicksAttribute()
    {
        return number_format($this->clicks);
    }

    // Methods
    public function incrementImpression()
    {
        $this->increment('impressions');
        $this->update(['last_displayed' => now()]);
    }

    public function incrementClick()
    {
        $this->increment('clicks');
        $this->update(['last_displayed' => now()]);
    }

    public function resetMetrics()
    {
        $this->update([
            'impressions' => 0,
            'clicks' => 0,
        ]);
    }

    public function getPerformanceScore()
    {
        $ctr = $this->click_through_rate;
        $impressions = $this->impressions;
        
        // Simple scoring algorithm
        $score = 0;
        
        if ($ctr > 5) $score += 30;
        elseif ($ctr > 2) $score += 20;
        elseif ($ctr > 1) $score += 10;
        
        if ($impressions > 1000) $score += 40;
        elseif ($impressions > 500) $score += 30;
        elseif ($impressions > 100) $score += 20;
        elseif ($impressions > 10) $score += 10;
        
        return min($score, 100);
    }
} 