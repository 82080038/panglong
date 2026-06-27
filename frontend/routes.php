<?php
require_once 'config.php';

$d = db();

$vehicles = $d->query("SELECT id, plate_no FROM vehicles WHERE status='active' ORDER BY plate_no")->fetchAll();
$deliveries = $d->query("SELECT id, delivery_no, customer_name, delivery_address, phone FROM deliveries WHERE status != 'delivered' ORDER BY id DESC LIMIT 50")->fetchAll();
$routes = $d->query("SELECT dr.*, v.plate_no FROM delivery_routes dr LEFT JOIN vehicles v ON dr.vehicle_id = v.id ORDER BY dr.id DESC LIMIT 20")->fetchAll();

renderHead('Delivery Routes');
renderNav('routes');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Rute Pengiriman</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#routeModal" onclick="resetRouteForm()"><i class="bi bi-plus-circle"></i> New Route</button>
    </div>
    <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>Route No</th><th>Tanggal</th><th>Vehicle</th><th>Driver</th><th>Stops</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($routes as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['route_no']) ?></td>
                    <td><?= htmlspecialchars($r['route_date']) ?></td>
                    <td><?= htmlspecialchars($r['plate_no'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['driver_name'] ?? '-') ?></td>
                    <td><?= (isset($r['total_distance_km']) && is_numeric($r['total_distance_km']) && $r['total_distance_km'] > 0) ? number_format($r['total_distance_km'], 1) . ' km' : '-' ?></td>
                    <td><span class="badge bg-<?= $r['status']==='completed'?'success':($r['status']==='in_progress'?'info':'warning') ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewRoute(<?= $r['id'] ?>)"><i class="bi bi-eye"></i></button>
                        <?php if ($r['status'] === 'planned'): ?>
                        <button class="btn btn-sm btn-success" onclick="startRoute(<?= $r['id'] ?>)"><i class="bi bi-play"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
</div>

<div class="modal fade" id="routeModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New Delivery Route</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row mb-3">
            <div class="col-md-4"><label class="form-label">Route Date</label><input type="date" class="form-control" id="rDate" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-4"><label class="form-label">Vehicle</label><select class="form-select" id="rVehicle"><option value="">Select Vehicle</option><?php foreach ($vehicles as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['plate_no']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Driver Name</label><input type="text" class="form-control" id="rDriver"></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label">Est. Distance (km)</label><input type="number" class="form-control" id="rDistance" step="0.1" min="0"></div>
            <div class="col-md-6"><label class="form-label">Est. Time (minutes)</label><input type="number" class="form-control" id="rTime" min="0"></div>
        </div>
        <div class="mb-3"><label class="form-label">Stops (Deliveries)</label>
            <div id="stopList">
                <?php foreach ($deliveries as $dl): ?>
                <div class="form-check">
                    <input class="form-check-input stop-check" type="checkbox" value="<?= $dl['id'] ?>" data-customer="<?= htmlspecialchars($dl['customer_name'] ?? '') ?>" data-address="<?= htmlspecialchars($dl['delivery_address'] ?? '') ?>" data-phone="<?= htmlspecialchars($dl['phone'] ?? '') ?>">
                    <label class="form-check-label"><?= htmlspecialchars($dl['delivery_no']) ?> - <?= htmlspecialchars($dl['customer_name'] ?? 'Unknown') ?> - <?= htmlspecialchars($dl['delivery_address'] ?? '') ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mb-3"><label class="form-label">Catatan</label><input type="text" class="form-control" id="rNotes"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitRoute()">Create Route</button></div>
</div></div></div>

<div class="modal fade" id="routeDetailModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Route Detail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="routeDetailBody"></div>
</div></div></div>

<script>
function resetRouteForm() { document.querySelectorAll('.stop-check').forEach(c => c.checked = false); }

function submitRoute() {
    const stops = [];
    document.querySelectorAll('.stop-check:checked').forEach(c => {
        stops.push({ delivery_id: parseInt(c.value), customer_name: c.dataset.customer, address: c.dataset.address, phone: c.dataset.phone });
    });
    if (stops.length === 0) { alert('Select at least 1 stop'); return; }
    fetch(API_URL+'?endpoint=delivery-routes', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ route_date: document.getElementById('rDate').value, vehicle_id: parseInt(document.getElementById('rVehicle').value)||null, driver_name: document.getElementById('rDriver').value, total_distance_km: parseFloat(document.getElementById('rDistance').value)||null, estimated_time_minutes: parseInt(document.getElementById('rTime').value)||null, notes: document.getElementById('rNotes').value, stops }) })
    .then(r=>r.json()).then(res=>{ if(res.success){alert('Route created: '+res.data.route_no); location.reload();} else alert('Kesalahan: '+res.message); });
}

function viewRoute(id) {
    fetch(`${API_URL}?endpoint=delivery-routes&id=${id}`).then(r=>r.json()).then(res=>{
        if(res.success) {
            const rt = res.data;
            let html = `<h6>${rt.route_no}</h6><p>Tanggal: ${rt.route_date} | Kendaraan: ${rt.plate_no||'-'} | Sopir: ${rt.driver_name||'-'} | Status: ${rt.status==='completed'?'Selesai':(rt.status==='in_progress'?'Berjalan':(rt.status==='cancelled'?'Dibatalkan':'Pending'))}</p>`;
            html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>Customer</th><th>Alamat</th><th>Telepon</th><th>Status</th><th>Action</th></tr></thead><tbody>';
            rt.stops?.forEach(s => {
                html += `<tr><td>${s.stop_order}</td><td>${s.customer_name||'-'}</td><td>${s.address||'-'}</td><td>${s.phone||'-'}</td><td><span class="badge bg-${s.status==='completed'?'success':(s.status==='in_progress'?'info':'warning')}">${s.status==='completed'?'Selesai':(s.status==='in_progress'?'Berjalan':'Pending')}</span></td>`;
                if (rt.status === 'in_progress' && s.status !== 'completed') {
                    html += `<td><button class="btn btn-sm btn-success" onclick="completeStop(${id}, ${s.id})">Complete</button></td>`;
                } else { html += '<td></td>'; }
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            document.getElementById('routeDetailBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('routeDetailModal')).show();
        }
    });
}

function startRoute(id) { if(!confirm('Start route?'))return; fetch(`${API_URL}?endpoint=delivery-routes&id=${id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({status:'in_progress'})}).then(r=>r.json()).then(res=>{if(res.success)location.reload();}); }
function completeStop(routeId, stopId) { fetch(`${API_URL}?endpoint=delivery-routes&id=${routeId}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({stop_id: stopId, stop_status: 'completed'})}).then(r=>r.json()).then(res=>{ if(res.success) viewRoute(routeId); }); }
</script>
<?php renderFoot(); ?>
