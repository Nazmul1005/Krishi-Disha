<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['dealer']);
$dealer = $pdo->prepare("SELECT * FROM DEALER WHERE user_id=?"); $dealer->execute([$_SESSION['user_id']]); $d = $dealer->fetch(); $did = $d['id'] ?? 0;

require_once __DIR__.'/../includes/transactions.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
    try { changeState($pdo,(int)$_SESSION['user_id'],'order',(int)($_POST['id'] ?? 0),(string)($_POST['status'] ?? ''));flash('success','Status updated. Payment remains separate.'); }
    catch(Throwable $e) { flash('error',$e instanceof InvalidArgumentException ? $e->getMessage() : 'Status could not be updated.'); }
    header('Location: sales.php');exit;
}
$orders = $pdo->prepare("SELECT o.*, u.name as buyer, c.name as crop_name
    FROM `ORDER` o
    JOIN USER u ON o.user_id=u.id
    JOIN DEALER_INVENTORY di ON di.id = o.dealer_inventory_id AND di.dealer_id = ?
    JOIN PRODUCT p ON o.product_id=p.id
    JOIN CROP c ON p.crop_id=c.id
    ORDER BY o.created_at DESC");
$orders=pagedRows($pdo,$orders->queryString,[$did],'sales','Sales orders',['buyer', 'crop_name'],true);
$page_title = 'Sales';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><h1 class="topbar-title"><i class="fa-solid fa-chart-line me-2" style="color:var(--primary);"></i>Sales Management</h1></div></div>
    <div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><?= renderFlash() ?>
        <div class="card-kd">
            <div class="card-header-kd"><h5>All Sales Orders (<?= count($orders) ?>)</h5></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>#</th><th>Buyer</th><th>Crop</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td style="color:var(--text-muted);font-size:12px;"><?= e($o['id']) ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($o['buyer']) ?></td>
                        <td><?= htmlspecialchars($o['crop_name']) ?></td>
                        <td><?= e($o['quantity_kg']) ?> kg</td>
                        <td style="color:var(--primary);font-weight:700;">৳<?= number_format($o['total_price']) ?></td>
                        <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$o['status']]??'badge-muted' ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y',strtotime($o['created_at'])) ?></td>
                        <td>
                            <?php if ($o['status']==='pending'): ?>
                            <form method="POST" style="display:inline;"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="id" value="<?= e($o['id']) ?>"><input type="hidden" name="status" value="confirmed"><button type="submit" class="btn-kd btn-kd-primary" style="padding:4px 8px;font-size:11px;">Confirm</button></form>
                            <?php elseif ($o['status']==='confirmed'): ?>
                            <form method="POST" style="display:inline;"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="id" value="<?= e($o['id']) ?>"><input type="hidden" name="status" value="delivered"><button type="submit" class="btn-kd btn-kd-gold" style="padding:4px 8px;font-size:11px;color:#fff;">Deliver</button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?><tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">No sales yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
