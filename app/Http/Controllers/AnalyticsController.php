<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Referral;
use App\Models\Opportunity;
use App\Models\OpportunityType;
use App\Models\SponsoredOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    // User Engagement Analytics
    public function userEngagement()
    {
        $activeUsers = User::where('status', 'active')->count();
        $referrals = Referral::where('status', 'completed')->count();
        // For demo: notifications opened is a mock (replace with real logic if available)
        $notificationsOpened = 3200;
        return response()->json([
            'active_users' => $activeUsers,
            'referrals' => $referrals,
            'notifications_opened' => $notificationsOpened,
        ]);
    }

    // Revenue Analytics
    public function revenue()
    {
        $sponsored = SponsoredOpportunity::where('payment_status', 'paid')->count();
        $affiliate = 600; // Mock value, replace with real affiliate logic
        $premium = User::where('is_premium', true)->count();
        $total = $sponsored + $affiliate + $premium;
        return response()->json([
            'sponsored' => $sponsored,
            'affiliate' => $affiliate,
            'premium' => $premium,
            'total' => $total,
        ]);
    }

    // Trends Analytics
    public function trends()
    {
        // Popular categories (opportunity types)
        $categories = OpportunityType::withCount('opportunities')
            ->orderBy('opportunities_count', 'desc')
            ->take(5)
            ->get()
            ->map(function($type) {
                return [
                    'name' => $type->name,
                    'value' => $type->opportunities_count,
                ];
            });
        // Top locations (geo_location on users)
        $locations = User::select('geo_location', DB::raw('count(*) as total'))
            ->whereNotNull('geo_location')
            ->groupBy('geo_location')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function($row) {
                return [
                    'name' => $row->geo_location,
                    'value' => $row->total,
                ];
            });
        // Seasonal trends (opportunities created per month)
        $seasonal = Opportunity::select(DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function($row) {
                return [
                    'month' => $row->month,
                    'value' => $row->total,
                ];
            });
        return response()->json([
            'categories' => $categories,
            'locations' => $locations,
            'seasonal' => $seasonal,
        ]);
    }
} 