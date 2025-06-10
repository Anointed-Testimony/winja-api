<?php

namespace App\Http\Controllers;

use App\Models\ApplicationTracker;
use Illuminate\Http\Request;

class ApplicationTrackerController extends Controller
{
    public function index()
    {
        $applications = auth()->user()->applicationTrackers()
            ->with(['opportunity.type', 'opportunity.partner'])
            ->get();
        return response()->json($applications);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
            'status' => 'required|in:applied,shortlisted,rejected,accepted',
            'notes' => 'nullable|string',
            'application_link' => 'nullable|url',
        ]);

        $data['user_id'] = auth()->id();
        $data['applied_at'] = now();
        $data['status_updated_at'] = now();

        // Check if already applied
        $exists = ApplicationTracker::where('user_id', auth()->id())
            ->where('opportunity_id', $data['opportunity_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already applied to this opportunity'], 400);
        }

        $application = ApplicationTracker::create($data);
        return response()->json($application->load(['opportunity.type', 'opportunity.partner']), 201);
    }

    public function update(Request $request, ApplicationTracker $applicationTracker)
    {
        // Ensure user owns this application
        if ($applicationTracker->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:applied,shortlisted,rejected,accepted',
            'notes' => 'nullable|string',
            'application_link' => 'nullable|url',
        ]);

        $data['status_updated_at'] = now();

        $applicationTracker->update($data);
        return response()->json($applicationTracker->load(['opportunity.type', 'opportunity.partner']));
    }

    public function destroy(ApplicationTracker $applicationTracker)
    {
        // Ensure user owns this application
        if ($applicationTracker->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $applicationTracker->delete();
        return response()->json(['message' => 'Deleted']);
    }
} 