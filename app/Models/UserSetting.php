<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'whatsapp_number',
        'email_notifications',
        'whatsapp_notifications',
        'opportunity_alerts',
        'marketing_emails'
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'whatsapp_notifications' => 'boolean',
        'opportunity_alerts' => 'boolean',
        'marketing_emails' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 