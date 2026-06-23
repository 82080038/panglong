<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['branch', 'warehouse', 'user']);

        if ($request->has('position')) {
            $query->where('position', $request->position);
        }
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('full_name')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $employee = Employee::with(['branch', 'warehouse', 'user'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $employee]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string|unique:employees,employee_no',
            'nik' => 'nullable|string|max:20',
            'full_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'position' => 'required|in:manager,salesman,kasir,gudang,driver,accounting,supervisor,staff',
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'user_id' => 'nullable|exists:users,id',
            'base_salary' => 'nullable|numeric|min:0',
            'commission_pct' => 'nullable|numeric|min:0|max:100',
            'hire_date' => 'nullable|date',
            'vehicle_plate' => 'nullable|string|max:20',
            'sim_no' => 'nullable|string|max:50',
        ]);

        $employee = Employee::create($validated + ['status' => 'active']);

        return response()->json(['success' => true, 'data' => $employee->load(['branch', 'warehouse']), 'message' => 'Employee created'], 201);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $validated = $request->validate([
            'full_name' => 'sometimes|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'position' => 'sometimes|in:manager,salesman,kasir,gudang,driver,accounting,supervisor,staff',
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'base_salary' => 'nullable|numeric|min:0',
            'commission_pct' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,resigned,terminated',
            'resign_date' => 'nullable|date',
            'vehicle_plate' => 'nullable|string',
            'sim_no' => 'nullable|string',
        ]);

        $employee->update($validated);
        return response()->json(['success' => true, 'data' => $employee->fresh(['branch', 'warehouse'])]);
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->update(['status' => 'resigned', 'resign_date' => now()->toDateString()]);
        return response()->json(['success' => true, 'message' => 'Employee marked as resigned']);
    }
}
