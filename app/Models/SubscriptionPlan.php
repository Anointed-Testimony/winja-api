<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'duration',
        'features',
        'status'
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2'
    ];

    public function subscriptions()
    {
        return $this->hasMany(PremiumSubscription::class, 'subscription_plan_id');
    }
} 