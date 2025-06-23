<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\PremiumSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function getPlans()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return response()->json(['plans' => $plans]);
    }

    public function getUserStatus()
    {
        $user = Auth::user();
        $subscription = PremiumSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        return response()->json([
            'is_premium' => $user->is_premium,
            'premium_until' => $user->premium_until,
            'current_subscription' => $subscription
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'required|in:card,mpesa',
            'payment_details' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $user = Auth::user();

        // Create subscription record
        $subscription = PremiumSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'payment_details' => $request->payment_details,
            'start_date' => now(),
            'end_date' => now()->addMonths($plan->duration_months)
        ]);

        // TODO: Implement payment processing logic here
        // For now, we'll just mark it as active
        $subscription->update(['status' => 'active']);
        $user->update([
            'is_premium' => true,
            'premium_until' => $subscription->end_date
        ]);

        return response()->json([
            'message' => 'Subscription created successfully',
            'subscription' => $subscription
        ]);
    }

    public function cancel()
    {
        $user = Auth::user();
        $subscription = PremiumSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }

        $subscription->update(['status' => 'cancelled']);
        $user->update(['is_premium' => false]);

        return response()->json(['message' => 'Subscription cancelled successfully']);
    }
} 