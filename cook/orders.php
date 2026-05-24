<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['cook']);
$cook = $pdo->prepare("SELECT * FROM COOK WHERE user_id=?"); $cook->execute([$_SESSION['user_id']]); $ck = $cook->fetch(); $cid = $ck['id'] ?? 0;

if (isset($_GET['status']) && isset($_GET['id'])) {
    $valid = ['preparing','delivered','cancelled'];
    if (in_array($_GET['status'], $valid)) $pdo->prepare("UPDATE FOOD_ORDER SET status=? WHERE id=? AND cook_id=?")->execute([$_GET['status'],(int)$_GET['id'],$cid]);
    header('Location: orders.php'); exit;
}

$orders = $pdo->prepare("SELECT fo.*, r.name as recipe_name, u.name as tourist_name FROM FOOD_ORDER fo JOIN RECIPE r ON fo.recipe_id=r.id JOIN TOURIST t ON fo.tourist_id=t.id JOIN USER u ON t.user_id=u.id WHERE fo.cook_id=? ORDER BY fo.created_at DESC"); $orders->execute([$cid]); $orders = $orders->fetchAll();
$page_title = 'Food Orders';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><i class="fa-solid fa-receipt me-2" style="color:#ea580c;"></i>Food Orders</div></div></div>
    <div class="page-body">
        <div class="card-kd">
            <div class="card-header-kd"><h5>My Food Orders (<?= count($orders) ?>)</h5></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>Tourist</th><th>Recipe</th><th>Qty</th><th>Total</th><th>Delivery</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($o['tourist_name']) ?></td>
                        <td><?= htmlspecialchars($o['recipe_name']) ?></td>
                        <td><?= $o['quantity'] ?></td>
                        <td style="color:#ea580c;font-weight:700;">৳<?= number_format($o['total_price']) ?></td>
                        <td style="font-size:12px;"><?= $o['delivery_date'] ?></td>
                        <td><?php $sc=['pending'=>'badge-warning','preparing'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$o['status']]??'badge-muted' ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td>
                            <?php if ($o['status']==='pending'): ?><a href="?id=<?= $o['id'] ?>&status=preparing" class="btn-kd btn-kd-primary" style="padding:4px 8px;font-size:11px;">Start</a>
                            <?php elseif ($o['status']==='preparing'): ?><a href="?id=<?= $o['id'] ?>&status=delivered" class="btn-kd btn-kd-gold" style="padding:4px 8px;font-size:11px;color:#fff;">Deliver</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?><tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">No orders yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
