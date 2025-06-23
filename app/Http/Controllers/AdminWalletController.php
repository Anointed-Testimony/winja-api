<?php

namespace App\Http\Controllers;

use App\Models\AdminWallet;
use App\Models\Transaction;
use App\Models\AdCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminWalletController extends Controller
{
    public function getBalance(Request $request)
    {
        try {
            $wallets = AdminWallet::all();
            
            return response()->json([
                'status' => 'success',
                'data' => $wallets,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get admin wallet balance: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get wallet balance',
            ], 500);
        }
    }

    public function getTransactionHistory(Request $request)
    {
        try {
            $query = Transaction::where('status', 'completed')
                ->with(['user', 'campaign.opportunity']);

            // Filter by transaction type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get transaction history: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get transaction history',
            ], 500);
        }
    }

    public function getWalletStats(Request $request)
    {
        try {
            $stats = [
                'total_balance' => AdminWallet::sum('balance'),
                'total_transactions' => Transaction::where('status', 'completed')->count(),
                'total_revenue' => Transaction::where('status', 'completed')->sum('amount'),
                
                // Revenue by type
                'subscription_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'subscription')
                    ->sum('amount'),
                'ad_campaign_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'ad_campaign')
                    ->sum('amount'),
                
                // Transaction counts by type
                'subscription_transactions' => Transaction::where('status', 'completed')
                    ->where('type', 'subscription')
                    ->count(),
                'ad_campaign_transactions' => Transaction::where('status', 'completed')
                    ->where('type', 'ad_campaign')
                    ->count(),
                
                // Recent transactions
                'recent_transactions' => Transaction::where('status', 'completed')
                    ->with(['user', 'campaign.opportunity'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
                
                // Ad campaign stats
                'active_campaigns' => AdCampaign::where('status', 'active')->count(),
                'pending_campaigns' => AdCampaign::where('status', 'pending')->count(),
                'total_campaigns' => AdCampaign::count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get wallet stats: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get wallet stats',
            ], 500);
        }
    }

    /**
     * Get detailed ad revenue analytics
     */
    public function getAdRevenueAnalytics(Request $request)
    {
        try {
            $analytics = [
                // Revenue by ad type
                'featured_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'ad_campaign')
                    ->whereHas('campaign', function($query) {
                        $query->where('ad_type', 'featured');
                    })
                    ->sum('amount'),
                
                'inline_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'ad_campaign')
                    ->whereHas('campaign', function($query) {
                        $query->where('ad_type', 'inline');
                    })
                    ->sum('amount'),
                
                // Revenue by duration type
                'daily_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'ad_campaign')
                    ->whereHas('campaign', function($query) {
                        $query->where('duration_type', 'daily');
                    })
                    ->sum('amount'),
                
                'weekly_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'ad_campaign')
                    ->whereHas('campaign', function($query) {
                        $query->where('duration_type', 'weekly');
                    })
                    ->sum('amount'),
                
                // Monthly revenue trend
                'monthly_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'ad_campaign')
                    ->whereYear('created_at', now()->year)
                    ->selectRaw('MONTH(created_at) as month, SUM(amount) as revenue')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
                
                // Top performing campaigns
                'top_campaigns' => AdCampaign::with(['opportunity', 'partner'])
                    ->where('payment_status', 'paid')
                    ->orderBy('amount_paid', 'desc')
                    ->limit(10)
                    ->get(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get ad revenue analytics: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get ad revenue analytics',
            ], 500);
        }
    }

    /**
     * Get revenue summary by date range
     */
    public function getRevenueSummary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $summary = [
                'period' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ],
                
                'total_revenue' => Transaction::where('status', 'completed')
                    ->whereBetween('created_at', [$request->start_date, $request->end_date])
                    ->sum('amount'),
                
                'subscription_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'subscription')
                    ->whereBetween('created_at', [$request->start_date, $request->end_date])
                    ->sum('amount'),
                
                'ad_campaign_revenue' => Transaction::where('status', 'completed')
                    ->where('type', 'ad_campaign')
                    ->whereBetween('created_at', [$request->start_date, $request->end_date])
                    ->sum('amount'),
                
                'daily_breakdown' => Transaction::where('status', 'completed')
                    ->whereBetween('created_at', [$request->start_date, $request->end_date])
                    ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue, type')
                    ->groupBy('date', 'type')
                    ->orderBy('date')
                    ->get(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get revenue summary: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get revenue summary',
            ], 500);
        }
    }
} 