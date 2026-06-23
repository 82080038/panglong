<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    private CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request)
    {
        $query = \App\Models\Customer::with(['group']);

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $customers = $query->orderBy('name')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $customer = \App\Models\Customer::with(['group', 'sales', 'accountsReceivable'])->findOrFail($id);

        $customer->outstanding_balance = $this->customerService->getOutstandingBalance($id);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $customer = $this->customerService->createCustomer($validated);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => $customer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer. Please check your input.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:100',
            'group_id' => 'sometimes|exists:customer_groups,id',
            'credit_limit' => 'sometimes|numeric|min:0',
            'payment_terms' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $customer = $this->customerService->updateCustomer($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer. Please check your input.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $customer = \App\Models\Customer::findOrFail($id);
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ]);
    }
}
