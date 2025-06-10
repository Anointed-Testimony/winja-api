<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'type',
        'requirements',
        'points_value',
        'is_special',
    ];

    protected $casts = [
        'requirements' => 'array',
        'is_special' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('earned_at', 'metadata')
            ->withTimestamps();
    }

    public function checkRequirements(User $user)
    {
        if (empty($this->requirements)) {
            return true;
        }

        foreach ($this->requirements as $requirement) {
            if (!$this->checkRequirement($user, $requirement)) {
                return false;
            }
        }

        return true;
    }

    protected function checkRequirement(User $user, array $requirement)
    {
        $type = $requirement['type'] ?? null;
        $value = $requirement['value'] ?? null;

        switch ($type) {
            case 'referrals':
                return $user->referrals()->where('status', 'completed')->count() >= $value;
            case 'applications':
                return $user->applicationTrackers()->count() >= $value;
            case 'saved_opportunities':
                return $user->savedOpportunities()->count() >= $value;
            default:
                return false;
        }
    }
} 