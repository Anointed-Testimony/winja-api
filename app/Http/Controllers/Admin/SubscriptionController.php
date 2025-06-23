<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function getPlans()
    {
        $plans = SubscriptionPlan::all();
        return response()->json(['plans' => $plans]);
    }

    public function createPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'features' => 'required|array',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = SubscriptionPlan::create($request->all());
        return response()->json([
            'message' => 'Subscription plan created successfully',
            'plan' => $plan
        ]);
    }

    public function updatePlan(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
            'duration_months' => 'integer|min:1',
            'features' => 'array',
            'status' => 'in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update($request->all());

        return response()->json([
            'message' => 'Subscription plan updated successfully',
            'plan' => $plan
        ]);
    }

    public function getPremiumUsers()
    {
        $premiumUsers = User::where('is_premium', true)
            ->with(['premiumSubscription.plan'])
            ->paginate(20);

        return response()->json(['users' => $premiumUsers]);
    }
} 