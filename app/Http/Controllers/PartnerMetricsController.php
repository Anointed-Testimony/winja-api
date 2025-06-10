<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\SavedOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartnerMetricsController extends Controller
{
    public function getMetrics()
    {
        $partner = Auth::user();
        
        if (!$partner || $partner->user_type !== 'partner') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get total opportunities count
        $opportunitiesCount = Opportunity::where('partner_id', $partner->id)->count();

        // Get total clicks (views) on opportunities (temporarily set to 0)
        $totalClicks = 0;

        // Get total applications (saved opportunities)
        $totalApplications = SavedOpportunity::whereHas('opportunity', function ($query) use ($partner) {
            $query->where('partner_id', $partner->id);
        })->count();

        // Get recent activity (last 5 activities)
        $recentActivity = $this->getRecentActivity($partner->id);

        return response()->json([
            'opportunities_count' => $opportunitiesCount,
            'total_clicks' => $totalClicks,
            'total_applications' => $totalApplications,
            'recent_activity' => $recentActivity
        ]);
    }

    private function getRecentActivity($partnerId)
    {
        // Get recent opportunities created
        $recentOpportunities = Opportunity::where('partner_id', $partnerId)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($opportunity) {
                return [
                    'type' => 'opportunity_created',
                    'title' => $opportunity->title,
                    'time' => $opportunity->created_at->diffForHumans()
                ];
            });

        // Get recent applications
        $recentApplications = SavedOpportunity::whereHas('opportunity', function ($query) use ($partnerId) {
            $query->where('partner_id', $partnerId);
        })
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->get()
        ->map(function ($saved) {
            return [
                'type' => 'application_received',
                'title' => $saved->opportunity->title,
                'time' => $saved->created_at->diffForHumans()
            ];
        });

        // Combine and sort by time
        $activities = $recentOpportunities->concat($recentApplications)
            ->sortByDesc(function ($activity) {
                return $activity['time'];
            })
            ->take(5)
            ->values();

        return $activities;
    }
} 