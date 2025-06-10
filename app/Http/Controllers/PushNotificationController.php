<?php

namespace App\Http\Controllers;

use App\Models\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushNotificationController extends Controller
{
    // List all notifications (optionally filter by status)
    public function index(Request $request)
    {
        $query = PushNotification::query();
        if ($request->status) {
            $query->where('status', $request->status);
        }
        return response()->json($query->latest()->get());
    }

    // Create a new notification
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'message' => 'required|string',
            'scheduled_at' => 'nullable|date',
            'type' => 'nullable|string',
            'recipients' => 'nullable|array',
        ]);
        $data['created_by'] = Auth::id();
        $notification = PushNotification::create($data);
        // (Optional) Dispatch job to send notification if scheduled_at is now or null
        return response()->json($notification);
    }

    // Update a notification
    public function update(Request $request, $id)
    {
        $notification = PushNotification::findOrFail($id);
        $notification->update($request->only(['title', 'message', 'scheduled_at', 'type', 'recipients', 'status']));
        return response()->json($notification);
    }

    // Delete a notification
    public function destroy($id)
    {
        $notification = PushNotification::findOrFail($id);
        $notification->delete();
        return response()->json(['message' => 'Notification deleted']);
    }
} 