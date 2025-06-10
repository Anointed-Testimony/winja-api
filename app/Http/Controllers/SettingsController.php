<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    // Get all settings as key-value pairs
    public function index()
    {
        $settings = Setting::all()->mapWithKeys(function ($item) {
            return [$item->key => $item->casted_value];
        });
        return response()->json($settings);
    }

    // Update multiple settings
    public function update(Request $request)
    {
        $data = $request->all();
        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                $setting->value = $value;
                $setting->save();
            }
        }
        Cache::forget('settings');
        return response()->json(['message' => 'Settings updated successfully']);
    }
} 