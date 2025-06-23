<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get user's notifications with pagination
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->notifications();
        
        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->byType($request->type);
        }
        
        // Filter by read status
        if ($request->has('read')) {
            if ($request->read === 'true') {
                $query->read();
            } else {
                $query->unread();
            }
        }
        
        // Get notifications with pagination
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));
            
        return response()->json($notifications);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $user->notifications()->unread()->count();
        
        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        
        $notification->markAsRead();
        
        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        $user->notifications()
            ->unread()
            ->update(['read_at' => now()]);
        
        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        
        $notification->delete();
        
        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function deleteRead()
    {
        $user = Auth::user();
        
        $deletedCount = $user->notifications()
            ->read()
            ->delete();
        
        return response()->json([
            'message' => "{$deletedCount} read notifications deleted"
        ]);
    }

    /**
     * Get notification statistics
     */
    public function stats()
    {
        $user = Auth::user();
        
        $stats = [
            'total' => $user->notifications()->count(),
            'unread' => $user->notifications()->unread()->count(),
            'read' => $user->notifications()->read()->count(),
            'by_type' => [
                'opportunity' => $user->notifications()->byType('opportunity')->count(),
                'application' => $user->notifications()->byType('application')->count(),
                'system' => $user->notifications()->byType('system')->count(),
                'partner' => $user->notifications()->byType('partner')->count(),
            ],
            'recent' => $user->notifications()->recent()->count(),
        ];
        
        return response()->json($stats);
    }

    /**
     * Get notification details
     */
    public function show($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        
        // Mark as read when viewed
        if ($notification->isUnread()) {
            $notification->markAsRead();
        }
        
        return response()->json($notification);
    }

    /**
     * Get notification statistics (alias for stats method)
     */
    public function getStats()
    {
        return $this->stats();
    }

    /**
     * Admin: Get all notifications with pagination
     */
    public function adminIndex(Request $request)
    {
        $query = Notification::with('user');
        
        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->byType($request->type);
        }
        
        // Filter by read status
        if ($request->has('read')) {
            if ($request->read === 'true') {
                $query->read();
            } else {
                $query->unread();
            }
        }
        
        // Get notifications with pagination
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));
            
        return response()->json($notifications);
    }

    /**
     * Admin: Get notification details
     */
    public function adminShow($id)
    {
        $notification = Notification::with('user')->findOrFail($id);
        
        return response()->json($notification);
    }

    /**
     * Admin: Delete a notification
     */
    public function adminDestroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();
        
        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Admin: Send system notification to all users or specific users
     */
    public function sendSystemNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $userIds = $request->user_ids ?? [];
        
        if (empty($userIds)) {
            // Send to all users
            $users = \App\Models\User::all();
        } else {
            // Send to specific users
            $users = \App\Models\User::whereIn('id', $userIds)->get();
        }

        $notificationService = app(\App\Services\NotificationService::class);
        
        foreach ($users as $user) {
            $notificationService->sendSystemNotification(
                $user->id,
                $request->title,
                $request->message,
                $request->get('data', [])
            );
        }

        return response()->json([
            'message' => 'System notification sent successfully',
            'recipients_count' => $users->count()
        ]);
    }

    /**
     * Admin: Get notification analytics
     */
    public function getAnalytics()
    {
        $analytics = [
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::unread()->count(),
            'read_notifications' => Notification::read()->count(),
            'by_type' => [
                'opportunity' => Notification::byType('opportunity')->count(),
                'application' => Notification::byType('application')->count(),
                'system' => Notification::byType('system')->count(),
                'partner' => Notification::byType('partner')->count(),
            ],
            'recent_notifications' => Notification::recent()->count(),
            'users_with_notifications' => Notification::distinct('user_id')->count(),
            'average_notifications_per_user' => Notification::count() / max(\App\Models\User::count(), 1),
            'notifications_today' => Notification::whereDate('created_at', today())->count(),
            'notifications_this_week' => Notification::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'notifications_this_month' => Notification::whereMonth('created_at', now()->month)->count(),
        ];
        
        return response()->json($analytics);
    }
} 