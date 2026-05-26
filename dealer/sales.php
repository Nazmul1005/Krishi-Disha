<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['dealer']);
$dealer = $pdo->prepare("SELECT * FROM DEALER WHERE user_id=?"); $dealer->execute([$_SESSION['user_id']]); $d = $dealer->fetch(); $did = $d['id'] ?? 0;

if (isset($_GET['status']) && isset($_GET['id'])) {
    $valid = ['confirmed','delivered','cancelled'];
    if (in_array($_GET['status'], $valid)) {
        $pdo->prepare("UPDATE `ORDER` SET status=? WHERE id=? AND product_id IN (SELECT product_id FROM DEALER_INVENTORY WHERE dealer_id=?)")->execute([$_GET['status'],(int)$_GET['id'],$did]);
        if ($_GET['status'] === 'delivered') {
            $order = $pdo->prepare("SELECT * FROM `ORDER` WHERE id=?"); $order->execute([(int)$_GET['id']]); $order = $order->fetch();
            if ($order) {
                $payRes = $pdo->prepare("SELECT id FROM PAYMENT WHERE ref_type='order' AND ref_id=?"); $payRes->execute([(int)$_GET['id']]); $pay = $payRes->fetch();
                if (!$pay) {
                    $pdo->prepare("INSERT INTO PAYMENT (payer_id,ref_type,ref_id,amount,status) VALUES (?,?,?,?,'completed')")->execute([$order['user_id'],'order',$order['id'],$order['total_price']]);
                    $pid = $pdo->lastInsertId();
                    $pdo->prepare("INSERT INTO ADMIN_COMMISSION (payment_id,commission_rate,commission_amount) VALUES (?,5.00,?)")->execute([$pid, $order['total_price']*0.05]);
                }
            }
        }
    }
    header('Location: sales.php'); exit;
}

$orders = $pdo->query("SELECT o.*, u.name as buyer, c.name as crop_name FROM `ORDER` o JOIN USER u ON o.user_id=u.id JOIN PRODUCT p ON o.product_id=p.id JOIN DEALER_INVENTORY di ON di.product_id=p.id AND di.dealer_id=$did JOIN CROP c ON p.crop_id=c.id ORDER BY o.created_at DESC")->fetchAll();
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
                            <a href="?id=<?= $o['id'] ?>&status=confirmed" class="btn-kd btn-kd-primary" style="padding:4px 8px;font-size:11px;">Confirm</a>
                            <?php elseif ($o['status']==='confirmed'): ?>
                            <a href="?id=<?= $o['id'] ?>&status=delivered" class="btn-kd btn-kd-gold" style="padding:4px 8px;font-size:11px;color:#fff;">Deliver</a>
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
