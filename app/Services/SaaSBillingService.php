<?php
namespace App\Services;

use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Carbon\Carbon;

class SaaSBillingService
{
    public function createTrial(Tenant $tenant, SubscriptionPlan $plan): Subscription
    {
        $trialEnds = Carbon::now()->addDays(14);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'start_date' => Carbon::today(),
            'end_date' => $trialEnds,
            'status' => 'active',
            'amount' => 0,
            'trial_ends_at' => $trialEnds,
        ]);

        $tenant->update(['status' => 'trial', 'trial_ends_at' => $trialEnds]);

        return $subscription;
    }

    public function activateSubscription(Tenant $tenant, SubscriptionPlan $plan, string $cycle = 'monthly'): Subscription
    {
        $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $endDate = $cycle === 'yearly' ? Carbon::now()->addYear() : Carbon::now()->addMonth();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'start_date' => Carbon::today(),
            'end_date' => $endDate,
            'status' => 'active',
            'amount' => $amount,
        ]);

        $tenant->update(['status' => 'active', 'subscription_ends_at' => $endDate]);

        $this->generateInvoice($subscription);

        return $subscription;
    }

    public function generateInvoice(Subscription $subscription): SubscriptionInvoice
    {
        $invoiceNo = 'SINV-' . date('Ymd') . '-' . str_pad($subscription->id, 4, '0', STR_PAD_LEFT);

        return SubscriptionInvoice::create([
            'invoice_no' => $invoiceNo,
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(7),
            'amount' => $subscription->amount,
            'status' => 'unpaid',
        ]);
    }

    public function payInvoice(SubscriptionInvoice $invoice, string $method): void
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $method,
        ]);

        $subscription = $invoice->subscription;
        $newEndDate = $subscription->billing_cycle === 'yearly'
            ? Carbon::parse($subscription->end_date)->addYear()
            : Carbon::parse($subscription->end_date)->addMonth();

        $subscription->update(['end_date' => $newEndDate]);
        $subscription->tenant->update(['subscription_ends_at' => $newEndDate]);
    }

    public function checkAndSuspendExpired(): int
    {
        $expired = Subscription::where('status', 'active')
            ->where('end_date', '<', Carbon::today())
            ->get();

        $count = 0;
        foreach ($expired as $sub) {
            $sub->update(['status' => 'expired']);
            $sub->tenant->update(['status' => 'suspended']);
            $count++;
        }

        return $count;
    }

    public function getPlanFeatures(SubscriptionPlan $plan): array
    {
        return [
            'max_users' => $plan->max_users,
            'max_products' => $plan->max_products,
            'max_warehouses' => $plan->max_warehouses,
            'has_accounting' => $plan->has_accounting,
            'has_multi_warehouse' => $plan->has_multi_warehouse,
            'has_api_access' => $plan->has_api_access,
            'has_custom_branding' => $plan->has_custom_branding,
        ];
    }
}
