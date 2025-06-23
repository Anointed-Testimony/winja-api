<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpportunityTypeController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SavedOpportunityController;
use App\Http\Controllers\ApplicationTrackerController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\PartnerMetricsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AdminWalletController;
use App\Http\Controllers\AdCampaignController;
use App\Http\Controllers\AdPlacementController;
use App\Http\Controllers\AdSettingsController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\Admin\PointsSettingsController;
use App\Http\Controllers\PointsController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-email', [AuthController::class, 'verifyOtp']);
Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp']);
Route::apiResource('opportunity-types', OpportunityTypeController::class);
Route::get('/opportunities/analytics', [OpportunityController::class, 'analytics']);
Route::post('/opportunities/{id}/increment/{type}', [OpportunityController::class, 'incrementCounter']);
Route::get('/opportunities/export/csv', [OpportunityController::class, 'exportCsv']);
Route::get('/opportunities/export/pdf', [OpportunityController::class, 'exportPdf']);
Route::get('/opportunities/mine', [App\Http\Controllers\OpportunityController::class, 'mine'])->middleware('auth:sanctum');
Route::apiResource('opportunities', OpportunityController::class);
Route::apiResource('sponsored-opportunities', \App\Http\Controllers\SponsoredOpportunityController::class);

Route::get('/partners/all', [App\Http\Controllers\PartnerController::class, 'all']);

Route::get('/subscriptions/plans', [SubscriptionController::class, 'getPlans']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/partners/status', [\App\Http\Controllers\PartnerController::class, 'getCurrentPartnerStatus']);
    Route::get('/partners', [\App\Http\Controllers\PartnerController::class, 'index']);
    Route::get('/partners/{id}', [\App\Http\Controllers\PartnerController::class, 'show']);
    Route::put('/partners/{id}', [\App\Http\Controllers\PartnerController::class, 'update']);
    Route::get('/partners/{id}/sponsored-opportunities', [\App\Http\Controllers\PartnerController::class, 'getSponsoredOpportunities']);
    Route::get('/partners/{id}/metrics', [\App\Http\Controllers\PartnerController::class, 'getMetrics']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::post('/users/{id}/ban', [UserController::class, 'ban']);
    Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::post('/users/{id}/activate', [UserController::class, 'activate']);
    Route::get('/user/stats', [UserController::class, 'getUserStats']);
    Route::get('/saved-opportunities', [SavedOpportunityController::class, 'index']);
    Route::post('/saved-opportunities', [SavedOpportunityController::class, 'store']);
    Route::put('/saved-opportunities/{savedOpportunity}', [SavedOpportunityController::class, 'update']);
    Route::delete('/saved-opportunities/{savedOpportunity}', [SavedOpportunityController::class, 'destroy']);
    Route::get('/applications', [ApplicationTrackerController::class, 'index']);
    Route::post('/applications', [ApplicationTrackerController::class, 'store']);
    Route::put('/applications/{applicationTracker}', [ApplicationTrackerController::class, 'update']);
    Route::delete('/applications/{applicationTracker}', [ApplicationTrackerController::class, 'destroy']);
    Route::get('/referrals', [ReferralController::class, 'index']);
    Route::get('/referrals/stats', [ReferralController::class, 'getStats']);
    Route::post('/referrals', [ReferralController::class, 'store']);
    Route::post('/referrals/{referral}/complete', [ReferralController::class, 'complete']);
    Route::get('/referrals/leaderboard', [ReferralController::class, 'leaderboard']);
    Route::get('/badges', [BadgeController::class, 'index']);
    Route::post('/badges', [BadgeController::class, 'store']);
    Route::get('/badges/{badge}', [BadgeController::class, 'show']);
    Route::put('/badges/{badge}', [BadgeController::class, 'update']);
    Route::delete('/badges/{badge}', [BadgeController::class, 'destroy']);
    Route::post('/badges/check-eligibility', [BadgeController::class, 'checkEligibility']);
    Route::get('/analytics/user-engagement', [AnalyticsController::class, 'userEngagement']);
    Route::get('/analytics/revenue', [AnalyticsController::class, 'revenue']);
    Route::get('/analytics/trends', [AnalyticsController::class, 'trends']);
    Route::post('/partners/apply', [\App\Http\Controllers\PartnerController::class, 'applyAsPartner']);
    Route::get('/partner/metrics', [PartnerMetricsController::class, 'getMetrics']);

    // User subscription routes
    Route::get('/subscriptions/user-status', [SubscriptionController::class, 'getUserStatus']);
    Route::post('/subscriptions/create', [SubscriptionController::class, 'create']);
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel']);

    // User settings routes
    Route::get('/user/settings', [UserSettingsController::class, 'getSettings']);
    Route::put('/user/settings', [UserSettingsController::class, 'updateSettings']);

    // Transaction routes
    Route::post('/transactions/initialize', [TransactionController::class, 'initializePayment']);
    Route::post('/transactions/verify', [TransactionController::class, 'verifyPayment']);
    Route::get('/transactions', [TransactionController::class, 'getUserTransactions']);
    Route::get('/transactions/{id}', [TransactionController::class, 'getTransactionDetails']);

    // Ad Campaign Routes
    Route::get('/ad-campaigns/pricing', [AdCampaignController::class, 'getPricing']);
    Route::get('/ad-campaigns', [AdCampaignController::class, 'index']);
    Route::post('/ad-campaigns', [AdCampaignController::class, 'store']);
    Route::get('/ad-campaigns/{id}', [AdCampaignController::class, 'show']);
    Route::put('/ad-campaigns/{id}', [AdCampaignController::class, 'update']);
    Route::delete('/ad-campaigns/{id}', [AdCampaignController::class, 'destroy']);
    Route::post('/ad-campaigns/{id}/initialize-payment', [AdCampaignController::class, 'initializePayment']);
    Route::post('/ad-campaigns/{id}/verify-payment', [AdCampaignController::class, 'verifyPayment']);

    // Ad Placement Routes
    Route::get('/ad-placements/featured', [AdPlacementController::class, 'getFeaturedAds']);
    Route::get('/ad-placements/inline', [AdPlacementController::class, 'getInlineAds']);
    Route::post('/ad-placements/impression', [AdPlacementController::class, 'trackImpression']);
    Route::post('/ad-placements/click', [AdPlacementController::class, 'trackClick']);
    Route::get('/ad-placements/stats/{campaignId}', [AdPlacementController::class, 'getAdStats']);
    Route::post('/ad-placements/activate/{id}', [AdPlacementController::class, 'activateCampaign']);
    Route::post('/ad-placements/approve/{id}', [AdPlacementController::class, 'approveCampaign']);
    Route::post('/ad-placements/reject/{id}', [AdPlacementController::class, 'rejectCampaign']);

    // User withdrawal routes
    Route::post('/withdrawals/request', [WithdrawalController::class, 'requestWithdrawal']);
    Route::get('/withdrawals', [WithdrawalController::class, 'getUserWithdrawals']);
    Route::get('/withdrawals/{id}', [WithdrawalController::class, 'getWithdrawalDetails']);

    // Points routes
    Route::get('/points/balance', [PointsController::class, 'getBalance']);
    Route::get('/points/transactions', [PointsController::class, 'getTransactions']);
    Route::post('/points/withdraw', [WithdrawalController::class, 'requestWithdrawal']);

    // Notification routes
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy']);
    Route::get('/notifications/stats', [\App\Http\Controllers\NotificationController::class, 'getStats']);
});

