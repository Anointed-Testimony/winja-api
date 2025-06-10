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
});

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
