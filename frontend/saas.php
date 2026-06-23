<?php
require_once 'config.php';

$tab = $_GET['tab'] ?? 'plans';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'create_tenant') {
        $result = apiCall('/tenants', 'POST', [
            'name' => $_POST['name'],
            'subdomain' => $_POST['subdomain'],
            'company_name' => $_POST['company_name'] ?? null,
            'company_address' => $_POST['company_address'] ?? null,
            'company_phone' => $_POST['company_phone'] ?? null,
            'company_email' => $_POST['company_email'] ?? null,
            'tax_id' => $_POST['tax_id'] ?? null,
            'plan_code' => $_POST['plan_code'] ?? 'BUSINESS',
        ]);
        $msg = $result['code'] === 201 ? 'tenant_created' : 'error';
        header("Location: saas.php?tab=tenants&msg=$msg");
        exit;
    } elseif (($_POST['action'] ?? '') === 'subscribe') {
        $result = apiCall('/tenants/' . $_POST['tenant_id'] . '/subscribe', 'POST', [
            'plan_id' => (int)$_POST['plan_id'],
            'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
        ]);
        $msg = $result['code'] === 201 ? 'subscribed' : 'error';
        header("Location: saas.php?tab=tenants&msg=$msg");
        exit;
    } elseif (($_POST['action'] ?? '') === 'pay_invoice') {
        $result = apiCall('/tenants/invoices/' . $_POST['invoice_id'] . '/pay', 'POST', [
            'payment_method' => $_POST['payment_method'],
        ]);
        $msg = $result['code'] === 200 ? 'paid' : 'error';
        header("Location: saas.php?tab=invoices&msg=$msg");
        exit;
    }
}

$plansResp = apiCall('/subscription-plans');
$plans = $plansResp['body']['data'] ?? [];

$tenantsResp = apiCall('/tenants');
$tenants = $tenantsResp['body']['data'] ?? [];

$msg = $_GET['msg'] ?? '';
renderHead('SaaS Management');
renderNav('saas');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>SaaS Management</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tenantModal"><i class="bi bi-plus"></i> New Tenant</button>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg==='error'?'danger':'success' ?> alert-dismissible fade show">
        <?php
        $msgs = ['tenant_created'=>'Tenant created with 14-day trial','subscribed'=>'Subscription activated','paid'=>'Invoice paid successfully','error'=>'Error occurred'];
        echo $msgs[$msg] ?? $msg;
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab==='plans'?'active':'' ?>" href="?tab=plans">Subscription Plans</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='tenants'?'active':'' ?>" href="?tab=tenants">Tenants</a></li>
    </ul>

    <?php if ($tab === 'plans'): ?>
        <div class="row">
        <?php foreach ($plans as $plan): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 <?= $plan['code']==='BUSINESS'?'border-primary':'' ?>">
                    <?php if ($plan['code']==='BUSINESS'): ?><div class="card-header bg-primary text-white text-center">RECOMMENDED</div><?php endif; ?>
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($plan['name']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($plan['description']) ?></p>
                        <h3>Rp <?= number_format($plan['price_monthly'], 0) ?><small class="text-muted">/mo</small></h3>
                        <p class="small">Rp <?= number_format($plan['price_yearly'], 0) ?>/yr</p>
                        <hr>
                        <ul class="list-unstyled text-start small">
                            <li><i class="bi bi-<?= $plan['max_users']>=5?'check':'check' ?>"></i> Up to <?= $plan['max_users'] ?> users</li>
                            <li><i class="bi bi-check"></i> Up to <?= number_format($plan['max_products']) ?> products</li>
                            <li><i class="bi bi-<?= $plan['max_warehouses']>1?'check':'x' ?>"></i> <?= $plan['max_warehouses'] ?> warehouse(s)</li>
                            <li><i class="bi bi-<?= $plan['has_accounting']?'check':'x' ?>"></i> Accounting module</li>
                            <li><i class="bi bi-<?= $plan['has_multi_warehouse']?'check':'x' ?>"></i> Multi-warehouse</li>
                            <li><i class="bi bi-<?= $plan['has_api_access']?'check':'x' ?>"></i> API access</li>
                            <li><i class="bi bi-<?= $plan['has_custom_branding']?'check':'x' ?>"></i> Custom branding</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

    <?php elseif ($tab === 'tenants'): ?>
        <table class="table table-striped">
            <thead><tr><th>Code</th><th>Name</th><th>Subdomain</th><th>Status</th><th>Trial Ends</th><th>Subscription Ends</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['code']) ?></td>
                <td><?= htmlspecialchars($t['name']) ?></td>
                <td><?= htmlspecialchars($t['subdomain']) ?></td>
                <td><span class="badge bg-<?= $t['status']==='active'?'success':($t['status']==='trial'?'info':($t['status']==='suspended'?'warning':'danger')) ?>"><?= ucfirst($t['status']) ?></span></td>
                <td><?= $t['trial_ends_at'] ?? '-' ?></td>
                <td><?= $t['subscription_ends_at'] ?? '-' ?></td>
                <td>
                    <form method="POST" action="saas.php" class="d-inline" onsubmit="return confirm('Activate subscription?')">
                        <input type="hidden" name="action" value="subscribe">
                        <input type="hidden" name="tenant_id" value="<?= $t['id'] ?>">
                        <select name="plan_id" class="form-select form-select-sm d-inline-block" style="width:auto">
                            <?php foreach ($plans as $p): ?><option value="<?= $p['id'] ?>"><?= $p['name'] ?></option><?php endforeach; ?>
                        </select>
                        <select name="billing_cycle" class="form-select form-select-sm d-inline-block" style="width:auto">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-success">Subscribe</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tenants)): ?><tr><td colspan="7" class="text-center text-muted">No tenants</td></tr><?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="modal fade" id="tenantModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="saas.php"><input type="hidden" name="action" value="create_tenant">
        <div class="modal-header"><h5 class="modal-title">New Tenant (14-day trial)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-md-6"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" required></div>
                <div class="col-md-6"><label class="form-label">Subdomain *</label><div class="input-group"><input type="text" class="form-control" name="subdomain" required><span class="input-group-text">.panglong.app</span></div></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6"><label class="form-label">Company Name</label><input type="text" class="form-control" name="company_name"></div>
                <div class="col-md-6"><label class="form-label">Tax ID (NPWP)</label><input type="text" class="form-control" name="tax_id"></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" name="company_phone"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="company_email"></div>
            </div>
            <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="company_address"></textarea></div>
            <div class="mb-3"><label class="form-label">Trial Plan</label><select class="form-select" name="plan_code">
                <?php foreach ($plans as $p): ?><option value="<?= $p['code'] ?>"><?= $p['name'] ?></option><?php endforeach; ?>
            </select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create Tenant</button></div>
    </form>
</div></div></div>
<?php renderFoot(); ?>
