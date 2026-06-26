<?php
require_once 'config.php';

$d = db();

$templates = $d->query("SELECT * FROM whatsapp_templates WHERE is_active = 1 ORDER BY id")->fetchAll();
$messages = $d->query("SELECT * FROM whatsapp_messages ORDER BY id DESC LIMIT 50")->fetchAll();
$customers = $d->query("SELECT id, name, phone FROM customers WHERE phone IS NOT NULL AND phone != '' ORDER BY name LIMIT 200")->fetchAll();

renderHead('WhatsApp');
renderNav('whatsapp');
?>
<div class="container mt-4">
    <h1>WhatsApp Notification</h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#send">Send Message</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#templates">Templates</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#history">History</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="send">
            <div class="card"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Template</label>
                    <select class="form-select" id="waTemplate" onchange="loadTemplate()">
                        <option value="">Custom Message</option>
                        <?php foreach ($templates as $t): ?>
                        <option value="<?= $t['id'] ?>" data-body="<?= htmlspecialchars($t['message_body']) ?>" data-vars="<?= htmlspecialchars($t['variables'] ?? '') ?>"><?= htmlspecialchars($t['template_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" class="form-control" id="waPhone" placeholder="628123456789">
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" id="waMessage" rows="4"></textarea>
                </div>
                <button class="btn btn-success" onclick="sendWA()"><i class="bi bi-whatsapp"></i> Send Message</button>
            </div></div>
        </div>
        <div class="tab-pane fade" id="templates">
            <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#tplModal"><i class="bi bi-plus"></i> Add Template</button>
            <div class="card"><div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Nama</th><th>Type</th><th>Message</th><th>Variables</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($templates as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['template_name']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($t['template_type']) ?></span></td>
                            <td style="max-width:400px;white-space:pre-wrap;font-size:0.85em;"><?= htmlspecialchars($t['message_body']) ?></td>
                            <td><code><?= htmlspecialchars($t['variables'] ?? '') ?></code></td>
                            <td><button class="btn btn-sm btn-warning" onclick='editTemplate(<?= json_encode($t) ?>)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
        <div class="tab-pane fade" id="history">
            <div class="card"><div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Tanggal</th><th>Telepon</th><th>Template</th><th>Message</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($messages as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['created_at']) ?></td>
                            <td><?= htmlspecialchars($m['phone_number']) ?></td>
                            <td><?= htmlspecialchars($m['template_name'] ?? '-') ?></td>
                            <td style="max-width:400px;font-size:0.85em;"><?= htmlspecialchars($m['message_body']) ?></td>
                            <td><span class="badge bg-<?= $m['status']==='sent'?'success':'danger' ?>"><?= ucfirst($m['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>
</div>

<div class="modal fade" id="tplModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add Template</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Template Name *</label><input type="text" class="form-control" id="tplName"></div>
        <div class="mb-3"><label class="form-label">Type</label><select class="form-select" id="tplType"><option value="notification">Notification</option><option value="reminder">Reminder</option><option value="confirmation">Confirmation</option><option value="marketing">Marketing</option></select></div>
        <div class="mb-3"><label class="form-label">Message Body</label><textarea class="form-control" id="tplBody" rows="4"></textarea></div>
        <div class="mb-3"><label class="form-label">Variables (comma-separated)</label><input type="text" class="form-control" id="tplVars" placeholder="customer_name,invoice_no,total"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" onclick="submitTpl()">Simpan</button></div>
</div></div></div>

<script>
function loadTemplate() {
    const sel = document.getElementById('waTemplate');
    if (sel.value) {
        document.getElementById('waMessage').value = sel.selectedOptions[0].dataset.body || '';
    }
}

function sendWA() {
    const phone = document.getElementById('waPhone').value.trim();
    const msg = document.getElementById('waMessage').value.trim();
    if (!phone || !msg) { alert('Phone and message required'); return; }
    const tplName = document.getElementById('waTemplate').selectedOptions[0]?.text || null;
    fetch(API_URL+'?endpoint=whatsapp-messages', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ phone_number: phone, message_body: msg, template_name: tplName }) })
    .then(r=>r.json()).then(res=>{ if(res.success){alert('Message sent (logged)'); location.reload();} else alert('Kesalahan: '+res.message); });
}

function submitTpl() {
    fetch(API_URL+'?endpoint=whatsapp-templates', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ template_name: document.getElementById('tplName').value, template_type: document.getElementById('tplType').value, message_body: document.getElementById('tplBody').value, variables: document.getElementById('tplVars').value }) })
    .then(r=>r.json()).then(res=>{ if(res.success) location.reload(); else alert(res.message); });
}

function editTemplate(t) {
    document.getElementById('tplName').value = t.template_name;
    document.getElementById('tplType').value = t.template_type;
    document.getElementById('tplBody').value = t.message_body;
    document.getElementById('tplVars').value = t.variables || '';
    new bootstrap.Modal(document.getElementById('tplModal')).show();
}
</script>
<?php renderFoot(); ?>
