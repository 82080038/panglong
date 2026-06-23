<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\AppSetting;
use App\Models\CustomerProductPrice;
use App\Models\ProductTierPrice;

class PricingService
{
    /**
     * Get the effective unit price for a product+unit+customer combination.
     * Priority: customer-specific price > tier price (volume) > product_unit.price_per_unit > product.sell_price
     * Then apply customer group discount percentage.
     */
    public function getUnitPrice(int $productId, int $unitId, ?int $customerId = null, ?float $quantity = null): float
    {
        $unit = ProductUnit::find($unitId);
        $basePrice = 0;

        if ($unit && $unit->price_per_unit > 0) {
            $basePrice = (float) $unit->price_per_unit;
        } else {
            $product = Product::find($productId);
            if ($product) {
                $basePrice = (float) $product->sell_price;
            }
        }

        // Priority 1: Customer-specific price
        if ($customerId) {
            $customerPrice = CustomerProductPrice::where('customer_id', $customerId)
                ->where('product_id', $productId)
                ->where('unit_id', $unitId)
                ->where('is_active', true)
                ->when($quantity, function ($q) use ($quantity) {
                    $q->where('min_qty', '<=', $quantity);
                })
                ->orderBy('min_qty', 'desc')
                ->first();
            if ($customerPrice) {
                return round((float) $customerPrice->custom_price, 2);
            }
        }

        // Priority 2: Volume-based tier price
        if ($quantity) {
            $tierPrice = ProductTierPrice::where('product_id', $productId)
                ->where('unit_id', $unitId)
                ->where('is_active', true)
                ->where('min_qty', '<=', $quantity)
                ->orderBy('min_qty', 'desc')
                ->first();
            if ($tierPrice) {
                return round((float) $tierPrice->unit_price, 2);
            }
        }

        // Priority 3: Customer group discount
        if ($customerId) {
            $customer = Customer::with('group')->find($customerId);
            if ($customer && $customer->group) {
                $discountPct = (float) ($customer->group->discount_pct ?? 0);
                if ($discountPct > 0) {
                    $basePrice = $basePrice * (1 - ($discountPct / 100));
                }
            }
        }

        return round($basePrice, 2);
    }

    /**
     * Get tax rate from settings
     */
    public function getTaxRate(): float
    {
        if (!AppSetting::get('tax_enabled', false)) {
            return 0;
        }
        return AppSetting::get('tax_rate', 0.11);
    }

    /**
     * Check if selling below buy price (margin warning)
     */
    public function checkMargin(int $productId, float $unitPrice): array
    {
        $product = Product::find($productId);
        if (!$product) {
            return ['below_cost' => false, 'margin_pct' => 0];
        }

        $buyPrice = (float) $product->buy_price;
        if ($buyPrice <= 0) {
            return ['below_cost' => false, 'margin_pct' => 100];
        }

        $margin = $unitPrice - $buyPrice;
        $marginPct = ($margin / $buyPrice) * 100;

        return [
            'below_cost' => $unitPrice < $buyPrice,
            'margin_pct' => round($marginPct, 2),
            'buy_price' => $buyPrice,
        ];
    }

    /**
     * Get customer's outstanding credit balance
     */
    public function getCustomerOutstanding(int $customerId): float
    {
        return \App\Models\AccountReceivable::where('customer_id', $customerId)
            ->where('status', '!=', 'paid')
            ->sum('balance');
    }

    /**
     * Check if customer can make credit purchase
     */
    public function checkCreditLimit(int $customerId, float $newAmount): array
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return ['allowed' => false, 'message' => 'Customer not found'];
        }

        $creditLimit = (float) $customer->credit_limit;
        if ($creditLimit <= 0) {
            return ['allowed' => true, 'message' => 'No credit limit set'];
        }

        $outstanding = $this->getCustomerOutstanding($customerId);
        $totalAfter = $outstanding + $newAmount;

        if ($totalAfter > $creditLimit) {
            return [
                'allowed' => false,
                'message' => "Credit limit exceeded. Outstanding: Rp " . number_format($outstanding, 0) . ", Limit: Rp " . number_format($creditLimit, 0) . ", New: Rp " . number_format($newAmount, 0),
                'outstanding' => $outstanding,
                'credit_limit' => $creditLimit,
            ];
        }

        return [
            'allowed' => true,
            'outstanding' => $outstanding,
            'credit_limit' => $creditLimit,
        ];
    }
}
