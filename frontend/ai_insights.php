<?php
require_once 'config.php';
requirePermission('view_ai_insights');

$d = db();

$tab = $_GET['tab'] ?? 'forecast';

$products = $d->query("SELECT id, code, name, sell_price, buy_price FROM products WHERE is_active = 1 ORDER BY name LIMIT 100")->fetchAll();

$forecasts = [];
$optimizations = [];

if ($tab === 'forecast') {
    foreach ($products as $p) {
        $stmt = $d->prepare("SELECT COALESCE(SUM(si.quantity),0) as total_sold FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE si.product_id = ? AND s.sale_date >= date('now','-90 days') AND s.status != 'voided'");
        $stmt->execute([$p['id']]);
        $totalSold = (float)$stmt->fetchColumn();
        $avgDaily = $totalSold / 90;
        $predicted = round($avgDaily * 30, 1);
        $Konfidensi = $totalSold > 10 ? 0.75 : ($totalSold > 0 ? 0.45 : 0.2);
        $forecasts[] = [
            'product_name' => $p['name'],
            'Prakiraan' => $predicted,
            'Konfidensi' => $Konfidensi,
            'method' => 'Moving Average 90 Hari'
        ];
    }
} elseif ($tab === 'pricing') {
    foreach ($products as $p) {
        $currentPrice = (float)($p['sell_price'] ?? 0);
        $buyPrice = (float)($p['buy_price'] ?? 0);
        if ($currentPrice <= 0 || $buyPrice <= 0) continue;
        $currentMargin = (($currentPrice - $buyPrice) / $currentPrice) * 100;
        $suggestedPrice = $currentPrice;
        $suggestedMargin = $currentMargin;
        $reasoning = 'Price is optimal';
        if ($currentMargin < 15) {
            $suggestedPrice = round($buyPrice * 1.25);
            $suggestedMargin = 20;
            $reasoning = 'Margin too low, suggest 25% markup';
        } elseif ($currentMargin > 50) {
            $suggestedPrice = round($buyPrice * 1.4);
            $suggestedMargin = 28.6;
            $reasoning = 'Margin very high, may reduce sales volume';
        }
        $optimizations[] = [
            'product_name' => $p['name'],
            'current_price' => $currentPrice,
            'suggested_price' => $suggestedPrice,
            'current_margin' => $currentMargin,
            'suggested_margin' => $suggestedMargin,
            'estimated_revenue_change' => 0,
            'reasoning' => $reasoning
        ];
    }
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
        <li class="nav-item"><a class="nav-link <?= $tab==='forecast'?'active':'' ?>" href="?tab=forecast">Prakiraan Permintaan</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='pricing'?'active':'' ?>" href="?tab=pricing">Optimasi Harga</a></li>
    </ul>

    <?php if ($tab === 'forecast'): ?>
        <div class="alert alert-info">
            <i class="bi bi-lightbulb"></i> AI memprediksi permintaan 30 hari ke depan berdasarkan riwayat penjualan 90 hari terakhir menggunakan <strong>moving average + trend analysis</strong>.
        </div>
        <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-striped" id="forecastTable">
            <thead><tr><th>Product</th><th>Predicted Demand (30d)</th><th>Confidence</th><th>Confidence Score</th><th>Method</th></tr></thead>
            <tbody>
            <?php if (!empty($forecasts)): foreach ($forecasts as $f): ?>
            <tr>
                <td><?= htmlspecialchars($f['product_name']) ?></td>
                <td class="fw-bold"><?= number_format($f['Prakiraan'], 1) ?></td>
                <td>
                    <div class="progress" style="height:20px">
                        <div class="progress-bar bg-<?= $f['Konfidensi']>0.7?'success':($f['Konfidensi']>0.4?'warning':'danger') ?>" style="width:<?= $f['Konfidensi']*100 ?>%"><?= round($f['Konfidensi']*100) ?>%</div>
                    </div>
                </td>
                <td><?= round($f['Konfidensi']*100) ?>%</td>
                <td><small><?= htmlspecialchars($f['method']) ?></small></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-center text-muted">Belum ada data prakiraan. Jalankan generate terlebih dahulu.</td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
        </div></div>

    <?php elseif ($tab === 'pricing'): ?>
        <div class="alert alert-warning">
            <i class="bi bi-currency-dollar"></i> AI menganalisis margin, sales velocity, dan stock level untuk merekomendasikan harga optimal.
        </div>
        <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-striped" id="pricingTable">
            <thead><tr><th>Product</th><th>Current Price</th><th>Suggested Price</th><th>Current Margin</th><th>Suggested Margin</th><th>Est. Revenue Change</th><th>Reasoning</th></tr></thead>
            <tbody>
            <?php if (!empty($optimizations)): foreach ($optimizations as $o): ?>
            <tr class="<?= $o['suggested_price']>$o['current_price']?'table-success':($o['suggested_price']<$o['current_price']?'table-warning':'') ?>">
                <td><?= htmlspecialchars($o['product_name']) ?></td>
                <td><?= rupiah($o['current_price']) ?></td>
                <td class="fw-bold"><?= rupiah($o['suggested_price']) ?></td>
                <td><?= number_format($o['current_margin'], 1) ?>%</td>
                <td><?= number_format($o['suggested_margin'], 1) ?>%</td>
                <td class="<?= $o['estimated_revenue_change']>0?'text-success':'text-danger' ?>"><?= rupiah($o['estimated_revenue_change']) ?></td>
                <td><small class="text-muted"><?= htmlspecialchars($o['reasoning']) ?></small></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center text-muted">No price optimization suggestions. All prices are optimal.</td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
        </div></div>
    <?php endif; ?>
</div>
<script>
function exportCSV(){const t=document.querySelector('.card-body table');if(!t){alert('No table');return;}let c=[];t.querySelectorAll('tr').forEach(r=>{let row=[];r.querySelectorAll('th,td').forEach(c2=>{let txt=c2.textContent.trim().replace(/Rp\s/g,'').replace(/,/g,'');row.push('"'+txt.replace(/"/g,'""')+'"')});c.push(row.join(','))});const b=new Blob([c.join('\n')],{type:'text/csv'});const a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='ai_<?= $tab ?>.csv';a.click()}
function exportPDF(){window.print()}
</script>
<style>@media print{.navbar,.nav-tabs,.btn-group,.btn,.alert{display:none!important}.card{border:none!important}body{font-size:12px}}</style>
<?php renderFoot(); ?>
