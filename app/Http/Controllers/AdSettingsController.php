<?php

namespace App\Http\Controllers;

use App\Models\AdSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdSettingsController extends Controller
{
    /**
     * GET /ad-settings - Get current pricing
     */
    public function index()
    {
        $settings = AdSettings::orderBy('ad_type')->orderBy('duration_type')->get();
        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * POST /ad-settings - Create new pricing setting
     */
    public function store(Request $request)
    {
        $request->validate([
            'ad_type' => 'required|in:featured,inline',
            'duration_type' => 'required|in:daily,weekly',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'min_duration' => 'nullable|integer|min:1',
            'max_duration' => 'nullable|integer|min:1',
        ]);

        try {
            // Check if setting already exists
            $existing = AdSettings::where('ad_type', $request->ad_type)
                ->where('duration_type', $request->duration_type)
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Pricing setting already exists for this combination'], 400);
            }

            $setting = AdSettings::create($request->all());
            return response()->json([
                'message' => 'Pricing setting created successfully',
                'setting' => $setting,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create ad setting: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create pricing setting'], 500);
        }
    }

    /**
     * GET /ad-settings/{id} - Get specific pricing setting
     */
    public function show($id)
    {
        $setting = AdSettings::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    /**
     * PUT /ad-settings/{id} - Update pricing setting
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'min_duration' => 'nullable|integer|min:1',
            'max_duration' => 'nullable|integer|min:1',
        ]);

        try {
            $setting = AdSettings::findOrFail($id);
            $setting->update($request->all());

            return response()->json([
                'message' => 'Pricing setting updated successfully',
                'setting' => $setting,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update ad setting: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update pricing setting'], 500);
        }
    }

    /**
     * DELETE /ad-settings/{id} - Delete pricing setting
     */
    public function destroy($id)
    {
        try {
            $setting = AdSettings::findOrFail($id);
            $setting->delete();

            return response()->json(['message' => 'Pricing setting deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete ad setting: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete pricing setting'], 500);
        }
    }

    /**
     * POST /ad-settings/{id}/toggle - Toggle active status
     */
    public function toggleActive($id)
    {
        try {
            $setting = AdSettings::findOrFail($id);
            $setting->toggleActive();

            return response()->json([
                'message' => 'Setting status updated successfully',
                'setting' => $setting,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle ad setting: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update setting status'], 500);
        }
    }

    /**
     * GET /ad-settings/pricing - Get active pricing (public endpoint)
     */
    public function getActivePricing()
    {
        $pricing = AdSettings::getActivePricing();
        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    /**
     * POST /ad-settings/bulk-update - Bulk update pricing
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:ad_settings,id',
            'settings.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->settings as $settingData) {
                $setting = AdSettings::find($settingData['id']);
                $setting->update(['price' => $settingData['price']]);
            }

            DB::commit();

            return response()->json(['message' => 'Pricing updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk update ad settings: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update pricing'], 500);
        }
    }
} 