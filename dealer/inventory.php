<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['dealer']);
$dealer = $pdo->prepare("SELECT * FROM DEALER WHERE user_id=?"); $dealer->execute([$_SESSION['user_id']]); $d = $dealer->fetch(); $did = $d['id'] ?? 0;
$msg = $err = '';

require_once __DIR__.'/../includes/transactions.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
    try { $id=procure($pdo,(int)$_SESSION['user_id'],requestKey(),(int)($_POST['product_id'] ?? 0),(string)($_POST['quantity_purchased'] ?? ''),(string)($_POST['markup_price'] ?? ''));flash('success','Procurement #'.$id.' recorded. Farmer stock deducted; cash collection is pending.'); }
    catch(Throwable $e) { flash('error',$e instanceof InvalidArgumentException ? $e->getMessage() : 'Purchase failed. Please try again.'); }
    header('Location: inventory.php');exit;
}
$available_products = $pdo->query("SELECT p.*, c.name as crop_name, u.name as farmer_name FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id JOIN FARMER f ON p.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE p.status='available' AND u.status='approved' ORDER BY c.name")->fetchAll();
$my_inventory = $pdo->prepare("SELECT di.*, c.name as crop_name FROM DEALER_INVENTORY di JOIN PRODUCT p ON di.product_id=p.id JOIN CROP c ON p.crop_id=c.id WHERE di.dealer_id=? ORDER BY di.created_at DESC"); $my_inventory->execute([$did]); $my_inventory = $my_inventory->fetchAll();
$page_title = 'Inventory';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><button id="sidebarToggle" aria-label="Open navigation" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><i class="fa-solid fa-boxes-stacked me-2" style="color:var(--primary);"></i>Inventory</div></div></div>
    <div class="page-body"><?= renderFlash() ?>
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-cart-plus me-2" style="color:var(--primary);"></i>Buy Farmer Produce</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                            <div class="form-group"><label>Select Product <span style="color:red">*</span></label>
                            <select name="product_id" class="form-control" required><option value="">Choose product</option>
                            <?php foreach ($available_products as $p): ?><option value="<?= e($p['id']) ?>"><?= htmlspecialchars($p['crop_name']) ?> — <?= e($p['quantity_kg']) ?>kg @ ৳<?= e($p['price_per_kg']) ?>/kg (<?= htmlspecialchars($p['farmer_name']) ?>)</option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Quantity to Purchase (kg) <span style="color:red">*</span></label><input type="number" name="quantity_purchased" step="0.5" min="0.5" class="form-control" required></div>
                            <div class="form-group"><label>Your Selling Price/kg (৳) <span style="color:red">*</span></label><input type="number" name="markup_price" step="0.01" min="0.01" class="form-control" required></div>
                            <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-shopping-basket"></i> Purchase</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card-kd">
                    <div class="card-header-kd"><h5>My Inventory (<?= count($my_inventory) ?>)</h5></div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Crop</th><th>Purchased</th><th>Buy Price</th><th>Sell Price</th><th>In Stock</th></tr></thead>
                            <tbody>
                            <?php foreach ($my_inventory as $i): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($i['crop_name']) ?></td>
                                <td><?= e($i['quantity_purchased']) ?> kg</td>
                                <td>৳<?= e($i['purchase_price']) ?></td>
                                <td style="color:var(--primary);font-weight:700;">৳<?= e($i['markup_price']) ?></td>
                                <td><span class="badge-kd <?= $i['stock_remaining']>0?'badge-success':'badge-muted' ?>"><?= e($i['stock_remaining']) ?> kg</span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($my_inventory)): ?><tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">No inventory yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
