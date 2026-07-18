<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['dealer']);
$dealer = $pdo->prepare("SELECT * FROM DEALER WHERE user_id=?"); $dealer->execute([$_SESSION['user_id']]); $d = $dealer->fetch(); $did = $d['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    verifyCsrf();
    $valid = ['confirmed','delivered','cancelled'];
    if (in_array($_POST['status'], $valid, true)) {
        // Only allow status changes on orders tied to this dealer's inventory
        $pdo->prepare("UPDATE `ORDER` SET status=? WHERE id=? AND dealer_inventory_id IN (SELECT id FROM DEALER_INVENTORY WHERE dealer_id=?)")
            ->execute([$_POST['status'], (int)$_POST['id'], $did]);
    }
    header('Location: sales.php'); exit;
}

$orders = $pdo->prepare("SELECT o.*, u.name as buyer, c.name as crop_name
    FROM `ORDER` o
    JOIN USER u ON o.user_id=u.id
    JOIN DEALER_INVENTORY di ON di.id = o.dealer_inventory_id AND di.dealer_id = ?
    JOIN PRODUCT p ON o.product_id=p.id
    JOIN CROP c ON p.crop_id=c.id
    ORDER BY o.created_at DESC");
$orders->execute([$did]);
$orders = $orders->fetchAll();
$page_title = 'Sales';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><i class="fa-solid fa-chart-line me-2" style="color:var(--primary);"></i>Sales Management</div></div></div>
    <div class="page-body">
        <div class="card-kd">
            <div class="card-header-kd"><h5>All Sales Orders (<?= count($orders) ?>)</h5></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>#</th><th>Buyer</th><th>Crop</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td style="color:var(--text-muted);font-size:12px;"><?= $o['id'] ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($o['buyer']) ?></td>
                        <td><?= htmlspecialchars($o['crop_name']) ?></td>
                        <td><?= $o['quantity_kg'] ?> kg</td>
                        <td style="color:var(--primary);font-weight:700;">৳<?= number_format($o['total_price']) ?></td>
                        <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$o['status']]??'badge-muted' ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y',strtotime($o['created_at'])) ?></td>
                        <td>
                            <?php if ($o['status']==='pending'): ?>
                            <form method="POST" style="display:inline;"><?= csrfField() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="status" value="confirmed"><button type="submit" class="btn-kd btn-kd-primary" style="padding:4px 8px;font-size:11px;">Confirm</button></form>
                            <?php elseif ($o['status']==='confirmed'): ?>
                            <form method="POST" style="display:inline;"><?= csrfField() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="status" value="delivered"><button type="submit" class="btn-kd btn-kd-gold" style="padding:4px 8px;font-size:11px;color:#fff;">Deliver</button></form>
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
