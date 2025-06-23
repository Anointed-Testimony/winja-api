<?php

namespace App\Services;

use App\Models\User;
use App\Models\Opportunity;
use App\Models\Notification;
use App\Mail\NewOpportunityNotification;
use App\Mail\OpportunityDigestNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Send opportunity notification to users
     * Premium users get instant notifications, free users get queued for digest
     */
    public static function sendOpportunityNotification(Opportunity $opportunity, $users = null)
    {
        try {
            // If no users specified, get all active users
            if (!$users) {
                $users = User::where('status', 'active')->get();
            }

            $premiumUsers = collect();
            $freeUsers = collect();

            // Separate users by premium status
            foreach ($users as $user) {
                if ($user->is_premium) {
                    $premiumUsers->push($user);
                } else {
                    $freeUsers->push($user);
                }
            }

            // Send instant notifications to premium users
            if ($premiumUsers->isNotEmpty()) {
                self::sendInstantOpportunityNotifications($opportunity, $premiumUsers);
            }

            // Queue digest notifications for free users
            if ($freeUsers->isNotEmpty()) {
                self::queueDigestNotifications($opportunity, $freeUsers);
            }

            Log::info("Opportunity notification sent", [
                'opportunity_id' => $opportunity->id,
                'premium_users' => $premiumUsers->count(),
                'free_users' => $freeUsers->count(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send opportunity notification", [
                'opportunity_id' => $opportunity->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send instant email notifications to premium users
     */
    private static function sendInstantOpportunityNotifications(Opportunity $opportunity, $users)
    {
        foreach ($users as $user) {
            try {
                // Create in-app notification
                $user->notifications()->create([
                    'title' => "New {$opportunity->type->name} Opportunity",
                    'message' => "A new {$opportunity->type->name} opportunity has been posted: {$opportunity->title}",
                    'type' => 'opportunity',
                    'data' => [
                        'opportunity_id' => $opportunity->id,
                        'opportunity_type' => $opportunity->type->name,
                    ],
                ]);

                // Send email notification
                Mail::to($user->email)->send(new NewOpportunityNotification($user, $opportunity));

                Log::info("Instant notification sent to premium user", [
                    'user_id' => $user->id,
                    'opportunity_id' => $opportunity->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send instant notification to user", [
                    'user_id' => $user->id,
                    'opportunity_id' => $opportunity->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Queue digest notifications for free users
     */
    private static function queueDigestNotifications(Opportunity $opportunity, $users)
    {
        foreach ($users as $user) {
            try {
                // Create in-app notification
                $user->notifications()->create([
                    'title' => "New {$opportunity->type->name} Opportunity",
                    'message' => "A new {$opportunity->type->name} opportunity has been posted: {$opportunity->title}",
                    'type' => 'opportunity',
                    'data' => [
                        'opportunity_id' => $opportunity->id,
                        'opportunity_type' => $opportunity->type->name,
                    ],
                ]);

                // Queue digest email (will be sent by scheduled job)
                // The digest job will collect all new opportunities and send them together
                Log::info("Digest notification queued for free user", [
                    'user_id' => $user->id,
                    'opportunity_id' => $opportunity->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to queue digest notification for user", [
                    'user_id' => $user->id,
                    'opportunity_id' => $opportunity->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send digest notifications to free users
     * This method is called by the scheduled job
     */
    public static function sendDigestNotifications()
    {
        try {
            // Get free users who haven't received a digest in the last 5 hours
            $fiveHoursAgo = Carbon::now()->subHours(5);
            
            $freeUsers = User::where('status', 'active')
                ->where('is_premium', false)
                ->whereDoesntHave('notifications', function ($query) use ($fiveHoursAgo) {
                    $query->where('type', 'digest_sent')
                          ->where('created_at', '>=', $fiveHoursAgo);
                })
                ->get();

            foreach ($freeUsers as $user) {
                self::sendDigestNotification($user);
            }

            Log::info("Digest notifications sent", [
                'users_count' => $freeUsers->count(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send digest notifications", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send digest notification to a specific user
     */
    public static function sendDigestNotification(User $user)
    {
        try {
            // Get new opportunities from the last 5 hours
            $fiveHoursAgo = Carbon::now()->subHours(5);
            
            $opportunities = Opportunity::where('status', 'Active')
                ->where('created_at', '>=', $fiveHoursAgo)
                ->with('type')
                ->orderBy('created_at', 'desc')
                ->get();

            // Send digest email
            Mail::to($user->email)->send(new OpportunityDigestNotification($user, $opportunities, '5 hours'));

            // Mark digest as sent
            $user->notifications()->create([
                'title' => 'Opportunity Digest Sent',
                'message' => "Digest email sent with {$opportunities->count()} new opportunities",
                'type' => 'system',
                'data' => [
                    'digest_type' => 'opportunity_digest',
                    'opportunities_count' => $opportunities->count(),
                ],
            ]);

            Log::info("Digest notification sent to user", [
                'user_id' => $user->id,
                'opportunities_count' => $opportunities->count(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send digest notification to user", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead($notificationId, $userId = null)
    {
        try {
            $query = Notification::where('id', $notificationId);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            $notification = $query->firstOrFail();
            $notification->markAsRead();
            
            Log::info("Notification marked as read", [
                'notification_id' => $notificationId,
                'user_id' => $notification->user_id,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to mark notification as read", [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead($userId)
    {
        try {
            $updatedCount = Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => Carbon::now()]);
            
            Log::info("All notifications marked as read", [
                'user_id' => $userId,
                'updated_count' => $updatedCount,
            ]);
            
            return $updatedCount;
        } catch (\Exception $e) {
            Log::error("Failed to mark all notifications as read", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Send application status notification
     */
    public static function sendApplicationNotification($application, $status)
    {
        try {
            $user = $application->user;
            $opportunity = $application->opportunity;
            
            $statusMessages = [
                'shortlisted' => 'Your application has been shortlisted!',
                'rejected' => 'Your application was not selected.',
                'accepted' => 'Congratulations! Your application has been accepted!',
            ];
            
            $message = $statusMessages[$status] ?? "Your application status has been updated to: {$status}";
            
            // Create in-app notification
            $user->notifications()->create([
                'title' => 'Application Update',
                'message' => $message,
                'type' => 'application',
                'data' => [
                    'application_id' => $application->id,
                    'opportunity_id' => $opportunity->id,
                    'status' => $status,
                ],
            ]);
            
            Log::info("Application notification sent", [
                'user_id' => $user->id,
                'application_id' => $application->id,
                'status' => $status,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send application notification", [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send partner status notification
     */
    public static function sendPartnerNotification(User $user, $status, $message = null)
    {
        try {
            $defaultMessages = [
                'approved' => 'Congratulations! Your partner application has been approved.',
                'rejected' => 'Your partner application was not approved at this time.',
                'pending' => 'Your partner application is under review.',
            ];
            
            $notificationMessage = $message ?? $defaultMessages[$status] ?? "Your partner status has been updated to: {$status}";
            
            // Create in-app notification
            $user->notifications()->create([
                'title' => 'Partner Status Update',
                'message' => $notificationMessage,
                'type' => 'partner',
                'data' => [
                    'partner_status' => $status,
                ],
            ]);
            
            Log::info("Partner notification sent", [
                'user_id' => $user->id,
                'status' => $status,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send partner notification", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send system notification
     */
    public static function sendSystemNotification($users, $title, $message, $data = [])
    {
        try {
            $userIds = is_array($users) ? $users : [$users];
            
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->notifications()->create([
                        'title' => $title,
                        'message' => $message,
                        'type' => 'system',
                        'data' => $data,
                    ]);
                }
            }
            
            Log::info("System notification sent", [
                'user_count' => count($userIds),
                'title' => $title,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send system notification", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get notification statistics
     */
    public static function getNotificationStats($userId = null)
    {
        try {
            $query = Notification::query();
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            $stats = [
                'total' => $query->count(),
                'unread' => $query->whereNull('read_at')->count(),
                'read' => $query->whereNotNull('read_at')->count(),
                'by_type' => [
                    'opportunity' => $query->where('type', 'opportunity')->count(),
                    'application' => $query->where('type', 'application')->count(),
                    'system' => $query->where('type', 'system')->count(),
                    'partner' => $query->where('type', 'partner')->count(),
                ],
            ];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error("Failed to get notification stats", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
} 