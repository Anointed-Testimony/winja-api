<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Opportunity extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'sponsor',
        'description',
        'eligibility',
        'status',
        'expiry',
        'verified',
        'opportunity_type_id',
        'image',
        'created_by',
        'partner_id',
        'application_link',
        'view_count',
        'click_count',
        'save_count',
        'application_count',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'click_count' => 'integer',
        'save_count' => 'integer',
        'application_count' => 'integer',
        'verified' => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(OpportunityType::class, 'opportunity_type_id');
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
} 