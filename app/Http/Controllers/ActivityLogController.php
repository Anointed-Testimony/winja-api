<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    // List recent activity logs
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')->latest()->limit(30)->get();
        return response()->json($logs);
    }

    // Create a new activity log
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'description' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
            'metadata' => 'nullable|array',
        ]);
        $log = ActivityLog::create($data);
        return response()->json($log);
    }
} 