// Admin subscription routes
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/subscription-plans', [AdminSubscriptionController::class, 'getPlans']);
    Route::post('/subscription-plans', [AdminSubscriptionController::class, 'createPlan']);
    Route::put('/subscription-plans/{id}', [AdminSubscriptionController::class, 'updatePlan']);
    Route::get('/premium-users', [AdminSubscriptionController::class, 'getPremiumUsers']);

    // Admin wallet routes
    Route::get('/wallet/balance', [AdminWalletController::class, 'getBalance']);
    Route::get('/wallet/transactions', [AdminWalletController::class, 'getTransactionHistory']);
    Route::get('/wallet/stats', [AdminWalletController::class, 'getWalletStats']);

    // Admin wallet analytics routes
    Route::get('/wallet/ad-revenue-analytics', [AdminWalletController::class, 'getAdRevenueAnalytics']);
    Route::get('/wallet/revenue-summary', [AdminWalletController::class, 'getRevenueSummary']);

    // Admin Ad Settings Routes
    Route::get('/ad-settings', [AdSettingsController::class, 'index']);
    Route::post('/ad-settings', [AdSettingsController::class, 'store']);
    Route::get('/ad-settings/{id}', [AdSettingsController::class, 'show']);
    Route::post('/ad-settings/{id}', [AdSettingsController::class, 'update']);
    Route::delete('/ad-settings/{id}', [AdSettingsController::class, 'destroy']);
    Route::post('/ad-settings/{id}/toggle', [AdSettingsController::class, 'toggleActive']);
    Route::post('/ad-settings/bulk-update', [AdSettingsController::class, 'bulkUpdate']);

    // Admin Ad Campaign Management Routes
    Route::get('/ad-campaigns', [AdCampaignController::class, 'adminIndex']);
    Route::post('/ad-campaigns/{id}/approve', [AdCampaignController::class, 'approveCampaign']);
    Route::post('/ad-campaigns/{id}/reject', [AdCampaignController::class, 'rejectCampaign']);
    Route::get('/ad-campaigns/{id}/stats', [AdCampaignController::class, 'getCampaignStats']);

    // Admin Ad Analytics Routes
    Route::get('/ad-analytics', [AdPlacementController::class, 'getAdminAnalytics']);
    Route::get('/ad-revenue', [AdPlacementController::class, 'getAdminRevenue']);
    
    Route::get('/withdrawals', [WithdrawalController::class, 'getAllWithdrawals']);
    Route::get('/withdrawals/requests', [WithdrawalController::class, 'getWithdrawalRequests']);
    Route::get('/withdrawals/requests/stats', [WithdrawalController::class, 'getWithdrawalStats']);
    Route::get('/withdrawals/{id}', [WithdrawalController::class, 'getWithdrawalDetailsAdmin']);
    Route::post('/withdrawals/{id}/approve', [WithdrawalController::class, 'approveWithdrawal']);
    Route::post('/withdrawals/{id}/reject', [WithdrawalController::class, 'rejectWithdrawal']);
    Route::get('/withdrawals/stats', [WithdrawalController::class, 'getWithdrawalStats']);
    
    // Admin points settings routes
    Route::get('/points/settings', [PointsSettingsController::class, 'getSettings']);
    Route::post('/points/settings', [PointsSettingsController::class, 'updateSettings']);
    Route::get('/points/overview', [PointsSettingsController::class, 'getPointsOverview']);

    // Admin notification routes
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'adminIndex']);
    Route::get('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'adminShow']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'adminDestroy']);
    Route::post('/notifications/send-system', [\App\Http\Controllers\NotificationController::class, 'sendSystemNotification']);
    Route::get('/notifications/analytics', [\App\Http\Controllers\NotificationController::class, 'getAnalytics']);
});

