<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;

class BranchesController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $branches = $query->orderBy('name')->get();

        return response()->json(['success' => true, 'data' => $branches]);
    }

    public function show($id)
    {
        $branch = Branch::with(['employees', 'warehouses'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $branch]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:branches,code',
            'name' => 'required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'manager_name' => 'nullable|string',
            'type' => 'nullable|in:pusat,cabang,agen',
        ]);

        $branch = Branch::create($validated + ['is_active' => true]);

        return response()->json(['success' => true, 'data' => $branch], 201);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'manager_name' => 'nullable|string',
            'type' => 'nullable|in:pusat,cabang,agen',
            'is_active' => 'nullable|boolean',
        ]);

        $branch->update($validated);
        return response()->json(['success' => true, 'data' => $branch]);
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->update(['is_active' => false]);
        return response()->json(['success' => true, 'message' => 'Branch deactivated']);
    }
}
