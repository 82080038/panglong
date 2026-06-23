<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;

class WarehouseLocationsController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseLocation::with(['warehouse']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->has('zone_type')) {
            $query->where('zone_type', $request->zone_type);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $locations = $query->orderBy('code')->get();

        return response()->json(['success' => true, 'data' => $locations]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string',
            'name' => 'required|string',
            'zone_type' => 'nullable|in:rack,block,shelf,pallet,floor',
            'aisle' => 'nullable|string',
            'level' => 'nullable|string',
            'max_weight_kg' => 'nullable|numeric|min:0',
            'capacity_m2' => 'nullable|numeric|min:0',
        ]);

        $location = WarehouseLocation::create($validated + ['is_active' => true]);

        return response()->json(['success' => true, 'data' => $location, 'message' => 'Location created'], 201);
    }

    public function update(Request $request, $id)
    {
        $location = WarehouseLocation::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'zone_type' => 'nullable|in:rack,block,shelf,pallet,floor',
            'aisle' => 'nullable|string',
            'level' => 'nullable|string',
            'max_weight_kg' => 'nullable|numeric|min:0',
            'capacity_m2' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $location->update($validated);
        return response()->json(['success' => true, 'data' => $location]);
    }

    public function destroy($id)
    {
        $location = WarehouseLocation::findOrFail($id);
        $location->update(['is_active' => false]);
        return response()->json(['success' => true, 'message' => 'Location deactivated']);
    }
}
