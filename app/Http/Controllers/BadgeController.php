<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function index()
    {
        $badges = Badge::with(['users' => function ($query) {
            $query->where('users.id', auth()->id());
        }])->get();

        return response()->json([
            'badges' => $badges,
            'earned_badges' => auth()->user()->badges,
            'total_points' => auth()->user()->badges()->sum('points_value')
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'required|string',
            'type' => 'required|in:referral,achievement,special',
            'requirements' => 'nullable|array',
            'points_value' => 'required|integer|min:0',
            'is_special' => 'boolean',
        ]);

        $badge = Badge::create($data);

        return response()->json($badge, 201);
    }

    public function show(Badge $badge)
    {
        $badge->load(['users' => function ($query) {
            $query->where('users.id', auth()->id());
        }]);

        return response()->json($badge);
    }

    public function update(Request $request, Badge $badge)
    {
        $data = $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'icon' => 'string',
            'type' => 'in:referral,achievement,special',
            'requirements' => 'nullable|array',
            'points_value' => 'integer|min:0',
            'is_special' => 'boolean',
        ]);

        $badge->update($data);

        return response()->json($badge);
    }

    public function destroy(Badge $badge)
    {
        $badge->delete();
        return response()->json(['message' => 'Badge deleted']);
    }

    public function checkEligibility()
    {
        auth()->user()->checkAndAwardBadges();
        
        return response()->json([
            'badges' => auth()->user()->badges,
            'total_points' => auth()->user()->badges()->sum('points_value')
        ]);
    }
} 