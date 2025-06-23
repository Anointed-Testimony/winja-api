<?php

namespace App\Http\Controllers;

use App\Models\SponsoredOpportunity;
use Illuminate\Http\Request;

class SponsoredOpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = SponsoredOpportunity::with(['opportunity', 'partner', 'adCampaign']);
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
            'partner_id' => 'nullable|exists:users,id',
            'ad_campaign_id' => 'nullable|exists:ad_campaigns,id',
            'status' => 'required|in:pending,approved,rejected,active',
            'payment_status' => 'required|in:unpaid,paid',
            'sponsored_from' => 'nullable|date',
            'sponsored_to' => 'nullable|date',
        ]);
        
        // Check if opportunity is already sponsored
        $existing = SponsoredOpportunity::where('opportunity_id', $data['opportunity_id'])->first();
        if ($existing) {
            return response()->json(['message' => 'Opportunity is already sponsored'], 400);
        }
        
        if (isset($data['sponsored_from'])) {
            $data['sponsored_from'] = date('Y-m-d H:i:s', strtotime($data['sponsored_from']));
        }
        if (isset($data['sponsored_to'])) {
            $data['sponsored_to'] = date('Y-m-d H:i:s', strtotime($data['sponsored_to']));
        }
        $sponsored = SponsoredOpportunity::create($data);
        return response()->json($sponsored->load(['opportunity', 'partner', 'adCampaign']), 201);
    }

    public function show($id)
    {
        $sponsored = SponsoredOpportunity::with(['opportunity', 'partner', 'adCampaign'])->findOrFail($id);
        return response()->json($sponsored);
    }

    public function update(Request $request, $id)
    {
        $sponsored = SponsoredOpportunity::findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|in:pending,approved,rejected,active,expired',
            'payment_status' => 'sometimes|in:unpaid,paid',
            'sponsored_from' => 'nullable|date',
            'sponsored_to' => 'nullable|date',
        ]);
        
        if (isset($data['sponsored_from'])) {
            $data['sponsored_from'] = date('Y-m-d H:i:s', strtotime($data['sponsored_from']));
        }
        if (isset($data['sponsored_to'])) {
            $data['sponsored_to'] = date('Y-m-d H:i:s', strtotime($data['sponsored_to']));
        }
        
        $sponsored->update($data);
        return response()->json([
            'message' => 'Sponsored opportunity updated successfully',
            'sponsored' => $sponsored->load(['opportunity', 'partner', 'adCampaign'])
        ]);
    }

    public function destroy($id)
    {
        $sponsored = SponsoredOpportunity::findOrFail($id);
        $sponsored->delete();
        return response()->json(['message' => 'Sponsored opportunity removed']);
    }
} 