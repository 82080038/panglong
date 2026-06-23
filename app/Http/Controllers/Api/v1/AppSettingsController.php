<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingsController extends Controller
{
    public function index()
    {
        $settings = AppSetting::all();

        $data = [];
        foreach ($settings as $setting) {
            $data[$setting->key] = AppSetting::get($setting->key);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.type' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $item) {
            AppSetting::set($item['key'], $item['value'], $item['type'] ?? 'string');
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }
}
