<?php
require_once 'config.php';

$d = db();

// app_settings uses key-value store: (id, key, value, type, description, created_at, updated_at, tenant_id)
$rows = $d->query("SELECT key, value FROM app_settings")->fetchAll();
$settings = [];
foreach ($rows as $row) {
    $settings[$row['key']] = $row['value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $now = date('Y-m-d H:i:s');
    $newSettings = [
        'tax_rate' => $_POST['tax_rate'] ?? '0.11',
        'tax_enabled' => isset($_POST['tax_enabled']) ? '1' : '0',
        'company_name' => $_POST['company_name'] ?? '',
        'company_address' => $_POST['company_address'] ?? '',
        'company_phone' => $_POST['company_phone'] ?? '',
        'session_timeout_minutes' => $_POST['session_timeout_minutes'] ?? '30',
    ];

    foreach ($newSettings as $key => $value) {
        $stmt = $d->prepare("SELECT id FROM app_settings WHERE key = ?");
        $stmt->execute([$key]);
        $existing = $stmt->fetch();
        if ($existing) {
            $d->prepare("UPDATE app_settings SET value = ?, updated_at = ? WHERE id = ?")->execute([$value, $now, $existing['id']]);
        } else {
            $d->prepare("INSERT INTO app_settings (key, value, type, description, created_at, updated_at) VALUES (?, ?, 'string', ?, ?, ?)")->execute([$key, $value, $key, $now, $now]);
        }
    }
    header("Location: settings.php?msg=saved");
    exit;
}

$msg = $_GET['msg'] ?? '';

renderHead('Settings');
renderNav('settings');
?>
<div class="container mt-4">
    <h1>Pengaturan</h1>
    <?php if ($msg === 'saved'): ?><div class="alert alert-success">Settings saved Berhasil.</div><?php endif; ?>
    <?php if ($msg === 'error'): ?><div class="alert alert-danger">Error saving settings.</div><?php endif; ?>
    <form method="POST" action="settings.php">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Tax Configuration</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tax Rate (PPN)</label>
                        <input type="number" class="form-control" name="tax_rate" value="<?= $settings['tax_rate'] ?? '0.11' ?>" step="0.01" min="0" max="1">
                        <small class="text-muted">0.11 = 11%</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" name="tax_enabled" id="taxEnabled" <?= ($settings['tax_enabled'] ?? false) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="taxEnabled">Enable PPN Tax</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Company Information</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nama Perusahaan</label><input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Telepon</label><input type="text" class="form-control" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>"></div>
                    <div class="col-md-12 mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="company_address" rows="2"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea></div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">System</h5></div>
            <div class="card-body">
                <div class="col-md-4 mb-3"><label class="form-label">Session Timeout (minutes)</label><input type="number" class="form-control" name="session_timeout_minutes" value="<?= $settings['session_timeout_minutes'] ?? '30' ?>" min="5" max="1440"></div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Settings</button>
    </form>
</div>
<?php renderFoot(); ?>