// Withdrawal requests routes (no admin middleware)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/withdrawals/requests', [WithdrawalController::class, 'getWithdrawalRequests']);
    Route::get('/withdrawals/requests/{id}', [WithdrawalController::class, 'getWithdrawalDetails']);
    Route::post('/withdrawals/requests/{id}/approve', [WithdrawalController::class, 'approveWithdrawal']);
    Route::post('/withdrawals/requests/{id}/reject', [WithdrawalController::class, 'rejectWithdrawal']);
    Route::get('/withdrawals/requests/stats', [WithdrawalController::class, 'getWithdrawalStats']);
});

// Public Ad Pricing Route
Route::get('/ad-settings/pricing', [AdSettingsController::class, 'getActivePricing']);

Route::get('/opportunity-types', [App\Http\Controllers\OpportunityTypeController::class, 'index']);

// Moderation Routes
Route::prefix('moderation')->group(function () {
    Route::get('/reports', [ModerationController::class, 'getReports']);
    Route::get('/reports/{id}', [ModerationController::class, 'getReportDetails']);
    Route::post('/reports/{id}/action', [ModerationController::class, 'takeAction']);
    Route::get('/stats', [ModerationController::class, 'getStats']);
    Route::get('/auto-flagged', [ModerationController::class, 'getAutoFlagged']);
    Route::get('/users/{userId}/history', [ModerationController::class, 'getUserHistory']);
});

// Settings & Config
Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index']);
Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update']);
Route::apiResource('push-notifications', \App\Http\Controllers\PushNotificationController::class)->except(['show']);

// Activity Logs
Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index']);
Route::post('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'store']);

Route::post('/partners', [App\Http\Controllers\PartnerController::class, 'store']);

// Forgot Password Routes
Route::prefix('auth/forgot-password')->group(function () {
    Route::post('send-otp', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendOtp']);
    Route::post('verify-otp', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'verifyOtp']);
    Route::post('reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'resetPassword']);
});

// Paystack Webhook Route (no auth required)
Route::post('/paystack/webhook', [TransactionController::class, 'handleWebhook']);

