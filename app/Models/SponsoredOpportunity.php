<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsoredOpportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id',
        'partner_id',
        'status',
        'payment_status',
        'sponsored_from',
        'sponsored_to',
    ];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
} 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 