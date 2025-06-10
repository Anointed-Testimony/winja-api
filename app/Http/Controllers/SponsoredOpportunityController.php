<?php

namespace App\Http\Controllers;

use App\Models\SponsoredOpportunity;
use Illuminate\Http\Request;

class SponsoredOpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = SponsoredOpportunity::with(['opportunity', 'partner']);
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
            'status' => 'required|in:pending,approved,rejected',
            'payment_status' => 'required|in:unpaid,paid',
            'sponsored_from' => 'nullable|date',
            'sponsored_to' => 'nullable|date',
        ]);
        if (isset($data['sponsored_from'])) {
            $data['sponsored_from'] = date('Y-m-d H:i:s', strtotime($data['sponsored_from']));
        }
        if (isset($data['sponsored_to'])) {
            $data['sponsored_to'] = date('Y-m-d H:i:s', strtotime($data['sponsored_to']));
        }
        $sponsored = SponsoredOpportunity::create($data);
        return response()->json($sponsored, 201);
    }

    public function show($id)
    {
        // Show details of a sponsored opportunity
    }

    public function update(Request $request, $id)
    {
        // Update status/payment/etc.
    }

    public function destroy($id)
    {
        $sponsored = SponsoredOpportunity::findOrFail($id);
        $sponsored->delete();
        return response()->json(['message' => 'Sponsored opportunity removed']);
    }
} 