<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ModerationAction;
use App\Models\User;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModerationController extends Controller
{
    // Get all reports with pagination and filters
    public function getReports(Request $request)
    {
        $query = Report::with(['reporter', 'resolver', 'reportable'])
            ->when($request->status, function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->type, function ($q) use ($request) {
                return $q->where('reportable_type', $request->type);
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                    $query->where('reason', 'like', "%{$request->search}%")
                        ->orWhere('description', 'like', "%{$request->search}%");
                });
            })
            ->latest();

        return response()->json([
            'reports' => $query->paginate(15),
            'stats' => [
                'total' => Report::count(),
                'pending' => Report::where('status', 'pending')->count(),
                'resolved' => Report::where('status', 'resolved')->count(),
                'dismissed' => Report::where('status', 'dismissed')->count(),
            ]
        ]);
    }

    // Get report details
    public function getReportDetails($id)
    {
        $report = Report::with(['reporter', 'resolver', 'reportable', 'actions'])
            ->findOrFail($id);

        return response()->json($report);
    }

    // Take moderation action
    public function takeAction(Request $request, $id)
    {
        $request->validate([
            'action_type' => 'required|in:warn,remove,ban,restore,dismiss',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $report = Report::findOrFail($id);

        DB::transaction(function () use ($request, $report) {
            // Create moderation action
            $action = ModerationAction::create([
                'moderator_id' => Auth::id(),
                'action_type' => $request->action_type,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'metadata' => $request->metadata,
                'expires_at' => $request->expires_at,
            ]);

            // Handle different action types
            switch ($request->action_type) {
                case 'warn':
                    // Send warning notification
                    break;
                case 'remove':
                    if ($report->reportable_type === Opportunity::class) {
                        $report->reportable->update(['status' => 'removed']);
                    }
                    break;
                case 'ban':
                    if ($report->reportable_type === User::class) {
                        $report->reportable->update(['status' => 'banned']);
                    }
                    break;
                case 'restore':
                    if ($report->reportable_type === Opportunity::class) {
                        $report->reportable->update(['status' => 'active']);
                    } elseif ($report->reportable_type === User::class) {
                        $report->reportable->update(['status' => 'active']);
                    }
                    break;
            }

            // Update report status
            $report->resolve(Auth::id(), $request->notes);
        });

        return response()->json(['message' => 'Action taken successfully']);
    }

    // Get moderation statistics
    public function getStats()
    {
        $stats = [
            'reports' => [
                'total' => Report::count(),
                'pending' => Report::where('status', 'pending')->count(),
                'resolved' => Report::where('status', 'resolved')->count(),
                'dismissed' => Report::where('status', 'dismissed')->count(),
            ],
            'actions' => [
                'total' => ModerationAction::count(),
                'warnings' => ModerationAction::where('action_type', 'warn')->count(),
                'removals' => ModerationAction::where('action_type', 'remove')->count(),
                'bans' => ModerationAction::where('action_type', 'ban')->count(),
            ],
            'recent_activity' => ModerationAction::with('moderator')
                ->latest()
                ->take(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    // Get auto-flagged content
    public function getAutoFlagged()
    {
        // This would typically come from your AI/ML system
        // For now, we'll return a placeholder
        return response()->json([
            'message' => 'Auto-flagging system not implemented yet'
        ]);
    }

    // Get user moderation history
    public function getUserHistory($userId)
    {
        $user = User::findOrFail($userId);
        
        $history = [
            'reports_received' => Report::where('reportable_type', User::class)
                ->where('reportable_id', $userId)
                ->with('reporter')
                ->get(),
            'reports_made' => Report::where('reporter_id', $userId)
                ->with('reportable')
                ->get(),
            'moderation_actions' => ModerationAction::where('actionable_type', User::class)
                ->where('actionable_id', $userId)
                ->with('moderator')
                ->get(),
        ];

        return response()->json($history);
    }
} 