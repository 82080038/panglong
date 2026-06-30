<?php
require_once 'config.php';
requirePermission('manage_iot');

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

$sensors = [];
try {
    $stmt = $d->prepare("SELECT * FROM iot_sensors" . ($isSuperAdmin ? "" : " WHERE tenant_id = ?") . " ORDER BY id DESC");
    $stmt->execute($isSuperAdmin ? [] : [$tenantId]);
    $sensors = $stmt->fetchAll();
} catch (Exception $e) {
    $sensors = [];
}

// Fetch last reading and recent readings for each sensor
$lastStmt = $d->prepare("SELECT value, unit, read_at FROM iot_sensor_readings WHERE sensor_id = ? ORDER BY read_at DESC LIMIT 1");
$chartStmt = $d->prepare("SELECT value, read_at FROM iot_sensor_readings WHERE sensor_id = ? ORDER BY read_at DESC LIMIT 20");
foreach ($sensors as $i => $s) {
    $lastStmt->execute([$s['id']]);
    $sensors[$i]['last_reading'] = $lastStmt->fetch(PDO::FETCH_ASSOC);
    $chartStmt->execute([$s['id']]);
    $sensors[$i]['readings'] = array_reverse($chartStmt->fetchAll(PDO::FETCH_ASSOC));
}

$alerts = [];
foreach ($sensors as $s) {
    $lr = $s['last_reading'] ?? null;
    if ($lr) {
        if ($s['type'] === 'temperature' && $lr['value'] > 35) {
            $alerts[] = ['sensor' => $s['name'], 'message' => 'Temperature too high', 'value' => $lr['value'] . ' ' . ($lr['unit'] ?? 'C')];
        } elseif ($s['type'] === 'humidity' && $lr['value'] > 80) {
            $alerts[] = ['sensor' => $s['name'], 'message' => 'Humidity too high', 'value' => $lr['value'] . ' ' . ($lr['unit'] ?? '%')];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    if (($_POST['action'] ?? '') === 'register_sensor') {
        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $d->prepare("INSERT INTO iot_sensors (tenant_id, sensor_id, name, type, location, is_active, created_at, updated_at) VALUES (?,?,?,?,?,1,?,?)");
            $stmt->execute([$tenantId, $_POST['sensor_id'], $_POST['name'], $_POST['type'], $_POST['location'] ?? null, $now, $now]);
            header('Location: iot.php?msg=registered');
        } catch (Exception $e) {
            header('Location: iot.php?msg=error');
        }
        exit;
    } elseif (($_POST['action'] ?? '') === 'record_reading') {
        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $d->prepare("INSERT INTO iot_sensor_readings (sensor_id, value, unit, read_at, created_at, updated_at) VALUES (?,?,?,?,?,?)");
            $stmt->execute([(int)$_POST['sensor_id'], (float)$_POST['value'], $_POST['unit'] ?? null, $now, $now, $now]);
            header('Location: iot.php?msg=reading_recorded');
        } catch (Exception $e) {
            header('Location: iot.php?msg=error');
        }
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
        <?= $msg==='registered'?'Sensor berhasil terdaftar':($msg==='reading_recorded'?'Data pembacaan tersimpan':'Error') ?>
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

    <script src="assets/js/chart.umd.min.js"></script>
    <?php if (!empty($sensors)): foreach ($sensors as $s): ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><strong><?= htmlspecialchars($s['sensor_id']) ?></strong> — <?= htmlspecialchars($s['name']) ?> <span class="badge bg-info"><?= ucfirst($s['type']) ?></span></div>
            <div class="text-end">
                <span class="badge bg-<?= $s['is_active']?'success':'danger' ?>"><?= $s['is_active']?'Active':'Inactive' ?></span>
                <button class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#readingModal<?= $s['id'] ?>"><i class="bi bi-plus"></i> Reading</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p class="mb-1 text-muted">Location</p>
                    <p class="fw-bold"><?= htmlspecialchars($s['location'] ?? '-') ?></p>
                    <p class="mb-1 text-muted">Last Reading</p>
                    <p class="fw-bold fs-5"><?= ($s['last_reading']['value'] ?? '') !== '' ? htmlspecialchars($s['last_reading']['value'] . ' ' . ($s['last_reading']['unit'] ?? '')) : 'Tidak ada data' ?></p>
                    <p class="text-muted small"><?= $s['last_reading']['read_at'] ?? '' ?></p>
                </div>
                <div class="col-md-9">
                    <canvas id="chartSensor<?= $s['id'] ?>" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="readingModal<?= $s['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="iot.php"><input type="hidden" name="action" value="record_reading"><input type="hidden" name="sensor_id" value="<?= $s['id'] ?>">
            <div class="modal-header"><h5 class="modal-title">Record Reading — <?= htmlspecialchars($s['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Value *</label><input type="number" class="form-control" name="value" step="0.01" required></div>
                <div class="mb-3"><label class="form-label">Unit</label><input type="text" class="form-control" name="unit" value="<?= $s['type']==='temperature'?'C':($s['type']==='humidity'?'%':($s['type']==='weight'?'kg':'')) ?>"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div></div>

    <script>
    (function(){
        const ctx = document.getElementById('chartSensor<?= $s['id'] ?>').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function($r){ return date('H:i', strtotime($r['read_at'])); }, $s['readings'])) ?>,
                datasets: [{
                    label: '<?= ucfirst($s['type']) ?>',
                    data: <?= json_encode(array_column($s['readings'], 'value')) ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    fill: false,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    })();
    </script>
    <?php endforeach; else: ?>
    <div class="card"><div class="card-body"><p class="text-center text-muted mb-0">Belum ada sensor terdaftar</p></div></div>
    <?php endif; ?>
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
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Daftar</button></div>
    </form>
</div></div></div>
<?php renderFoot(); ?>
