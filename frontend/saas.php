<?php
require_once 'config.php';
requirePermission('manage_saas');

$d = db();

$tab = $_GET['tab'] ?? 'plans';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'create_tenant') {
        $now = date('Y-m-d H:i:s');
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $_POST['subdomain']), 0, 6));
        $trialEnds = date('Y-m-d H:i:s', strtotime('+14 days'));
        $stmt = $d->prepare("INSERT INTO tenants (code, name, subdomain, company_name, company_address, company_phone, company_email, tax_id, status, trial_ends_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,'trial',?,?,?)");
        $stmt->execute([$code, $_POST['name'], $_POST['subdomain'], $_POST['company_name'] ?? null, $_POST['company_address'] ?? null, $_POST['company_phone'] ?? null, $_POST['company_email'] ?? null, $_POST['tax_id'] ?? null, $trialEnds, $now, $now]);
        header("Location: saas.php?tab=tenants&msg=tenant_created");
        exit;
    }
    if (($_POST['action'] ?? '') === 'subscribe') {
        $now = date('Y-m-d H:i:s');
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $planId = (int)($_POST['plan_id'] ?? 0);
        $billingCycle = $_POST['billing_cycle'] ?? 'monthly';
        $plan = $d->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $plan->execute([$planId]);
        $planData = $plan->fetch();
        if ($planData) {
            $amount = $billingCycle === 'yearly' ? $planData['price_yearly'] : $planData['price_monthly'];
            $startDate = date('Y-m-d');
            $endDate = $billingCycle === 'yearly' ? date('Y-m-d', strtotime('+1 year')) : date('Y-m-d', strtotime('+1 month'));
            $d->prepare("INSERT INTO subscriptions (tenant_id, plan_id, billing_cycle, start_date, end_date, status, amount, created_at, updated_at) VALUES (?,?,?,?,?,'active',?,?,?)")
                ->execute([$tenantId, $planId, $billingCycle, $startDate, $endDate, $amount, $now, $now]);
            $subId = $d->lastInsertId();
            $d->prepare("UPDATE tenants SET status = 'active', subscription_ends_at = ? WHERE id = ?")->execute([$endDate, $tenantId]);
            $invNo = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $d->prepare("INSERT INTO subscription_invoices (invoice_no, tenant_id, subscription_id, invoice_date, due_date, amount, status, created_at, updated_at) VALUES (?,?,?,?,?,?,'unpaid',?,?)")
                ->execute([$invNo, $tenantId, $subId, $startDate, date('Y-m-d', strtotime('+7 days')), $amount, $now, $now]);
            header("Location: saas.php?tab=tenants&msg=subscribed");
        } else {
            header("Location: saas.php?tab=tenants&msg=error");
        }
        exit;
    }
    if (($_POST['action'] ?? '') === 'pay_invoice') {
        $now = date('Y-m-d H:i:s');
        $invId = (int)($_POST['invoice_id'] ?? 0);
        $d->prepare("UPDATE subscription_invoices SET status = 'paid', paid_at = ?, payment_method = ?, updated_at = ? WHERE id = ?")
            ->execute([$now, $_POST['payment_method'] ?? 'bank_transfer', $now, $invId]);
        header("Location: saas.php?tab=tenants&msg=paid");
        exit;
    }
}

$plans = $d->query("SELECT * FROM subscription_plans ORDER BY price_monthly")->fetchAll();
$tenants = $d->query("SELECT * FROM tenants ORDER BY id DESC")->fetchAll();

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
        $msgs = ['tenant_created'=>'Tenant dibuat dengan uji coba 14 hari','subscribed'=>'Langganan diaktifkan','paid'=>'Faktur berhasil dibayar','error'=>'Terjadi kesalahan'];
        echo $msgs[$msg] ?? $msg;
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab==='plans'?'active':'' ?>" href="?tab=plans">Paket Langganan</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='tenants'?'active':'' ?>" href="?tab=tenants">Tenant</a></li>
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
                        <h3><?= rupiah($plan['price_monthly']) ?><small class="text-muted">/mo</small></h3>
                        <p class="small"><?= rupiah($plan['price_yearly']) ?>/yr</p>
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
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>Kode</th><th>Nama</th><th>Subdomain</th><th>Status</th><th>Trial Ends</th><th>Subscription Ends</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['code']) ?></td>
                <td><?= htmlspecialchars($t['name']) ?></td>
                <td><?= htmlspecialchars($t['subdomain']) ?></td>
                <td><span class="badge bg-<?= $t['status']==='active'?'success':($t['status']==='trial'?'info':($t['status']==='suspended'?'warning':'danger')) ?>"><?= $t['status'] === 'active' ? 'Aktif' : ($t['status'] === 'trial' ? 'Uji Coba' : ($t['status'] === 'suspended' ? 'Ditangguhkan' : 'Berhenti')) ?></span></td>
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
        </table></div>
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
                <div class="col-md-6"><label class="form-label">Telepon</label><input type="text" class="form-control" name="company_phone"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="company_email"></div>
            </div>
            <div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="company_address"></textarea></div>
            <div class="mb-3"><label class="form-label">Trial Plan</label><select class="form-select" name="plan_code">
                <?php foreach ($plans as $p): ?><option value="<?= $p['code'] ?>"><?= $p['name'] ?></option><?php endforeach; ?>
            </select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Create Tenant</button></div>
    </form>
</div></div></div>
<?php renderFoot(); ?>
