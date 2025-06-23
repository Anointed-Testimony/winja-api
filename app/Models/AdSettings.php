<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_type',
        'duration_type',
        'price',
        'description',
        'is_active',
        'max_duration',
        'min_duration',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'max_duration' => 'integer',
        'min_duration' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('ad_type', $type);
    }

    public function scopeByDurationType($query, $durationType)
    {
        return $query->where('duration_type', $durationType);
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return '₦' . number_format($this->price, 2);
    }

    public function getDurationLabelAttribute()
    {
        return $this->duration_type === 'daily' ? 'Day' : 'Week';
    }

    public function getFullDescriptionAttribute()
    {
        return "₦{$this->price} per {$this->duration_label}";
    }

    // Methods
    public function calculateTotalPrice($duration)
    {
        return $this->price * $duration;
    }

    public function isDurationValid($duration)
    {
        if ($this->min_duration && $duration < $this->min_duration) {
            return false;
        }
        
        if ($this->max_duration && $duration > $this->max_duration) {
            return false;
        }
        
        return true;
    }

    public function getValidationRules()
    {
        $rules = ['integer|min:1'];
        
        if ($this->min_duration) {
            $rules[] = "min:{$this->min_duration}";
        }
        
        if ($this->max_duration) {
            $rules[] = "max:{$this->max_duration}";
        }
        
        return implode('|', $rules);
    }

    public function toggleActive()
    {
        $this->update(['is_active' => !$this->is_active]);
        return $this->is_active;
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->description = $duplicate->description . ' (Copy)';
        $duplicate->is_active = false;
        $duplicate->save();
        
        return $duplicate;
    }

    // Static methods
    public static function getPricingForType($adType)
    {
        return static::where('ad_type', $adType)
                    ->where('is_active', true)
                    ->orderBy('duration_type')
                    ->orderBy('price')
                    ->get();
    }

    public static function getActivePricing()
    {
        return static::where('is_active', true)
                    ->orderBy('ad_type')
                    ->orderBy('duration_type')
                    ->orderBy('price')
                    ->get();
    }

    public static function getDefaultPricing()
    {
        return [
            'featured' => [
                'daily' => 2000,
                'weekly' => 14000,
            ],
            'inline' => [
                'daily' => 2000,
                'weekly' => 14000,
            ]
        ];
    }
} 