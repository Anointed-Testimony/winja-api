<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendOpportunityDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-opportunity-digest {--force : Force send to all free users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send opportunity digest emails to free users who haven\'t received one in 5 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting opportunity digest job...');
        
        try {
            $force = $this->option('force');
            $fiveHoursAgo = Carbon::now()->subHours(5);
            
            // Get free users who haven't received a digest in the last 5 hours
            $query = User::where('status', 'active')
                ->where('is_premium', false);
            
            if (!$force) {
                $query->whereDoesntHave('notifications', function ($q) use ($fiveHoursAgo) {
                    $q->where('type', 'system')
                      ->whereJsonContains('data->digest_type', 'opportunity_digest')
                      ->where('created_at', '>=', $fiveHoursAgo);
                });
            }
            
            $freeUsers = $query->get();
            
            if ($freeUsers->isEmpty()) {
                $this->info('No free users eligible for digest at this time.');
                return 0;
            }
            
            $this->info("Found {$freeUsers->count()} free users eligible for digest.");
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($freeUsers as $user) {
                try {
                    $this->line("Processing digest for user: {$user->name} ({$user->email})");
                    
                    // Get new opportunities from the last 5 hours
                    $newOpportunities = $this->getNewOpportunities($user, $fiveHoursAgo);
                    
                    if ($newOpportunities->isEmpty()) {
                        $this->line("  - No new opportunities for this user, skipping...");
                        continue;
                    }
                    
                    $this->line("  - Found {$newOpportunities->count()} new opportunities");
                    
                    // Send digest notification
                    $result = NotificationService::sendDigestNotification($user);
                    
                    if ($result) {
                        $successCount++;
                        $this->line("  - ✓ Digest sent successfully");
                        
                        // Log the digest sent
                        $user->notifications()->create([
                            'title' => 'Opportunity Digest Sent',
                            'message' => "Digest email sent with {$newOpportunities->count()} new opportunities",
                            'type' => 'system',
                            'data' => [
                                'digest_type' => 'opportunity_digest',
                                'opportunities_count' => $newOpportunities->count(),
                                'digest_sent_at' => Carbon::now()->toISOString(),
                            ],
                        ]);
                    } else {
                        $errorCount++;
                        $this->error("  - ✗ Failed to send digest");
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("  - ✗ Error processing user {$user->id}: {$e->getMessage()}");
                    
                    Log::error('Digest processing error for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            $this->info("\nDigest job completed:");
            $this->info("  - Successfully sent: {$successCount}");
            $this->info("  - Errors: {$errorCount}");
            $this->info("  - Total processed: " . ($successCount + $errorCount));
            
            // Log summary
            Log::info('Opportunity digest job completed', [
                'total_users' => $freeUsers->count(),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'force_mode' => $force,
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Digest job failed: {$e->getMessage()}");
            
            Log::error('Opportunity digest job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
    
    /**
     * Get new opportunities for a user since the last digest
     */
    private function getNewOpportunities(User $user, Carbon $since)
    {
        // Get opportunities created since the last digest
        $opportunities = \App\Models\Opportunity::where('status', 'Active')
            ->where('created_at', '>=', $since)
            ->with('type')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Filter out opportunities the user has already been notified about
        $alreadyNotifiedOpportunities = $user->notifications()
            ->where('type', 'opportunity')
            ->where('created_at', '>=', $since)
            ->pluck('data->opportunity_id')
            ->filter()
            ->toArray();
        
        return $opportunities->reject(function ($opportunity) use ($alreadyNotifiedOpportunities) {
            return in_array($opportunity->id, $alreadyNotifiedOpportunities);
        });
    }
    
    /**
     * Get the command's help text
     */
    public function getHelp(): string
    {
        return <<<'HELP'
The <info>notifications:send-opportunity-digest</info> command sends digest emails to free users
who haven't received one in the last 5 hours.

Usage:
  <info>php artisan notifications:send-opportunity-digest</info>
  <info>php artisan notifications:send-opportunity-digest --force</info>

Options:
  --force    Force send digest to all free users regardless of last digest time

Examples:
  <info>php artisan notifications:send-opportunity-digest</info>
  <info>php artisan notifications:send-opportunity-digest --force</info>

This command is typically scheduled to run every 5 hours via the Laravel scheduler.
HELP;
    }
} 