<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Sale;

class CustomerService
{
    /**
     * Create customer
     */
    public function createCustomer(array $data): Customer
    {
        return Customer::create([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'group_id' => $data['group_id'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'payment_terms' => $data['payment_terms'] ?? 30,
            'credit_score' => 'C',
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update customer
     */
    public function updateCustomer(int $customerId, array $data): Customer
    {
        $customer = Customer::findOrFail($customerId);
        
        $customer->update([
            'name' => $data['name'] ?? $customer->name,
            'address' => $data['address'] ?? $customer->address,
            'phone' => $data['phone'] ?? $customer->phone,
            'email' => $data['email'] ?? $customer->email,
            'group_id' => $data['group_id'] ?? $customer->group_id,
            'credit_limit' => $data['credit_limit'] ?? $customer->credit_limit,
            'payment_terms' => $data['payment_terms'] ?? $customer->payment_terms,
            'is_active' => $data['is_active'] ?? $customer->is_active,
        ]);

        return $customer;
    }

    /**
     * Calculate customer credit score
     */
    public function calculateCreditScore(int $customerId): string
    {
        $customer = Customer::with('sales.payments')->findOrFail($customerId);
        
        $totalSales = $customer->sales->count();
        $onTimePayments = 0;
        $latePayments = 0;
        
        foreach ($customer->sales as $sale) {
            if ($sale->payment_method === 'credit') {
                $expectedDate = date('Y-m-d', strtotime($sale->sale_date . " +{$customer->payment_terms} days"));
                $lastPayment = $sale->payments->sortByDesc('payment_date')->first();
                
                if ($lastPayment) {
                    if ($lastPayment->payment_date <= $expectedDate) {
                        $onTimePayments++;
                    } else {
                        $latePayments++;
                    }
                }
            }
        }

        if ($totalSales === 0) {
            return 'C';
        }

        $onTimeRate = $onTimePayments / $totalSales;
        
        if ($onTimeRate >= 0.9) {
            return 'A';
        } elseif ($onTimeRate >= 0.7) {
            return 'B';
        } elseif ($onTimeRate >= 0.5) {
            return 'C';
        } else {
            return 'D';
        }
    }

    /**
     * Get customer outstanding balance
     */
    public function getOutstandingBalance(int $customerId): float
    {
        $customer = Customer::with('accountsReceivable')->findOrFail($customerId);
        
        return $customer->accountsReceivable->sum('balance');
    }
}
