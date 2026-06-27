<?php
require_once 'config.php';

$d = db();
$user = currentUser();
$tenantId = $user['tenant_id'] ?? null;
$isSuperAdmin = $user['role_slug'] === 'super_admin';

// Fetch delivery methods for dropdown
$deliveryMethods = $d->query("SELECT code, name FROM delivery_methods WHERE is_active = 1 ORDER BY name")->fetchAll();

$deliverySql = "SELECT * FROM deliveries";
if (!$isSuperAdmin && $tenantId) {
    $deliverySql .= " WHERE tenant_id = $tenantId";
}
$deliverySql .= " ORDER BY id DESC LIMIT 50";
$deliveries = $d->query($deliverySql)->fetchAll();

renderHead('Deliveries');
renderNav('deliveries');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Pengiriman (Surat Jalan)</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deliveryModal" onclick="resetDeliveryForm()">
            <i class="bi bi-plus-circle"></i> Pengiriman Baru
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped">
                <thead><tr><th>Nomor Kirim</th><th>Tanggal</th><th>Pelanggan</th><th>Alamat</th><th>Driver</th><th>Vehicle</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($deliveries as $del): ?>
                    <tr>
                        <td><?= htmlspecialchars($del['delivery_no']) ?></td>
                        <td><?= tglIndo($del['delivery_date']) ?></td>
                        <td><?= htmlspecialchars($del['customer_name']) ?></td>
                        <td><?= htmlspecialchars(substr($del['delivery_address'] ?? '', 0, 30)) ?></td>
                        <td><?= htmlspecialchars($del['driver_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($del['vehicle_plate'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= $del['status']==='delivered'?'success':($del['status']==='in_transit'?'primary':($del['status']==='failed'?'danger':'warning')) ?>"><?= ucfirst(str_replace('_',' ',$del['status'])) ?></span></td>
                        <td>
                            <select class="form-select form-select-sm d-inline-block" style="width:auto" onchange="updateStatus(<?= $del['id'] ?>, this.value)">
                                <option value="">Update Status</option>
                                <?php 
                                $deliveryStatuses = $d->query("SELECT code, name FROM status_codes WHERE module = 'deliveries' AND is_active = 1 ORDER BY name")->fetchAll();
                                foreach ($deliveryStatuses as $ds): ?>
                                    <option value="<?= htmlspecialchars($ds['code']) ?>"><?= htmlspecialchars($ds['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<div class="modal fade" id="deliveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Pengiriman Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="deliveryForm">
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">Nama Pelanggan *</label><input type="text" class="form-control" id="delCustomerName" required></div>
                        <div class="col-md-6"><label class="form-label">Telepon</label><input type="text" class="form-control" id="delPhone"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Alamat Kirim</label><textarea class="form-control" id="delAddress" rows="2"></textarea></div>
                    <div class="row mb-3">
                        <div class="col-md-4"><label class="form-label">Tanggal Kirim *</label><input type="date" class="form-control" id="delDate" required></div>
                        <div class="col-md-4"><label class="form-label">Time</label><input type="time" class="form-control" id="delTime"></div>
                        <div class="col-md-4"><label class="form-label">Metode Pengiriman</label><select class="form-select" id="delMethod"><?php if (is_array($deliveryMethods)): foreach ($deliveryMethods as $dm): ?><option value="<?php echo htmlspecialchars($dm['code']); ?>"><?php echo htmlspecialchars($dm['name']); ?></option><?php endforeach; endif; ?></select></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">Nama Sopir</label><input type="text" class="form-control" id="delDriver"></div>
                        <div class="col-md-6"><label class="form-label">Plat Kendaraan</label><input type="text" class="form-control" id="delPlate"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Catatan</label><textarea class="form-control" id="delNotes" rows="2"></textarea></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" onclick="submitDelivery()">Buat</button></div>
        </div>
    </div>
</div>

<script>
function resetDeliveryForm() { document.getElementById('deliveryForm').reset(); document.getElementById('delDate').value = new Date().toISOString().split('T')[0]; }

function submitDelivery() {
    const data = {
        customer_name: document.getElementById('delCustomerName').value,
        delivery_address: document.getElementById('delAddress').value,
        phone: document.getElementById('delPhone').value,
        delivery_date: document.getElementById('delDate').value,
        delivery_time: document.getElementById('delTime').value,
        driver_name: document.getElementById('delDriver').value,
        vehicle_plate: document.getElementById('delPlate').value,
        notes: document.getElementById('delNotes').value,
    };
    fetch(API_URL+'?endpoint=deliveries', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Delivery created: ' + res.data.delivery_no); location.reload(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}

function updateStatus(id, status) {
    if (!status) return;
    fetch(`${API_URL}?endpoint=deliveries&id=${id}`, {
        method: 'PUT', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status })
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Status updated'); location.reload(); }
        else { alert('Kesalahan: ' + res.message); }
    });
}
</script>
<?php renderFoot(); ?>
