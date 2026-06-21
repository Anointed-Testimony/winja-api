<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/opportunity/{id}', function ($id) {
    // Check if the request is coming from a mobile device
    $userAgent = request()->header('User-Agent');
    $isMobile = preg_match('/(android|iphone|ipad|ipod)/i', $userAgent);
    
    Log::info("Opportunity share accessed", [
        'id' => $id,
        'userAgent' => $userAgent,
        'isMobile' => $isMobile
    ]);
    
    if ($isMobile) {
        // Redirect to the app using the custom scheme
        $deepLink = "winja://opportunity/$id";
        Log::info("Redirecting mobile user to deep link", ['deepLink' => $deepLink]);
        return redirect($deepLink);
    }
    
    // If not mobile, show the web version
    Log::info("Showing web version for desktop user");
    return view('opportunity-share', ['opportunityId' => $id]);
})->name('opportunity.share');
