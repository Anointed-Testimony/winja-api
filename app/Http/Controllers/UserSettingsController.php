<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserSettingsController extends Controller
{
    public function getSettings()
    {
        $user = Auth::user();
        $settings = UserSetting::where('user_id', $user->id)->first();
        
        return response()->json(['settings' => $settings]);
    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'nullable|string|max:20',
            'email_notifications' => 'boolean',
            'whatsapp_notifications' => 'boolean',
            'opportunity_alerts' => 'boolean',
            'marketing_emails' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $settings = UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            $request->all()
        );

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $settings
        ]);
    }
} 