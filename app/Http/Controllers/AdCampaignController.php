<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\AdSettings;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdCampaignController extends Controller
{
    /**
     * GET /ad-campaigns - List partner campaigns
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = AdCampaign::with(['opportunity', 'partner']);

        // Partners see only their campaigns, admins see all
        if ($user->user_type === 'partner') {
            $query->where('partner_id', $user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by ad type
        if ($request->has('ad_type')) {
            $query->where('ad_type', $request->ad_type);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $campaigns = $query->latest()->paginate(10);
        return response()->json($campaigns);
    }

    /**
     * POST /ad-campaigns - Create campaign
     */
    public function store(Request $request)
    {
        $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
            'ad_type' => 'required|in:featured,inline',
            'duration_type' => 'required|in:daily,weekly',
            'duration_value' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            
            // Verify user owns the opportunity
            $opportunity = Opportunity::where('id', $request->opportunity_id)
                ->where('partner_id', $user->id)
                ->first();

            if (!$opportunity) {
                return response()->json(['message' => 'Opportunity not found or not owned by you'], 404);
            }

            // Get pricing from settings
            $settings = AdSettings::where('ad_type', $request->ad_type)
                ->where('duration_type', $request->duration_type)
                ->where('is_active', true)
                ->first();

            if (!$settings) {
                return response()->json(['message' => 'Pricing not available for this ad type'], 400);
            }

            // Validate duration
            if (!$settings->isDurationValid($request->duration_value)) {
                return response()->json([
                    'message' => "Duration must be between {$settings->min_duration} and {$settings->max_duration} {$settings->duration_type}",
                ], 400);
            }

            $totalAmount = $settings->calculateTotalPrice($request->duration_value);

            // Create campaign
            $campaign = AdCampaign::create([
                'partner_id' => $user->id,
                'opportunity_id' => $request->opportunity_id,
                'ad_type' => $request->ad_type,
                'duration_type' => $request->duration_type,
                'duration_value' => $request->duration_value,
                'amount_paid' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Ad campaign created successfully',
                'campaign' => $campaign->load('opportunity'),
                'amount_to_pay' => $totalAmount,
                'pricing_details' => [
                    'price_per_unit' => $settings->price,
                    'duration_type' => $settings->duration_type,
                    'total_duration' => $request->duration_value,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ad campaign creation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create ad campaign'], 500);
        }
    }

    /**
     * GET /ad-campaigns/{id} - Campaign details
     */
    public function show($id)
    {
        $user = Auth::user();
        $query = AdCampaign::with(['opportunity', 'partner', 'placements']);

        if ($user->user_type === 'partner') {
            $query->where('partner_id', $user->id);
        }

        $campaign = $query->findOrFail($id);
        
        // Add placement stats
        $campaign->load(['placements' => function($query) {
            $query->selectRaw('
                ad_campaign_id,
                placement_type,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                CASE 
                    WHEN SUM(impressions) > 0 
                    THEN ROUND((SUM(clicks) / SUM(impressions)) * 100, 2)
                    ELSE 0 
                END as click_through_rate
            ')
            ->groupBy('ad_campaign_id', 'placement_type');
        }]);

        return response()->json($campaign);
    }

    /**
     * PUT /ad-campaigns/{id} - Update campaign
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $campaign = AdCampaign::where('id', $id);

        if ($user->user_type === 'partner') {
            $campaign = $campaign->where('partner_id', $user->id);
        }

        $campaign = $campaign->findOrFail($id);

        // Partners can only update certain fields
        if ($user->user_type === 'partner') {
            $request->validate([
                'partner_notes' => 'nullable|string', // for partner to add notes
            ]);

            $campaign->update($request->only(['partner_notes']));
        } else {
            // Admin can update status and other fields
            $request->validate([
                'status' => 'sometimes|in:pending,approved,rejected,active,expired',
                'payment_status' => 'sometimes|in:unpaid,paid',
                'admin_notes' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);

            $campaign->update($request->all());
        }

        return response()->json([
            'message' => 'Campaign updated successfully',
            'campaign' => $campaign->load('opportunity'),
        ]);
    }

    /**
     * DELETE /ad-campaigns/{id} - Delete campaign
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $campaign = AdCampaign::where('id', $id);

        if ($user->user_type === 'partner') {
            $campaign = $campaign->where('partner_id', $user->id);
        }

        $campaign = $campaign->findOrFail($id);

        // Only allow deletion if not paid or not active
        if ($campaign->payment_status === 'paid' || $campaign->status === 'active') {
            return response()->json(['message' => 'Cannot delete paid or active campaigns'], 400);
        }

        $campaign->delete();
        return response()->json(['message' => 'Campaign deleted successfully']);
    }

    /**
     * GET /ad-campaigns/pricing - Get current pricing
     */
    public function getPricing()
    {
        $pricing = AdSettings::getActivePricing();
        // Return as a plain array, not wrapped in {data: ...}
        return response()->json($pricing->values());
    }

    /**
     * POST /ad-campaigns/{id}/initialize-payment - Initialize payment for campaign
     */
    public function initializePayment(Request $request, $id)
    {
        $user = Auth::user();
        $campaign = AdCampaign::where('id', $id)
            ->where('partner_id', $user->id)
            ->findOrFail($id);

        if ($campaign->payment_status === 'paid') {
            return response()->json(['message' => 'Campaign is already paid'], 400);
        }

        if ($campaign->status !== 'pending') {
            return response()->json(['message' => 'Campaign is not in pending status'], 400);
        }

        try {
            // Create payment request
            $paymentRequest = [
                'amount' => $campaign->amount_paid, // Amount in naira, PaystackService will convert to kobo
                'type' => 'ad_campaign',
                'payment_method' => 'paystack',
                'description' => "Ad Campaign Payment - {$campaign->ad_type} ({$campaign->duration_value} {$campaign->duration_type})",
                'campaign_id' => $campaign->id,
            ];

            // Call transaction controller to initialize payment
            $transactionController = app(\App\Http\Controllers\TransactionController::class);
            $response = $transactionController->initializePayment(new \Illuminate\Http\Request($paymentRequest));

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to initialize ad campaign payment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to initialize payment'], 500);
        }
    }

    /**
     * POST /ad-campaigns/{id}/verify-payment - Manually verify payment for campaign
     */
    public function verifyPayment(Request $request, $id)
    {
        $user = Auth::user();
        $campaign = AdCampaign::where('id', $id)
            ->where('partner_id', $user->id)
            ->findOrFail($id);

        if ($campaign->payment_status === 'paid') {
            return response()->json(['message' => 'Campaign is already paid'], 400);
        }

        try {
            // Find the transaction for this campaign
            $transaction = \App\Models\Transaction::where('campaign_id', $campaign->id)
                ->where('type', 'ad_campaign')
                ->latest()
                ->first();

            if (!$transaction) {
                return response()->json(['message' => 'No transaction found for this campaign'], 404);
            }

            // Verify payment with Paystack
            $paystackService = app(\App\Services\PaystackService::class);
            $paymentData = $paystackService->verifyTransaction($transaction->reference);

            if ($paymentData['data']['status'] === 'success') {
                DB::beginTransaction();

                try {
                    // Update transaction status
                    $transaction->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'payment_details' => $paymentData['data'],
                    ]);

                    // Update campaign payment status
                    $campaign->update([
                        'payment_status' => 'paid',
                        'status' => 'approved',
                        'payment_reference' => $transaction->reference,
                        'payment_details' => $paymentData['data'],
                    ]);

                    // Update admin wallet
                    $adminWallet = \App\Models\AdminWallet::firstOrCreate(
                        ['type' => $transaction->type],
                        ['balance' => 0, 'currency' => 'NGN']
                    );

                    $adminWallet->increment('balance', $transaction->amount);

                    // Create sponsored opportunity record
                    $sponsoredOpportunity = \App\Models\SponsoredOpportunity::create([
                        'opportunity_id' => $campaign->opportunity_id,
                        'partner_id' => $campaign->partner_id,
                        'ad_campaign_id' => $campaign->id,
                        'status' => 'active',
                        'payment_status' => 'paid',
                        'sponsored_from' => now(),
                        'sponsored_to' => now()->addDays($campaign->duration_value * ($campaign->duration_type === 'weekly' ? 7 : 1)),
                    ]);

                    DB::commit();

                    return response()->json([
                        'message' => 'Payment verified successfully',
                        'campaign' => $campaign->load('opportunity'),
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                return response()->json(['message' => 'Payment not successful'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to verify ad campaign payment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to verify payment'], 500);
        }
    }

    /**
     * GET /admin/ad-campaigns - Admin view of all campaigns
     */
    public function adminIndex(Request $request)
    {
        $query = AdCampaign::with(['opportunity', 'partner']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by ad type
        if ($request->has('ad_type')) {
            $query->where('ad_type', $request->ad_type);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $campaigns = $query->latest()->paginate(20);
        
        return response()->json([
            'data' => $campaigns->items(),
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ]
        ]);
    }

    /**
     * POST /admin/ad-campaigns/{id}/approve - Approve campaign
     */
    public function approveCampaign($id)
    {
        $campaign = AdCampaign::findOrFail($id);
        
        if ($campaign->status !== 'pending') {
            return response()->json(['message' => 'Campaign is not pending approval'], 400);
        }

        $campaign->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Campaign approved successfully',
            'campaign' => $campaign->load('opportunity', 'partner'),
        ]);
    }

    /**
     * POST /admin/ad-campaigns/{id}/reject - Reject campaign
     */
    public function rejectCampaign($id)
    {
        $campaign = AdCampaign::findOrFail($id);
        
        if ($campaign->status !== 'pending') {
            return response()->json(['message' => 'Campaign is not pending approval'], 400);
        }

        $campaign->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return response()->json([
            'message' => 'Campaign rejected successfully',
            'campaign' => $campaign->load('opportunity', 'partner'),
        ]);
    }

    /**
     * GET /admin/ad-campaigns/{id}/stats - Get campaign statistics
     */
    public function getCampaignStats($id)
    {
        $campaign = AdCampaign::with(['placements'])->findOrFail($id);
        
        $stats = [
            'campaign' => $campaign,
            'total_impressions' => $campaign->placements->sum('impressions'),
            'total_clicks' => $campaign->placements->sum('clicks'),
            'click_through_rate' => $campaign->placements->sum('impressions') > 0 
                ? round(($campaign->placements->sum('clicks') / $campaign->placements->sum('impressions')) * 100, 2)
                : 0,
            'revenue' => $campaign->amount_paid,
            'placement_breakdown' => $campaign->placements->groupBy('placement_type')->map(function($placements) {
                return [
                    'impressions' => $placements->sum('impressions'),
                    'clicks' => $placements->sum('clicks'),
                    'ctr' => $placements->sum('impressions') > 0 
                        ? round(($placements->sum('clicks') / $placements->sum('impressions')) * 100, 2)
                        : 0,
                ];
            }),
        ];

        return response()->json($stats);
    }
}