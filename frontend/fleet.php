<?php
require_once 'config.php';

$d = db();

$vehicles = $d->query("SELECT * FROM vehicles ORDER BY id DESC LIMIT 50")->fetchAll();
$maintenance = $d->query("SELECT vm.*, v.plate_no FROM vehicle_maintenance vm LEFT JOIN vehicles v ON vm.vehicle_id = v.id ORDER BY vm.id DESC LIMIT 50")->fetchAll();

renderHead('Fleet Management');
renderNav('fleet');
?>
<div class="container mt-4">
    <h1>Manajemen Kendaraan</h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#vehicles">Vehicles</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#maintenance">Maintenance</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="vehicles">
            <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#vehicleModal"><i class="bi bi-plus"></i> Tambah Kendaraan</button>
            <div class="card"><div class="card-body">
                <div class="table-responsive"><table class="table table-striped">
                    <thead><tr><th>Plate No</th><th>Type</th><th>Brand/Model</th><th>Capacity (kg)</th><th>Fuel</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['plate_no']) ?></td>
                            <td><?= htmlspecialchars($v['vehicle_type'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(($v['brand'] ?? '') . ' ' . ($v['model'] ?? '')) ?></td>
                            <td><?= $v['capacity_kg'] ? number_format($v['capacity_kg'], 0) . ' kg' : '-' ?></td>
                            <td><?= htmlspecialchars($v['fuel_type'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $v['status']==='active'?'success':'secondary' ?>"><?= $v['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td><button class="btn btn-sm btn-danger" onclick="deleteVehicle(<?= $v['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div></div>
        </div>
        <div class="tab-pane fade" id="maintenance">
            <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#maintModal"><i class="bi bi-plus"></i> Tambah Servis Record</button>
            <div class="card"><div class="card-body">
                <div class="table-responsive"><table class="table table-striped">
                    <thead><tr><th>Vehicle</th><th>Tanggal</th><th>Type</th><th>Cost</th><th>Kilometer</th><th>Servis Berikutnya</th><th>Description</th></tr></thead>
                    <tbody>
                        <?php foreach ($maintenance as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['plate_no'] ?? '-') ?></td>
                            <td><?= tglIndo($m['maintenance_date']) ?></td>
                            <td><?= htmlspecialchars($m['maintenance_type'] ?? '-') ?></td>
                            <td><?= rupiah($m['cost']) ?></td>
                            <td><?= $m['odometer_km'] ? number_format($m['odometer_km'], 0) . ' km' : '-' ?></td>
                            <td><?= htmlspecialchars($m['next_maintenance_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($m['description'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div></div>
        </div>
    </div>
</div>

<div class="modal fade" id="vehicleModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Tambah Kendaraan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Plate No *</label><input type="text" class="form-control" id="vPlate" required></div>
        <div class="mb-3"><label class="form-label">Type</label><select class="form-select" id="vType"><option value="truck">Truck</option><option value="pickup">Pickup</option><option value="engkel">Engkel</option><option value="colt_diesel">Colt Diesel</option><option value="motorcycle">Motorcycle</option></select></div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Brand</label><input type="text" class="form-control" id="vBrand"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Model</label><input type="text" class="form-control" id="vModel"></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Capacity (kg)</label><input type="number" class="form-control" id="vCapacity" min="0"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Fuel</label><select class="form-select" id="vFuel"><option value="diesel">Diesel</option><option value="petrol">Petrol</option><option value="electric">Electric</option></select></div>
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitVehicle()">Simpan</button></div>
</div></div></div>

<div class="modal fade" id="maintModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Tambah Servis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Vehicle *</label><select class="form-select" id="mVehicle"><?php foreach ($vehicles as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['plate_no']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Tanggal</label><input type="date" class="form-control" id="mDate" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-3"><label class="form-label">Type</label><select class="form-select" id="mType"><option value="service">Service</option><option value="oil_change">Oil Change</option><option value="tire_replacement">Tire Replacement</option><option value="brake_service">Brake Service</option><option value="general_repair">General Repair</option><option value="registration">Registration/STNK</option></select></div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Cost</label><input type="number" class="form-control" id="mCost" min="0" value="0"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Kilometer (km)</label><input type="number" class="form-control" id="mOdo" min="0"></div>
        </div>
        <div class="mb-3"><label class="form-label">Servis Berikutnya Date</label><input type="date" class="form-control" id="mNext"></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="mDesc" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitMaint()">Simpan</button></div>
</div></div></div>

<script>
function submitVehicle() {
    fetch(API_URL+'?endpoint=vehicles', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ plate_no: document.getElementById('vPlate').value, vehicle_type: document.getElementById('vType').value, brand: document.getElementById('vBrand').value, model: document.getElementById('vModel').value, capacity_kg: parseFloat(document.getElementById('vCapacity').value)||null, fuel_type: document.getElementById('vFuel').value }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
function deleteVehicle(id) { if(!confirm('Deactivate vehicle?'))return; fetch(`${API_URL}?endpoint=vehicles&id=${id}`,{method:'DELETE'}).then(r=>r.json()).then(res=>{if(res.success)location.reload();}); }
function submitMaint() {
    fetch(API_URL+'?endpoint=vehicle-maintenance', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ vehicle_id: parseInt(document.getElementById('mVehicle').value), maintenance_date: document.getElementById('mDate').value, maintenance_type: document.getElementById('mType').value, cost: parseFloat(document.getElementById('mCost').value), odometer_km: parseInt(document.getElementById('mOdo').value)||null, next_maintenance_date: document.getElementById('mNext').value||null, description: document.getElementById('mDesc').value }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}
</script>
<?php renderFoot(); ?>
