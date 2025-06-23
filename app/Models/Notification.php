<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'read_at',
        'data',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Get the user that owns the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for notifications by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for recent notifications (last 30 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Check if notification is read
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if notification is unread
     */
    public function isUnread()
    {
        return is_null($this->read_at);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
        return $this;
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
        return $this;
    }

    /**
     * Get notification data value
     */
    public function getData($key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Set notification data value
     */
    public function setData($key, $value)
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->update(['data' => $data]);
        return $this;
    }

    /**
     * Get related opportunity if this is an opportunity notification
     */
    public function opportunity()
    {
        if ($this->type === 'opportunity' && $this->getData('opportunity_id')) {
            return Opportunity::find($this->getData('opportunity_id'));
        }
        return null;
    }

    /**
     * Get related application if this is an application notification
     */
    public function application()
    {
        if ($this->type === 'application' && $this->getData('application_id')) {
            return ApplicationTracker::find($this->getData('application_id'));
        }
        return null;
    }

    /**
     * Get notification icon based on type
     */
    public function getIcon()
    {
        return match($this->type) {
            'opportunity' => 'work',
            'application' => 'description',
            'partner' => 'business',
            'system' => 'notifications',
            default => 'notifications'
        };
    }

    /**
     * Get notification color based on type
     */
    public function getColor()
    {
        return match($this->type) {
            'opportunity' => 'blue',
            'application' => 'orange',
            'partner' => 'green',
            'system' => 'purple',
            default => 'grey'
        };
    }
} 