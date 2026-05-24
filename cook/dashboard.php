<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['cook']);

$cook = $pdo->prepare("SELECT c.* FROM COOK c WHERE c.user_id=?");
$cook->execute([$_SESSION['user_id']]);
$ck = $cook->fetch();
$cid = $ck['id'] ?? 0;

$recipes   = $pdo->prepare("SELECT COUNT(*) FROM RECIPE WHERE cook_id=?"); $recipes->execute([$cid]); $rc = $recipes->fetchColumn();
$orders    = $pdo->prepare("SELECT COUNT(*) FROM FOOD_ORDER WHERE cook_id=?"); $orders->execute([$cid]); $oc = $orders->fetchColumn();
$pending_o = $pdo->prepare("SELECT COUNT(*) FROM FOOD_ORDER WHERE cook_id=? AND status='pending'"); $pending_o->execute([$cid]); $pend = $pending_o->fetchColumn();
$earned    = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM FOOD_ORDER WHERE cook_id=? AND status='delivered'"); $earned->execute([$cid]); $earn = $earned->fetchColumn();

$recent_orders = $pdo->prepare("SELECT fo.*, r.name as recipe_name, u.name as tourist_name FROM FOOD_ORDER fo JOIN RECIPE r ON fo.recipe_id=r.id JOIN TOURIST t ON fo.tourist_id=t.id JOIN USER u ON t.user_id=u.id WHERE fo.cook_id=? ORDER BY fo.created_at DESC LIMIT 6");
$recent_orders->execute([$cid]);
$recent_orders = $recent_orders->fetchAll();

$page_title = 'Cook Dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">👩‍🍳 Cook Dashboard</div>
        </div>
        <div class="topbar-actions">
            <span class="badge-kd <?= ($ck['availability']??'') === 'available' ? 'badge-success' : 'badge-warning' ?>">
                <?= ucfirst($ck['availability'] ?? 'N/A') ?>
            </span>
        </div>
    </div>
    <div class="page-body">
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3"><div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-book-open"></i></div><div><div class="stat-value"><?= $rc ?></div><div class="stat-label">Recipes Created</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card blue"><div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div><div><div class="stat-value"><?= $oc ?></div><div class="stat-label">Total Orders</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card gold"><div class="stat-icon gold"><i class="fa-solid fa-clock"></i></div><div><div class="stat-value"><?= $pend ?></div><div class="stat-label">Pending Orders</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-coins"></i></div><div><div class="stat-value">৳<?= number_format($earn) ?></div><div class="stat-label">Earned</div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-receipt me-2" style="color:var(--primary);"></i>Recent Food Orders</h5>
                    <a href="/KrishiDisha/cook/orders.php" class="btn-kd btn-kd-outline" style="padding:6px 14px; font-size:12px;">All Orders</a></div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Tourist</th><th>Recipe</th><th>Qty</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_orders as $o): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($o['tourist_name']) ?></td>
                                <td><?= htmlspecialchars($o['recipe_name']) ?></td>
                                <td><?= $o['quantity'] ?></td>
                                <td style="color:var(--primary); font-weight:700;">৳<?= number_format($o['total_price']) ?></td>
                                <td><?php $sc=['pending'=>'badge-warning','preparing'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$o['status']]??'badge-muted' ?>"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_orders)): ?><tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">No orders yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-bolt me-2" style="color:var(--gold);"></i>Quick Actions</h5></div>
                    <div class="card-body-kd">
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <a href="/KrishiDisha/cook/recipes.php" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-plus"></i> Add Recipe</a>
                            <a href="/KrishiDisha/cook/orders.php" class="btn-kd btn-kd-outline w-100 justify-content-center"><i class="fa-solid fa-receipt"></i> Manage Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
