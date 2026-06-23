<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Services\SaaSBillingService;
use Illuminate\Http\Request;

class TenantsController extends Controller
{
    public function __construct(private SaaSBillingService $billingService)
    {
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => Tenant::orderBy('created_at', 'desc')->get()]);
    }

    public function show($id)
    {
        $tenant = Tenant::with(['activeSubscription.plan'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $tenant]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'subdomain' => 'required|string|unique:tenants,subdomain',
            'company_name' => 'nullable|string',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string',
            'company_email' => 'nullable|email',
            'tax_id' => 'nullable|string',
            'plan_code' => 'nullable|string',
        ]);

        $code = 'TEN-' . strtoupper(substr(uniqid(), -6));
        $tenant = Tenant::create([
            'code' => $code,
            'name' => $validated['name'],
            'subdomain' => $validated['subdomain'],
            'company_name' => $validated['company_name'] ?? $validated['name'],
            'company_address' => $validated['company_address'] ?? null,
            'company_phone' => $validated['company_phone'] ?? null,
            'company_email' => $validated['company_email'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        if (isset($validated['plan_code'])) {
            $plan = SubscriptionPlan::where('code', $validated['plan_code'])->first();
            if ($plan) $this->billingService->createTrial($tenant, $plan);
        }

        return response()->json(['success' => true, 'data' => $tenant], 201);
    }

    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string',
            'logo_url' => 'nullable|string',
            'primary_color' => 'nullable|string|size:7',
            'secondary_color' => 'nullable|string|size:7',
            'company_name' => 'nullable|string',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string',
            'company_email' => 'nullable|email',
            'tax_id' => 'nullable|string',
        ]);
        $tenant->update($validated);
        return response()->json(['success' => true, 'data' => $tenant]);
    }

    public function plans()
    {
        return response()->json(['success' => true, 'data' => SubscriptionPlan::where('is_active', true)->get()]);
    }

    public function subscribe(Request $request, $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'in:monthly,yearly',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $cycle = $validated['billing_cycle'] ?? 'monthly';
        $subscription = $this->billingService->activateSubscription($tenant, $plan, $cycle);

        return response()->json(['success' => true, 'data' => $subscription->load('plan')], 201);
    }

    public function invoices($tenantId)
    {
        $invoices = \App\Models\SubscriptionInvoice::where('tenant_id', $tenantId)->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $invoices]);
    }

    public function payInvoice(Request $request, $invoiceId)
    {
        $invoice = \App\Models\SubscriptionInvoice::findOrFail($invoiceId);
        $validated = $request->validate(['payment_method' => 'required|string']);
        $this->billingService->payInvoice($invoice, $validated['payment_method']);
        return response()->json(['success' => true, 'message' => 'Invoice paid successfully']);
    }
}
