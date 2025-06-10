<?php

namespace App\Http\Controllers;

use App\Models\SavedOpportunity;
use Illuminate\Http\Request;

class SavedOpportunityController extends Controller
{
    public function index()
    {
        $saved = auth()->user()->savedOpportunities()
            ->with(['opportunity.type', 'opportunity.partner'])
            ->get();
        return response()->json($saved);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
            'reminder_set' => 'boolean',
            'reminder_date' => 'nullable|date|after:now',
        ]);

        $data['user_id'] = auth()->id();

        // Check if already saved
        $exists = SavedOpportunity::where('user_id', auth()->id())
            ->where('opportunity_id', $data['opportunity_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Opportunity already saved'], 400);
        }

        $saved = SavedOpportunity::create($data);
        return response()->json($saved->load(['opportunity.type', 'opportunity.partner']), 201);
    }

    public function update(Request $request, SavedOpportunity $savedOpportunity)
    {
        // Ensure user owns this saved opportunity
        if ($savedOpportunity->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'reminder_set' => 'boolean',
            'reminder_date' => 'nullable|date|after:now',
        ]);

        $savedOpportunity->update($data);
        return response()->json($savedOpportunity->load(['opportunity.type', 'opportunity.partner']));
    }

    public function destroy(SavedOpportunity $savedOpportunity)
    {
        // Ensure user owns this saved opportunity
        if ($savedOpportunity->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $savedOpportunity->delete();
        return response()->json(['message' => 'Deleted']);
    }
} 