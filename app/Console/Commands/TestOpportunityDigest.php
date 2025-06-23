<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Opportunity;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestOpportunityDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test-digest {user_id? : Specific user ID to test with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the opportunity digest functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            // Test with specific user
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            
            $this->info("Testing digest for user: {$user->name} ({$user->email})");
            $this->info("User premium status: " . ($user->is_premium ? 'Premium' : 'Free'));
            
            if ($user->is_premium) {
                $this->warn("This user is premium and should receive instant notifications, not digest emails.");
            }
            
            $this->testDigestForUser($user);
            
        } else {
            // Test with all free users
            $freeUsers = User::where('status', 'active')
                ->where('is_premium', false)
                ->get();
            
            $this->info("Testing digest for {$freeUsers->count()} free users...");
            
            foreach ($freeUsers as $user) {
                $this->line("\nTesting for: {$user->name} ({$user->email})");
                $this->testDigestForUser($user);
            }
        }
        
        return 0;
    }
    
    /**
     * Test digest for a specific user
     */
    private function testDigestForUser(User $user)
    {
        // Get recent opportunities
        $recentOpportunities = Opportunity::where('status', 'Active')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->with('type')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->line("  - Found {$recentOpportunities->count()} recent opportunities");
        
        if ($recentOpportunities->isEmpty()) {
            $this->warn("  - No recent opportunities found for testing");
            return;
        }
        
        // Show opportunity details
        $this->line("  - Recent opportunities:");
        foreach ($recentOpportunities->take(3) as $opportunity) {
            $this->line("    * {$opportunity->title} ({$opportunity->type->name})");
        }
        
        // Test sending digest
        $this->line("  - Sending test digest...");
        
        try {
            $result = NotificationService::sendDigestNotification($user);
            
            if ($result) {
                $this->info("  - ✓ Test digest sent successfully");
            } else {
                $this->error("  - ✗ Failed to send test digest");
            }
            
        } catch (\Exception $e) {
            $this->error("  - ✗ Error: {$e->getMessage()}");
        }
    }
} 