<?php
require_once 'config.php';

$resp = apiCall('/deliveries?per_page=50');
$deliveries = $resp['body']['data'] ?? [];

renderHead('Deliveries');
renderNav('deliveries');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Deliveries (Surat Jalan)</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deliveryModal" onclick="resetDeliveryForm()">
            <i class="bi bi-plus-circle"></i> New Delivery
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>Delivery No</th><th>Date</th><th>Customer</th><th>Address</th><th>Driver</th><th>Vehicle</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($deliveries as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['delivery_no']) ?></td>
                        <td><?= htmlspecialchars($d['delivery_date']) ?></td>
                        <td><?= htmlspecialchars($d['customer_name']) ?></td>
                        <td><?= htmlspecialchars(substr($d['delivery_address'] ?? '', 0, 30)) ?></td>
                        <td><?= htmlspecialchars($d['driver_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($d['vehicle_plate'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= $d['status']==='delivered'?'success':($d['status']==='in_transit'?'primary':($d['status']==='failed'?'danger':'warning')) ?>"><?= ucfirst(str_replace('_',' ',$d['status'])) ?></span></td>
                        <td>
                            <select class="form-select form-select-sm d-inline-block" style="width:auto" onchange="updateStatus(<?= $d['id'] ?>, this.value)">
                                <option value="">Update Status</option>
                                <option value="loaded">Loaded</option>
                                <option value="in_transit">In Transit</option>
                                <option value="delivered">Delivered</option>
                                <option value="failed">Failed</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="deliveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">New Delivery</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="deliveryForm">
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">Customer Name *</label><input type="text" class="form-control" id="delCustomerName" required></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" id="delPhone"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Delivery Address</label><textarea class="form-control" id="delAddress" rows="2"></textarea></div>
                    <div class="row mb-3">
                        <div class="col-md-4"><label class="form-label">Delivery Date *</label><input type="date" class="form-control" id="delDate" required></div>
                        <div class="col-md-4"><label class="form-label">Time</label><input type="time" class="form-control" id="delTime"></div>
                        <div class="col-md-4"><label class="form-label">Vehicle Plate</label><input type="text" class="form-control" id="delPlate"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Driver Name</label><input type="text" class="form-control" id="delDriver"></div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" id="delNotes" rows="2"></textarea></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="submitDelivery()">Create</button></div>
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
    fetch('http://127.0.0.1:8000/api/v1/deliveries', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Delivery created: ' + res.data.delivery_no); location.reload(); }
        else { alert('Error: ' + res.message); }
    });
}

function updateStatus(id, status) {
    if (!status) return;
    fetch(`http://127.0.0.1:8000/api/v1/deliveries/${id}/status`, {
        method: 'PUT', headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer <?= $_SESSION['token'] ?>' },
        body: JSON.stringify({ status })
    }).then(r => r.json()).then(res => {
        if (res.success) { alert('Status updated'); location.reload(); }
        else { alert('Error: ' + res.message); }
    });
}
</script>
<?php renderFoot(); ?>
