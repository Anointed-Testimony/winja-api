<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Models\UserPoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
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
     * Admin: Get withdrawal request details
     */
    public function getWithdrawalDetailsAdmin($id)
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

        return response()->json($stats);
    }

    /**
     * Admin: Get all withdrawal requests
     */
    public function getAllWithdrawals(Request $request)
    {
        $query = WithdrawalRequest::with('user:id,name,email');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($withdrawals);
    }

    /**
     * Request a withdrawal (user method)
     */
    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:bank_transfer,paypal,mobile_money',
            'payment_details' => 'required|array',
        ]);

        $user = auth()->user();
        $settings = \App\Models\PointsSetting::getCurrent();
        
        // Calculate points required for this amount
        $pointsRequired = $settings->nairaToPoints($request->amount);
        
        // Check if user has enough points
        $userPoints = $user->userPoints;
        if (!$userPoints) {
            $userPoints = \App\Models\UserPoints::getOrCreateForUser($user->id);
        }
        
        if ($userPoints->points_balance < $pointsRequired) {
            return response()->json([
                'message' => 'Insufficient points balance for this withdrawal amount'
            ], 400);
        }
        
        // Check minimum withdrawal
        if ($request->amount < $settings->minimum_withdrawal) {
            return response()->json([
                'message' => "Minimum withdrawal amount is ₦{$settings->minimum_withdrawal}"
            ], 400);
        }
        
        try {
            DB::beginTransaction();

            // Deduct points from user's balance
            $userPoints->deductPoints($pointsRequired, 'withdrawal');

            // Create withdrawal request
            $withdrawalRequest = WithdrawalRequest::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'points_used' => $pointsRequired,
                'payment_method' => $request->payment_method,
                'payment_details' => $request->payment_details,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Withdrawal request submitted successfully',
                'withdrawal_request' => $withdrawalRequest
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal request failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to submit withdrawal request'], 500);
        }
    }

    /**
     * Get user's withdrawal requests
     */
    public function getUserWithdrawals()
    {
        $user = auth()->user();
        
        $withdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($withdrawals);
    }
} 