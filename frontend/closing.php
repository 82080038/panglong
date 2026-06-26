<?php
require_once 'config.php';
requirePermission('view_reports');

$d = db();

$closings = $d->query("SELECT pc.*, u.full_name as closed_by_name FROM period_closings pc LEFT JOIN users u ON pc.closed_by = u.id ORDER BY pc.period_year DESC, pc.period_month DESC")->fetchAll();

$currentYear = (int)date('Y');
$currentMonth = (int)date('n');

// Check if current period is locked
$stmt = $d->prepare("SELECT status FROM period_closings WHERE period_year = ? AND period_month = ?");
$stmt->execute([$currentYear, $currentMonth]);
$currentStatus = $stmt->fetchColumn() ?: 'open';

renderHead('Tutup Buku Periode');
renderNav('closing');
?>

<div class="container-fluid mt-3">
    <h4><i class="bi bi-lock"></i> Tutup Buku Periode</h4>
    <p class="text-muted small">Kunci transaksi per periode bulanan. Setelah ditutup, transaksi dengan tanggal pada periode tersebut tidak dapat diubah.</p>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card <?= $currentStatus === 'closed' ? 'border-danger' : 'border-success' ?>">
                <div class="card-body text-center">
                    <h5 class="card-title">Periode Aktif</h5>
                    <p class="display-6"><?= $currentYear ?>-<?= str_pad($currentMonth, 2, '0', STR_PAD_LEFT) ?></p>
                    <span class="badge bg-<?= $currentStatus === 'closed' ? 'danger' : 'success' ?> fs-6"><?= strtoupper($currentStatus) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><strong>Tutup Periode</strong></div>
                <div class="card-body">
                    <form id="closeForm" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control" value="<?= $currentYear ?>" min="2020" max="2099" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m === $currentMonth ? 'selected' : '' ?>><?= DateTime::createFromFormat('!m', $m)->format('F') ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-lock-fill"></i> Tutup Periode</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Riwayat Tutup Buku</strong></div>
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Period</th><th>Status</th><th>Ditutup Oleh</th><th>Waktu Tutup</th><th>Catatan</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($closings as $c): ?>
                    <tr>
                        <td class="fw-bold"><?= $c['period_year'] ?>-<?= str_pad($c['period_month'], 2, '0', STR_PAD_LEFT) ?></td>
                        <td><span class="badge bg-<?= $c['status'] === 'closed' ? 'danger' : 'success' ?>"><?= $c['status'] === 'closed' ? 'DITUTUP' : 'TERBUKA' ?></span></td>
                        <td><?= htmlspecialchars($c['closed_by_name'] ?? '-') ?></td>
                        <td><?= $c['closed_at'] ? tglJamIndo($c['closed_at']) : '-' ?></td>
                        <td><?= htmlspecialchars($c['notes'] ?? '-') ?></td>
                        <td>
                            <?php if ($c['status'] === 'closed'): ?>
                            <button class="btn btn-sm btn-outline-warning" onclick="reopen(<?= $c['id'] ?>)"><i class="bi bi-unlock"></i> Reopen</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($closings)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Belum ada riwayat tutup buku.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<?php renderFoot(); ?>
<script>
$('#closeForm').on('submit', function(e) {
    e.preventDefault();
    if (!confirm('Tutup periode ini? Transaksi pada periode ini akan dikunci.')) return;
    const data = {};
    $(this).serializeArray().forEach(f => data[f.name] = f.value);
    data.year = parseInt(data.year);
    data.month = parseInt(data.month);
    $.ajax({
        url: API_URL + '?endpoint=period-closings',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function() {
            alert('Periode ditutup!');
            location.reload();
        },
        error: function(xhr) {
            const err = JSON.parse(xhr.responseText);
            alert('Kesalahan: ' + (err.message || 'Failed'));
        }
    });
});

function reopen(id) {
    if (!confirm('Buka kembali periode ini? Transaksi dapat diedit kembali.')) return;
    $.ajax({
        url: API_URL + '?endpoint=period-closings',
        type: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id: id }),
        success: function() {
            alert('Periode dibuka kembali!');
            location.reload();
        },
        error: function(xhr) {
            const err = JSON.parse(xhr.responseText);
            alert('Kesalahan: ' + (err.message || 'Failed'));
        }
    });
}
</script>
