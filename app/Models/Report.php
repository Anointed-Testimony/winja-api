<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reporter_id',
        'reason',
        'description',
        'status',
        'moderator_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // Polymorphic relationship for the reported item (listing, user, etc.)
    public function reportable()
    {
        return $this->morphTo();
    }

    // User who created the report
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    // Moderator who resolved the report
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Get all moderation actions related to this report
    public function actions()
    {
        return $this->morphMany(ModerationAction::class, 'actionable');
    }

    // Scope for pending reports
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope for resolved reports
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    // Mark report as resolved
    public function resolve($moderatorId, $notes = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $moderatorId,
            'resolved_at' => now(),
            'moderator_notes' => $notes,
        ]);
    }

    // Mark report as dismissed
    public function dismiss($moderatorId, $notes = null)
    {
        $this->update([
            'status' => 'dismissed',
            'resolved_by' => $moderatorId,
            'resolved_at' => now(),
            'moderator_notes' => $notes,
        ]);
    }
} 