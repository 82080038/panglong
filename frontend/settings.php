<?php
require_once 'config.php';

$settingsResp = apiCall('/settings');
$settings = $settingsResp['body']['data'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsData = [
        ['key' => 'tax_rate', 'value' => $_POST['tax_rate'] ?? '0.11', 'type' => 'float'],
        ['key' => 'tax_enabled', 'value' => isset($_POST['tax_enabled']) ? '1' : '0', 'type' => 'boolean'],
        ['key' => 'company_name', 'value' => $_POST['company_name'] ?? '', 'type' => 'string'],
        ['key' => 'company_address', 'value' => $_POST['company_address'] ?? '', 'type' => 'string'],
        ['key' => 'company_phone', 'value' => $_POST['company_phone'] ?? '', 'type' => 'string'],
        ['key' => 'session_timeout_minutes', 'value' => $_POST['session_timeout_minutes'] ?? '30', 'type' => 'integer'],
    ];
    $result = apiCall('/settings', 'PUT', ['settings' => $settingsData]);
    $msg = ($result['body']['success'] ?? false) ? 'saved' : 'error';
    header("Location: settings.php?msg=$msg");
    exit;
}

$msg = $_GET['msg'] ?? '';

renderHead('Settings');
renderNav('settings');
?>
<div class="container mt-4">
    <h1>Settings</h1>
    <?php if ($msg === 'saved'): ?><div class="alert alert-success">Settings saved successfully.</div><?php endif; ?>
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
                    <div class="col-md-6 mb-3"><label class="form-label">Company Name</label><input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>"></div>
                    <div class="col-md-12 mb-3"><label class="form-label">Address</label><textarea class="form-control" name="company_address" rows="2"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea></div>
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
