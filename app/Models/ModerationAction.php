<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModerationAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'moderator_id',
        'action_type',
        'reason',
        'notes',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    // Polymorphic relationship for the item being moderated (listing, user, etc.)
    public function actionable()
    {
        return $this->morphTo();
    }

    // Moderator who performed the action
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    // Scope for active actions (not expired)
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Scope for expired actions
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    // Check if action is expired
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // Get the related report if any
    public function report()
    {
        return $this->morphOne(Report::class, 'reportable');
    }
} 