<?php
require_once 'config.php';

$tab = $_GET['tab'] ?? 'forecast';

$productsResp = apiCall('/products?per_page=100');
$products = $productsResp['body']['data'] ?? [];

if ($tab === 'forecast') {
    $resp = apiCall('/ai/demand-forecast/batch');
    $forecasts = $resp['body']['data'] ?? [];
} elseif ($tab === 'pricing') {
    $resp = apiCall('/ai/price-optimization/batch');
    $optimizations = $resp['body']['data'] ?? [];
}

renderHead('AI Insights');
renderNav('ai_insights');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1><i class="bi bi-cpu"></i> AI Insights</h1>
        <div class="btn-group">
            <button class="btn btn-outline-success btn-sm" onclick="exportCSV()"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
            <button class="btn btn-outline-danger btn-sm" onclick="exportPDF()"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab==='forecast'?'active':'' ?>" href="?tab=forecast">Demand Forecast</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='pricing'?'active':'' ?>" href="?tab=pricing">Price Optimization</a></li>
    </ul>

    <?php if ($tab === 'forecast'): ?>
        <div class="alert alert-info">
            <i class="bi bi-lightbulb"></i> AI memprediksi permintaan 30 hari ke depan berdasarkan riwayat penjualan 90 hari terakhir menggunakan <strong>moving average + trend analysis</strong>.
        </div>
        <div class="card"><div class="card-body">
        <table class="table table-striped" id="forecastTable">
            <thead><tr><th>Product</th><th>Predicted Demand (30d)</th><th>Confidence</th><th>Confidence Score</th><th>Method</th></tr></thead>
            <tbody>
            <?php if (!empty($forecasts)): foreach ($forecasts as $f): ?>
            <tr>
                <td><?= htmlspecialchars($f['product_name']) ?></td>
                <td class="fw-bold"><?= number_format($f['predicted_demand'], 1) ?></td>
                <td>
                    <div class="progress" style="height:20px">
                        <div class="progress-bar bg-<?= $f['confidence']>0.7?'success':($f['confidence']>0.4?'warning':'danger') ?>" style="width:<?= $f['confidence']*100 ?>%"><?= round($f['confidence']*100) ?>%</div>
                    </div>
                </td>
                <td><?= round($f['confidence']*100) ?>%</td>
                <td><small><?= htmlspecialchars($f['method']) ?></small></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-center text-muted">No forecast data. Generate forecasts first.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div></div>

    <?php elseif ($tab === 'pricing'): ?>
        <div class="alert alert-warning">
            <i class="bi bi-currency-dollar"></i> AI menganalisis margin, sales velocity, dan stock level untuk merekomendasikan harga optimal.
        </div>
        <div class="card"><div class="card-body">
        <table class="table table-striped" id="pricingTable">
            <thead><tr><th>Product</th><th>Current Price</th><th>Suggested Price</th><th>Current Margin</th><th>Suggested Margin</th><th>Est. Revenue Change</th><th>Reasoning</th></tr></thead>
            <tbody>
            <?php if (!empty($optimizations)): foreach ($optimizations as $o): ?>
            <tr class="<?= $o['suggested_price']>$o['current_price']?'table-success':($o['suggested_price']<$o['current_price']?'table-warning':'') ?>">
                <td><?= htmlspecialchars($o['product_name']) ?></td>
                <td>Rp <?= number_format($o['current_price'], 0) ?></td>
                <td class="fw-bold">Rp <?= number_format($o['suggested_price'], 0) ?></td>
                <td><?= number_format($o['current_margin'], 1) ?>%</td>
                <td><?= number_format($o['suggested_margin'], 1) ?>%</td>
                <td class="<?= $o['estimated_revenue_change']>0?'text-success':'text-danger' ?>">Rp <?= number_format($o['estimated_revenue_change'], 0) ?></td>
                <td><small class="text-muted"><?= htmlspecialchars($o['reasoning']) ?></small></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center text-muted">No price optimization suggestions. All prices are optimal.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div></div>
    <?php endif; ?>
</div>
<script>
function exportCSV(){const t=document.querySelector('.card-body table');if(!t){alert('No table');return;}let c=[];t.querySelectorAll('tr').forEach(r=>{let row=[];r.querySelectorAll('th,td').forEach(c2=>{let txt=c2.textContent.trim().replace(/Rp\s/g,'').replace(/,/g,'');row.push('"'+txt.replace(/"/g,'""')+'"')});c.push(row.join(','))});const b=new Blob([c.join('\n')],{type:'text/csv'});const a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='ai_<?= $tab ?>.csv';a.click()}
function exportPDF(){window.print()}
</script>
<style>@media print{.navbar,.nav-tabs,.btn-group,.btn,.alert{display:none!important}.card{border:none!important}body{font-size:12px}}</style>
<?php renderFoot(); ?>
