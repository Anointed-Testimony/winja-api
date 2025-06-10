<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    public function index()
    {
        $referrals = auth()->user()->referrals()
            ->with(['referred'])
            ->latest()
            ->get();

        return response()->json([
            'referrals' => $referrals,
            'stats' => auth()->user()->getReferralStats()
        ]);
    }

    public function getStats()
    {
        $user = auth()->user();
        return response()->json([
            'sent_referrals' => $user->referrals()->count(),
            'joined_referrals' => $user->referrals()->where('status', 'completed')->count(),
            'applied_referrals' => $user->referrals()
                ->whereHas('referred.applicationTrackers')
                ->count(),
            'referral_code' => $user->referral_code,
            'total_points' => $user->badges()->sum('points_value'),
            'leaderboard_rank' => $this->getLeaderboardRank($user),
            'referral_progress' => $user->getReferralProgress(),
            'recent_referrals' => $user->referrals()
                ->with(['referred'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    private function getLeaderboardRank($user)
    {
        // Get all users with completed referrals
        $usersWithReferrals = User::whereHas('referrals', function($query) {
            $query->where('status', 'completed');
        })
        ->withCount(['referrals' => function($query) {
            $query->where('status', 'completed');
        }])
        ->orderByDesc('referrals_count')
        ->get();

        // If user has no completed referrals, they should be at the bottom
        if (!$usersWithReferrals->contains('id', $user->id)) {
            return $usersWithReferrals->count() + 1;
        }

        // Find user's position in the sorted list
        return $usersWithReferrals->search(function($item) use ($user) {
            return $item->id === $user->id;
        }) + 1;
    }

    public function leaderboard()
    {
        $leaderboard = User::whereHas('referrals', function($query) {
            $query->where('status', 'completed');
        })
        ->withCount(['referrals' => function($query) {
            $query->where('status', 'completed');
        }])
        ->withSum('badges', 'points_value')
        ->orderByDesc('referrals_count')
        ->take(10)
        ->get()
        ->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'referrals_count' => $user->referrals_count,
                'total_points' => $user->badges_sum_points_value ?? 0,
                'profile_image' => $user->profile_image,
            ];
        });

        return response()->json([
            'leaderboard' => $leaderboard,
            'user_rank' => $this->getLeaderboardRank(auth()->user()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
        ]);

        // Generate a unique referral code for the new user
        $referralCode = Str::random(8);
        while (User::where('referral_code', $referralCode)->exists()) {
            $referralCode = Str::random(8);
        }

        // Create the new user
        $referredUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'referral_code' => $referralCode,
            'referred_by' => auth()->user()->referral_code,
        ]);

        // Create the referral record
        $referral = Referral::create([
            'referrer_id' => auth()->id(),
            'referred_id' => $referredUser->id,
            'status' => 'pending',
        ]);

        // Check for new badges
        auth()->user()->checkAndAwardBadges();

        return response()->json([
            'message' => 'Referral created successfully',
            'referral' => $referral->load('referred'),
            'stats' => auth()->user()->getReferralStats()
        ], 201);
    }

    public function complete(Referral $referral)
    {
        if ($referral->referrer_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $referral->complete();

        // Check for new badges after completing referral
        auth()->user()->checkAndAwardBadges();

        return response()->json([
            'message' => 'Referral marked as completed',
            'referral' => $referral->fresh(['referred']),
            'stats' => auth()->user()->getReferralStats()
        ]);
    }
} 