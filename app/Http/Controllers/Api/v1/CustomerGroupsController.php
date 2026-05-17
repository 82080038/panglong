<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerGroupsController extends Controller
{
    public function index()
    {
        $groups = \App\Models\CustomerGroup::all();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $group = \App\Models\CustomerGroup::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer group created successfully',
            'data' => $group,
        ], 201);
    }

    public function show($id)
    {
        $group = \App\Models\CustomerGroup::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $group,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'discount_pct' => 'sometimes|numeric|min:0|max:100',
            'credit_limit' => 'sometimes|numeric|min:0',
        ]);

        $group = \App\Models\CustomerGroup::findOrFail($id);
        $group->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer group updated successfully',
            'data' => $group,
        ]);
    }

    public function destroy($id)
    {
        $group = \App\Models\CustomerGroup::findOrFail($id);
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer group deleted successfully',
        ]);
    }
}
