<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\AdminWallet;
use App\Models\PremiumSubscription;
use App\Models\SubscriptionPlan;
use App\Models\AdCampaign;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    public function initializePayment(Request $request)
    {
        Log::info('Payment initialization request:', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'type' => 'required|string',
            'payment_method' => 'required|string',
            'campaign_id' => 'nullable|exists:ad_campaigns,id', // For ad campaign payments
        ]);

        try {
            DB::beginTransaction();

            // Generate unique reference
            $reference = 'TRX-' . strtoupper(Str::random(10));

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'transaction_id' => Str::uuid(),
                'type' => $request->type,
                'amount' => $request->amount,
                'currency' => 'NGN',
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'reference' => $reference,
                'description' => $request->description ?? null,
                'campaign_id' => $request->campaign_id ?? null, // Link to ad campaign if applicable
            ]);

            Log::info('Transaction record created:', ['transaction' => $transaction->toArray()]);

            // Initialize Paystack payment
            $paymentData = $this->paystackService->initializeTransaction([
                'amount' => $request->amount,
                'email' => auth()->user()->email,
                'reference' => $reference,
                'callback_url' => config('app.url') . '/api/paystack/webhook',
                'type' => $request->type,
                'user_id' => auth()->id(),
            ]);

            Log::info('Paystack payment initialized:', ['payment_data' => $paymentData]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment initialized successfully',
                'data' => [
                    'transaction' => $transaction,
                    'payment_data' => $paymentData['data'],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initialization failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initialize payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        Log::info('Payment verification request:', [
            'reference' => $request->reference,
            'all_params' => $request->all(),
        ]);

        $request->validate([
            'reference' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $transaction = Transaction::where('reference', $request->reference)->first();
            
            if (!$transaction) {
                Log::error('Transaction not found for reference: ' . $request->reference);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction not found',
                ], 404);
            }

            Log::info('Found transaction:', ['transaction' => $transaction->toArray()]);

            // Verify payment with Paystack
            $paymentData = $this->paystackService->verifyTransaction($request->reference);
            
            Log::info('Paystack verification response:', ['payment_data' => $paymentData]);

            if ($paymentData['data']['status'] === 'success') {
                $transaction->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'payment_details' => $paymentData['data'],
                ]);

                Log::info('Transaction updated to completed');

                // Update admin wallet
                $adminWallet = AdminWallet::firstOrCreate(
                    ['type' => $transaction->type],
                    ['balance' => 0, 'currency' => 'NGN']
                );

                $adminWallet->increment('balance', $transaction->amount);

                // Handle post-payment actions based on transaction type
                if ($transaction->type === 'subscription') {
                    $this->handleSubscriptionPayment($transaction);
                } elseif ($transaction->type === 'ad_campaign') {
                    $this->handleAdCampaignPayment($transaction);
                }

                DB::commit();

                Log::info('Payment verification completed successfully');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment verified successfully',
                    'data' => [
                        'transaction' => $transaction,
                        'subscription_updated' => $transaction->type === 'subscription',
                        'campaign_updated' => $transaction->type === 'ad_campaign',
                    ],
                ]);
            }

            DB::rollBack();
            Log::error('Payment verification failed - status not success');
            return response()->json([
                'status' => 'error',
                'message' => 'Payment verification failed - payment not successful',
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment verification failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle subscription payment verification
     */
    private function handleSubscriptionPayment($transaction)
    {
        Log::info('Processing subscription payment');
        
        // Find the subscription plan based on amount
        $plan = SubscriptionPlan::where('price', $transaction->amount)
            ->where('status', 'active')
            ->first();
        
        if ($plan) {
            Log::info('Found matching plan:', ['plan' => $plan->toArray()]);
            
            // Create or update premium subscription
            $subscription = PremiumSubscription::create([
                'user_id' => $transaction->user_id,
                'subscription_plan_id' => $plan->id,
                'plan_type' => $plan->duration,
                'amount_paid' => $transaction->amount,
                'status' => 'active',
                'payment_reference' => $transaction->reference,
                'start_date' => now(),
                'end_date' => now()->addMonths($plan->duration === 'monthly' ? 1 : ($plan->duration === 'quarterly' ? 3 : 12)),
            ]);

            Log::info('Premium subscription created:', ['subscription' => $subscription->toArray()]);

            // Update user premium status
            $user = $transaction->user;
            $user->update([
                'is_premium' => true,
                'premium_until' => $subscription->end_date,
            ]);

            Log::info('User premium status updated:', [
                'user_id' => $user->id,
                'is_premium' => $user->is_premium,
                'premium_until' => $user->premium_until,
            ]);
        } else {
            Log::error('No matching plan found for amount: ' . $transaction->amount);
        }
    }

    /**
     * Handle ad campaign payment verification
     */
    private function handleAdCampaignPayment($transaction)
    {
        Log::info('Processing ad campaign payment');
        
        if (!$transaction->campaign_id) {
            Log::error('No campaign ID associated with transaction');
            return;
        }

        $campaign = AdCampaign::find($transaction->campaign_id);
        
        if (!$campaign) {
            Log::error('Campaign not found for ID: ' . $transaction->campaign_id);
            return;
        }

        Log::info('Found campaign:', ['campaign' => $campaign->toArray()]);

        // Update campaign payment status
        $campaign->update([
            'payment_status' => 'paid',
            'status' => 'approved', // Auto-approve after payment
        ]);

        Log::info('Campaign payment status updated:', [
            'campaign_id' => $campaign->id,
            'payment_status' => $campaign->payment_status,
            'status' => $campaign->status,
        ]);

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

        Log::info('Sponsored opportunity created:', ['sponsored_opportunity' => $sponsoredOpportunity->toArray()]);
    }

    public function getUserTransactions(Request $request)
    {
        $transactions = Transaction::where('user_id', auth()->id())
            ->with(['campaign' => function($query) {
                $query->with('opportunity');
            }])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    public function getTransactionDetails($id)
    {
        $transaction = Transaction::where('user_id', auth()->id())
            ->with(['campaign' => function($query) {
                $query->with('opportunity');
            }])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $transaction,
        ]);
    }

    /**
     * Handle Paystack webhook
     */
    public function handleWebhook(Request $request)
    {
        Log::info('Paystack webhook received:', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        try {
            // Verify webhook signature
            $signature = $request->header('X-Paystack-Signature');
            $payload = $request->getContent();
            $expectedSignature = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

            if (!hash_equals($expectedSignature, $signature)) {
                Log::error('Invalid webhook signature');
                return response()->json(['message' => 'Invalid signature'], 400);
            }

            $event = $request->input('event');
            $data = $request->input('data');

            Log::info('Processing webhook event:', ['event' => $event, 'data' => $data]);

            if ($event === 'charge.success') {
                $reference = $data['reference'];
                
                // Find the transaction
                $transaction = Transaction::where('reference', $reference)->first();
                
                if (!$transaction) {
                    Log::error('Transaction not found for webhook reference: ' . $reference);
                    return response()->json(['message' => 'Transaction not found'], 404);
                }

                // Check if transaction is already completed
                if ($transaction->status === 'completed') {
                    Log::info('Transaction already completed, skipping webhook processing');
                    return response()->json(['message' => 'Transaction already processed']);
                }

                DB::beginTransaction();

                try {
                    // Update transaction status
                    $transaction->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'payment_details' => $data,
                    ]);

                    Log::info('Transaction updated to completed via webhook');

                    // Update admin wallet
                    $adminWallet = AdminWallet::firstOrCreate(
                        ['type' => $transaction->type],
                        ['balance' => 0, 'currency' => 'NGN']
                    );

                    $adminWallet->increment('balance', $transaction->amount);

                    // Handle post-payment actions based on transaction type
                    if ($transaction->type === 'subscription') {
                        $this->handleSubscriptionPayment($transaction);
                    } elseif ($transaction->type === 'ad_campaign') {
                        $this->handleAdCampaignPayment($transaction);
                    }

                    DB::commit();

                    Log::info('Webhook payment processing completed successfully');

                    return response()->json(['message' => 'Webhook processed successfully']);

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Webhook processing failed:', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }

            return response()->json(['message' => 'Event ignored']);

        } catch (\Exception $e) {
            Log::error('Webhook processing error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }
} 