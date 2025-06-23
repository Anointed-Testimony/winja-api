<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointsSetting;
use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Models\UserPoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointsSettingsController extends Controller
{
    public function __construct()
    {
        // Remove any automatic authorization checks
    }

    /**
     * Get current points settings
     */
    public function getSettings()
    {
        $settings = PointsSetting::first();
        
        if (!$settings) {
            // Create default settings if none exist
            $settings = PointsSetting::create([
                'signup_points' => 100,
                'referral_points' => 50,
                'point_value_in_naira' => 1.00,
                'minimum_withdrawal' => 10.00,
            ]);
        }

        return response()->json($settings);
    }

    /**
     * Update points settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'signup_points' => 'required|integer|min:0',
            'referral_points' => 'required|integer|min:0',
            'point_value_in_naira' => 'required|numeric|min:0.01',
            'minimum_withdrawal' => 'required|numeric|min:0.01',
        ]);

        try {
            $settings = PointsSetting::first();
            
            if (!$settings) {
                $settings = new PointsSetting();
            }

            $settings->fill($request->all());
            $settings->save();

            return response()->json([
                'message' => 'Points settings updated successfully',
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update points settings: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update points settings'], 500);
        }
    }

    /**
     * Get withdrawal requests with filtering
     */
    public function getWithdrawalRequests(Request $request)
    {
        $query = WithdrawalRequest::with(['user:id,name,email,phone']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by amount range
        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Search by user name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($withdrawals);
    }

    /**
     * Get withdrawal request details
     */
    public function getWithdrawalDetails($id)
    {
        $withdrawal = WithdrawalRequest::with(['user:id,name,email,phone,created_at'])
            ->find($id);

        if (!$withdrawal) {
            return response()->json(['message' => 'Withdrawal request not found'], 404);
        }

        return response()->json($withdrawal);
    }

    /**
     * Approve withdrawal request
     */
    public function approveWithdrawal(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:500',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $withdrawal = WithdrawalRequest::find($id);
        if (!$withdrawal) {
            return response()->json(['message' => 'Withdrawal request not found'], 404);
        }

        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Withdrawal request is not pending'], 400);
        }

        try {
            DB::beginTransaction();

            $withdrawal->update([
                'status' => 'approved',
                'admin_notes' => $request->admin_notes,
                'payment_reference' => $request->payment_reference,
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Process payment (integrate with payment processor)
            $this->processPayment($withdrawal);

            DB::commit();

            return response()->json([
                'message' => 'Withdrawal request approved successfully',
                'withdrawal' => $withdrawal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal approval failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to approve withdrawal request'], 500);
        }
    }

    /**
     * Reject withdrawal request
     */
    public function rejectWithdrawal(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        $withdrawal = WithdrawalRequest::find($id);
        if (!$withdrawal) {
            return response()->json(['message' => 'Withdrawal request not found'], 404);
        }

        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Withdrawal request is not pending'], 400);
        }

        try {
            DB::beginTransaction();

            $withdrawal->update([
                'status' => 'rejected',
                'admin_notes' => $request->admin_notes,
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Refund points to user
            $user = User::find($withdrawal->user_id);
            if ($user) {
                $userPoints = $user->userPoints;
                if (!$userPoints) {
                    $userPoints = UserPoints::getOrCreateForUser($user->id);
                }
                $userPoints->increment('points_balance', $withdrawal->points_used);
                
                // Log the refund transaction
                $user->pointsTransactions()->create([
                    'type' => 'earned',
                    'amount' => $withdrawal->points_used,
                    'source' => 'withdrawal_refund',
                    'description' => 'Withdrawal request rejected - points refunded',
                    'metadata' => [
                        'withdrawal_id' => $withdrawal->id,
                        'refund_reason' => 'withdrawal_rejected'
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Withdrawal request rejected successfully',
                'withdrawal' => $withdrawal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal rejection failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reject withdrawal request'], 500);
        }
    }

    /**
     * Get withdrawal statistics
     */
    public function getWithdrawalStats()
    {
        $stats = [
            'total_pending' => WithdrawalRequest::where('status', 'pending')->count(),
            'total_approved' => WithdrawalRequest::where('status', 'approved')->count(),
            'total_rejected' => WithdrawalRequest::where('status', 'rejected')->count(),
            'total_amount_pending' => WithdrawalRequest::where('status', 'pending')->sum('amount'),
            'total_amount_approved' => WithdrawalRequest::where('status', 'approved')->sum('amount'),
            'total_amount_rejected' => WithdrawalRequest::where('status', 'rejected')->sum('amount'),
            'total_points_pending' => WithdrawalRequest::where('status', 'pending')->sum('points_used'),
            'total_points_approved' => WithdrawalRequest::where('status', 'approved')->sum('points_used'),
            'total_points_rejected' => WithdrawalRequest::where('status', 'rejected')->sum('points_used'),
        ];

        // Monthly trends
        $monthlyStats = WithdrawalRequest::selectRaw('
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            SUM(points_used) as total_points
        ')
        ->whereYear('created_at', date('Y'))
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->get();

        $stats['monthly_trends'] = $monthlyStats;

        return response()->json($stats);
    }

    /**
     * Get points system overview
     */
    public function getPointsOverview()
    {
        $overview = [
            'total_users_with_points' => User::whereHas('userPoints', function($q) {
                $q->where('points_balance', '>', 0);
            })->count(),
            'total_points_awarded' => DB::table('points_transactions')
                ->where('type', 'earned')
                ->sum('amount'),
            'total_points_redeemed' => DB::table('points_transactions')
                ->where('type', 'spent')
                ->sum('amount'),
            'total_points_balance' => DB::table('user_points')->sum('points_balance'),
            'total_signup_rewards' => DB::table('points_transactions')
                ->where('type', 'earned')
                ->where('source', 'signup')
                ->sum('amount'),
            'total_referral_rewards' => DB::table('points_transactions')
                ->where('type', 'earned')
                ->where('source', 'referral')
                ->sum('amount'),
        ];

        // Top users by points
        $topUsers = User::join('user_points', 'users.id', '=', 'user_points.user_id')
            ->where('user_points.points_balance', '>', 0)
            ->orderByDesc('user_points.points_balance')
            ->limit(10)
            ->get(['users.id', 'users.name', 'users.email']);

        $overview['top_users'] = $topUsers;

        return response()->json($overview);
    }

    /**
     * Process payment for approved withdrawal
     */
    private function processPayment($withdrawal)
    {
        // This is where you would integrate with your payment processor
        // Examples: Paystack, Flutterwave, PayPal, etc.
        
        switch ($withdrawal->payment_method) {
            case 'bank_transfer':
                $this->processBankTransfer($withdrawal);
                break;
                
            case 'paypal':
                $this->processPayPalPayment($withdrawal);
                break;
                
            case 'mobile_money':
                $this->processMobileMoney($withdrawal);
                break;
        }
    }

    private function processBankTransfer($withdrawal)
    {
        // Implement bank transfer logic
        // This would typically involve calling your bank's API
        Log::info('Processing bank transfer for withdrawal: ' . $withdrawal->id);
    }

    private function processPayPalPayment($withdrawal)
    {
        // Implement PayPal payment logic
        // This would involve calling PayPal's API
        Log::info('Processing PayPal payment for withdrawal: ' . $withdrawal->id);
    }

    private function processMobileMoney($withdrawal)
    {
        // Implement mobile money logic
        // This would involve calling mobile money provider's API
        Log::info('Processing mobile money for withdrawal: ' . $withdrawal->id);
    }
} 