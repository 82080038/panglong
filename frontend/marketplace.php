<?php
require_once 'config.php';

$d = db();

$integrations = [];
try {
    $integrations = $d->query("SELECT * FROM marketplace_integrations ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    $integrations = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === '') {
        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $d->prepare("INSERT INTO marketplace_integrations (platform, shop_id, shop_name, access_token, status, created_at, updated_at) VALUES (?,?,?,?,'connected',?,?)");
            $stmt->execute([$_POST['platform'], $_POST['shop_id'], $_POST['shop_name'], $_POST['access_token'] ?? null, $now, $now]);
            header('Location: marketplace.php?msg=connected');
        } catch (Exception $e) {
            header('Location: marketplace.php?msg=error');
        }
        exit;
    }
}

renderHead('Marketplace Integration');
renderNav('marketplace');
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1><i class="bi bi-shop"></i> Marketplace Integration</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#connectModal"><i class="bi bi-plus"></i> Connect Shop</button>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Sinkronkan stok dan produk ke marketplace: Tokopedia, Shopee, Bukalapak, Lazada, Blibli.
    </div>

    <div class="row">
    <?php if (!empty($integrations)): foreach ($integrations as $int): ?>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-<?= $int['platform']==='tokopedia'?'shop':'shop-window' ?>"></i> <?= ucfirst($int['platform']) ?></h5>
                    <span class="badge bg-<?= $int['status']==='connected'?'success':($int['status']==='error'?'danger':'secondary') ?>"><?= $int['status'] === 'connected' ? 'Terhubung' : ($int['status'] === 'error' ? 'Error' : 'Pending') ?></span>
                </div>
                <div class="card-body">
                    <p><strong><?= htmlspecialchars($int['shop_name']) ?></strong><br><small class="text-muted">Shop ID: <?= htmlspecialchars($int['shop_id']) ?></small></p>
                    <p><small>Last synced: <?= htmlspecialchars($int['last_synced_at'] ?? 'Never') ?></small></p>
                    <p>Mapped products: <?= count($int['mappings'] ?? []) ?></p>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="syncStock(<?= $int['id'] ?>)"><i class="bi bi-arrow-repeat"></i> Sync Stock</button>
                        <button class="btn btn-sm btn-outline-success" onclick="syncProducts(<?= $int['id'] ?>)"><i class="bi bi-box-arrow-down"></i> Sync Products</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="disconnect(<?= $int['id'] ?>)"><i class="bi bi-x-circle"></i> Disconnect</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; else: ?>
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">
            <i class="bi bi-shop" style="font-size:3rem"></i>
            <h5 class="mt-3">Belum ada marketplace terhubung</h5>
            <p>Connect your marketplace shops to start syncing products and stock.</p>
        </div></div></div>
    <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="connectModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="marketplace.php">
        <div class="modal-header"><h5 class="modal-title">Connect Marketplace</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Platform</label><select class="form-select" name="platform" required>
                <option value="tokopedia">Tokopedia</option>
                <option value="shopee">Shopee</option>
                <option value="bukalapak">Bukalapak</option>
                <option value="lazada">Lazada</option>
                <option value="blibli">Blibli</option>
            </select></div>
            <div class="mb-3"><label class="form-label">Shop ID</label><input type="text" class="form-control" name="shop_id" required></div>
            <div class="mb-3"><label class="form-label">Shop Name</label><input type="text" class="form-control" name="shop_name" required></div>
            <div class="mb-3"><label class="form-label">Access Token (optional)</label><input type="text" class="form-control" name="access_token" placeholder="API token from marketplace"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Hubungkan</button></div>
    </form>
</div></div></div>

<?php
$msg = $_GET['msg'] ?? '';
if ($msg): ?>
<div class="alert alert-<?= $msg==='error'?'danger':'success' ?> alert-dismissible fade show"><?= $msg==='connected'?'Toko terhubung':'Error' ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<script>
function syncStock(id){fetch(`${API_URL}?endpoint=marketplace&id=${id}&action=sync-stock`,{method:'POST'}).then(r=>r.json()).then(d=>alert(d.message||'Synced')).catch(()=>alert('Error'))}
function syncProducts(id){fetch(`${API_URL}?endpoint=marketplace&id=${id}&action=sync-products`,{method:'POST'}).then(r=>r.json()).then(d=>alert(d.message||'Synced')).catch(()=>alert('Error'))}
function disconnect(id){if(!confirm('Disconnect?'))return;fetch(`${API_URL}?endpoint=marketplace&id=${id}&action=disconnect`,{method:'DELETE'}).then(()=>location.reload()).catch(()=>alert('Error'))}
</script>
<?php renderFoot(); ?>
