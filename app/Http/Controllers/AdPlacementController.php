<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\AdPlacement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdPlacementController extends Controller
{
    /**
     * GET /ad-placements/featured - Get featured ads for home screen
     */
    public function getFeaturedAds()
    {
        try {
            $featuredAds = AdCampaign::with(['opportunity', 'partner'])
                ->where('ad_type', 'featured')
                ->where('status', 'active')
                ->where('payment_status', 'paid')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $featuredAds,
                'count' => $featuredAds->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get featured ads: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load featured ads'], 500);
        }
    }

    /**
     * GET /ad-placements/inline - Get inline ads for For You section
     */
    public function getInlineAds()
    {
        try {
            $inlineAds = AdCampaign::with(['opportunity', 'partner'])
                ->where('ad_type', 'inline')
                ->where('status', 'active')
                ->where('payment_status', 'paid')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $inlineAds,
                'count' => $inlineAds->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get inline ads: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load inline ads'], 500);
        }
    }

    /**
     * POST /ad-placements/approve/{id} - Admin approve campaign
     */
    public function approveCampaign($id)
    {
        try {
            $campaign = AdCampaign::findOrFail($id);
            
            if ($campaign->status !== 'pending') {
                return response()->json(['message' => 'Campaign is not in pending status'], 400);
            }

            $campaign->approve();

            return response()->json([
                'message' => 'Campaign approved successfully',
                'campaign' => $campaign->load('opportunity'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve campaign: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to approve campaign'], 500);
        }
    }

    /**
     * POST /ad-placements/reject/{id} - Admin reject campaign
     */
    public function rejectCampaign(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        try {
            $campaign = AdCampaign::findOrFail($id);
            
            if ($campaign->status !== 'pending') {
                return response()->json(['message' => 'Campaign is not in pending status'], 400);
            }

            $campaign->reject($request->admin_notes);

            return response()->json([
                'message' => 'Campaign rejected successfully',
                'campaign' => $campaign->load('opportunity'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reject campaign: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reject campaign'], 500);
        }
    }

    /**
     * POST /ad-placements/impression - Track impression
     */
    public function trackImpression(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:ad_campaigns,id',
            'placement_type' => 'required|in:featured,inline',
        ]);

        try {
            $placement = AdPlacement::updateOrCreate(
                [
                    'ad_campaign_id' => $request->campaign_id,
                    'placement_type' => $request->placement_type,
                ],
                [
                    'impressions' => DB::raw('impressions + 1'),
                    'last_displayed' => now(),
                ]
            );

            return response()->json(['message' => 'Impression tracked']);
        } catch (\Exception $e) {
            Log::error('Failed to track impression: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to track impression'], 500);
        }
    }

    /**
     * POST /ad-placements/click - Track click
     */
    public function trackClick(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:ad_campaigns,id',
            'placement_type' => 'required|in:featured,inline',
        ]);

        try {
            $placement = AdPlacement::updateOrCreate(
                [
                    'ad_campaign_id' => $request->campaign_id,
                    'placement_type' => $request->placement_type,
                ],
                [
                    'clicks' => DB::raw('clicks + 1'),
                    'last_displayed' => now(),
                ]
            );

            return response()->json(['message' => 'Click tracked']);
        } catch (\Exception $e) {
            Log::error('Failed to track click: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to track click'], 500);
        }
    }

    /**
     * GET /ad-placements/stats/{campaignId} - Get ad statistics
     */
    public function getAdStats($campaignId)
    {
        try {
            $stats = AdPlacement::where('ad_campaign_id', $campaignId)
                ->selectRaw('
                    placement_type,
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    CASE 
                        WHEN SUM(impressions) > 0 
                        THEN ROUND((SUM(clicks) / SUM(impressions)) * 100, 2)
                        ELSE 0 
                    END as click_through_rate
                ')
                ->groupBy('placement_type')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get ad stats: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load ad statistics'], 500);
        }
    }

    /**
     * POST /ad-placements/activate/{id} - Activate campaign
     */
    public function activateCampaign($id)
    {
        try {
            $campaign = AdCampaign::findOrFail($id);
            
            if ($campaign->payment_status !== 'paid') {
                return response()->json(['message' => 'Campaign must be paid before activation'], 400);
            }

            if ($campaign->status !== 'approved') {
                return response()->json(['message' => 'Campaign must be approved before activation'], 400);
            }

            $campaign->activate();

            return response()->json([
                'message' => 'Campaign activated successfully',
                'campaign' => $campaign->load('opportunity'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to activate campaign: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to activate campaign'], 500);
        }
    }

    /**
     * GET /admin/ad-analytics - Get admin analytics
     */
    public function getAdminAnalytics()
    {
        try {
            // Get overall statistics
            $totalCampaigns = AdCampaign::count();
            $activeCampaigns = AdCampaign::where('status', 'active')->count();
            $pendingCampaigns = AdCampaign::where('status', 'pending')->count();
            
            $totalImpressions = AdPlacement::sum('impressions');
            $totalClicks = AdPlacement::sum('clicks');
            $clickRate = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
            
            // Get impression growth (compare with last month)
            $lastMonthImpressions = AdPlacement::where('created_at', '>=', now()->subMonth())->sum('impressions');
            $twoMonthsAgoImpressions = AdPlacement::where('created_at', '>=', now()->subMonths(2))
                ->where('created_at', '<', now()->subMonth())
                ->sum('impressions');
            $impressionGrowth = $twoMonthsAgoImpressions > 0 
                ? round((($lastMonthImpressions - $twoMonthsAgoImpressions) / $twoMonthsAgoImpressions) * 100, 2)
                : 0;

            // Get campaign performance
            $campaignPerformance = AdCampaign::with(['placements'])
                ->where('status', 'active')
                ->get()
                ->map(function($campaign) {
                    $totalImpressions = $campaign->placements->sum('impressions');
                    $totalClicks = $campaign->placements->sum('clicks');
                    return [
                        'id' => $campaign->id,
                        'title' => $campaign->opportunity->title ?? 'N/A',
                        'impressions' => $totalImpressions,
                        'clicks' => $totalClicks,
                        'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
                    ];
                })
                ->sortByDesc('impressions')
                ->take(10);

            // Get ad type performance
            $featuredStats = AdPlacement::where('placement_type', 'featured')
                ->selectRaw('SUM(impressions) as impressions, SUM(clicks) as clicks')
                ->first();
            $inlineStats = AdPlacement::where('placement_type', 'inline')
                ->selectRaw('SUM(impressions) as impressions, SUM(clicks) as clicks')
                ->first();

            $featuredCTR = $featuredStats->impressions > 0 
                ? round(($featuredStats->clicks / $featuredStats->impressions) * 100, 2) 
                : 0;
            $inlineCTR = $inlineStats->impressions > 0 
                ? round(($inlineStats->clicks / $inlineStats->impressions) * 100, 2) 
                : 0;

            return response()->json([
                'data' => [
                    'activeCampaigns' => $activeCampaigns,
                    'pendingCampaigns' => $pendingCampaigns,
                    'totalImpressions' => $totalImpressions,
                    'totalClicks' => $totalClicks,
                    'clickRate' => $clickRate,
                    'impressionGrowth' => $impressionGrowth,
                    'campaignPerformance' => $campaignPerformance,
                    'featuredCTR' => $featuredCTR,
                    'inlineCTR' => $inlineCTR,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get admin analytics: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load analytics'], 500);
        }
    }

    /**
     * GET /admin/ad-revenue - Get admin revenue data
     */
    public function getAdminRevenue()
    {
        try {
            // Get total revenue
            $totalRevenue = AdCampaign::where('payment_status', 'paid')->sum('amount_paid');
            
            // Get revenue growth (compare with last month)
            $lastMonthRevenue = AdCampaign::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subMonth())
                ->sum('amount_paid');
            $twoMonthsAgoRevenue = AdCampaign::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subMonths(2))
                ->where('created_at', '<', now()->subMonth())
                ->sum('amount_paid');
            $revenueGrowth = $twoMonthsAgoRevenue > 0 
                ? round((($lastMonthRevenue - $twoMonthsAgoRevenue) / $twoMonthsAgoRevenue) * 100, 2)
                : 0;

            // Get revenue breakdown by period
            $revenueBreakdown = collect([
                'This Month' => AdCampaign::where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->sum('amount_paid'),
                'Last Month' => AdCampaign::where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->subMonth()->startOfMonth())
                    ->where('created_at', '<', now()->startOfMonth())
                    ->sum('amount_paid'),
                'This Quarter' => AdCampaign::where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->startOfQuarter())
                    ->sum('amount_paid'),
                'This Year' => AdCampaign::where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->startOfYear())
                    ->sum('amount_paid'),
            ])->map(function($amount, $period) {
                return [
                    'period' => $period,
                    'featured' => 0, // Placeholder - would need separate tracking
                    'inline' => 0,   // Placeholder - would need separate tracking
                    'total' => $amount,
                    'growth' => 0,   // Placeholder - would need historical comparison
                ];
            })->values();

            return response()->json([
                'data' => [
                    'total' => $totalRevenue,
                    'growth' => $revenueGrowth,
                    'breakdown' => $revenueBreakdown,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get admin revenue: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load revenue data'], 500);
        }
    }
} 