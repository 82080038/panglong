<?php
require_once 'config.php';

$sensorsResp = apiCall('/iot/sensors');
$sensors = $sensorsResp['body']['data'] ?? [];

$alertsResp = apiCall('/iot/alerts');
$alerts = $alertsResp['body']['data'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'register_sensor') {
        $result = apiCall('/iot/sensors', 'POST', [
            'sensor_id' => $_POST['sensor_id'],
            'name' => $_POST['name'],
            'type' => $_POST['type'],
            'location' => $_POST['location'] ?? null,
        ]);
        header('Location: iot.php?msg=' . ($result['code'] === 201 ? 'registered' : 'error'));
        exit;
    }
}

$msg = $_GET['msg'] ?? '';
renderHead('IoT Sensors');
renderNav('iot');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1><i class="bi bi-cpu"></i> IoT Smart Warehouse</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sensorModal"><i class="bi bi-plus"></i> Register Sensor</button>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg==='error'?'danger':'success' ?> alert-dismissible fade show">
        <?= $msg==='registered'?'Sensor registered successfully':'Error' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($alerts)): ?>
    <div class="alert alert-danger">
        <h6><i class="bi bi-exclamation-triangle"></i> Active Alerts (<?= count($alerts) ?>)</h6>
        <ul class="mb-0">
        <?php foreach ($alerts as $a): ?>
            <li><strong><?= htmlspecialchars($a['sensor']) ?></strong>: <?= htmlspecialchars($a['message']) ?> (value: <?= $a['value'] ?>)</li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <table class="table table-striped">
            <thead><tr><th>Sensor ID</th><th>Name</th><th>Type</th><th>Location</th><th>Last Reading</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!empty($sensors)): foreach ($sensors as $s): ?>
                <?php $lastReading = $s['readings'][0] ?? null; ?>
            <tr>
                <td><?= htmlspecialchars($s['sensor_id']) ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><span class="badge bg-info"><?= ucfirst($s['type']) ?></span></td>
                <td><?= htmlspecialchars($s['location'] ?? '-') ?></td>
                <td><?= $lastReading ? htmlspecialchars($lastReading['value']) . ' ' . htmlspecialchars($lastReading['unit'] ?? '') : 'No data' ?></td>
                <td><span class="badge bg-<?= $s['is_active']?'success':'danger' ?>"><?= $s['is_active']?'Active':'Inactive' ?></span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted">No sensors registered</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div></div>
</div>

<div class="modal fade" id="sensorModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="iot.php"><input type="hidden" name="action" value="register_sensor">
        <div class="modal-header"><h5 class="modal-title">Register IoT Sensor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Sensor ID *</label><input type="text" class="form-control" name="sensor_id" required placeholder="e.g. TEMP-001"></div>
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" required></div>
            <div class="mb-3"><label class="form-label">Type *</label><select class="form-select" name="type" required>
                <option value="temperature">Temperature</option>
                <option value="humidity">Humidity</option>
                <option value="weight">Weight/Load</option>
                <option value="proximity">Proximity</option>
                <option value="door">Door Sensor</option>
            </select></div>
            <div class="mb-3"><label class="form-label">Location</label><input type="text" class="form-control" name="location" placeholder="e.g. Warehouse A - Rack 3"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Register</button></div>
    </form>
</div></div></div>
<?php renderFoot(); ?>
