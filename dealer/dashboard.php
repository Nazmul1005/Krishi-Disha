<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['dealer']);

$dealer = $pdo->prepare("SELECT d.* FROM DEALER d WHERE d.user_id=?");
$dealer->execute([$_SESSION['user_id']]);
$d = $dealer->fetch();
$did = $d['id'] ?? 0;

$inv_count = $pdo->prepare("SELECT COUNT(*) FROM DEALER_INVENTORY WHERE dealer_id=?"); $inv_count->execute([$did]); $inv = $inv_count->fetchColumn();
$order_count = 0; // Deprecated
$revenue = 0; // Deprecated
$stock = $pdo->prepare("SELECT COALESCE(SUM(stock_remaining),0) FROM DEALER_INVENTORY WHERE dealer_id=?"); $stock->execute([$did]); $st = $stock->fetchColumn();

$recent_orders = []; // Deprecated

$page_title = 'Dealer Dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">🏪 Dealer Dashboard</div>
        </div>
        <div class="topbar-actions"><span style="font-size:13px; color:var(--text-muted);"><?= htmlspecialchars($d['business_name'] ?? '') ?></span></div>
    </div>
    <div class="page-body">
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3"><div class="stat-card blue"><div class="stat-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div><div><div class="stat-value"><?= $inv ?></div><div class="stat-label">Inventory Items</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat-value"><?= number_format($st,0) ?> kg</div><div class="stat-label">Stock Remaining</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card gold"><div class="stat-icon gold"><i class="fa-solid fa-cart-shopping"></i></div><div><div class="stat-value"><?= $order_count ?></div><div class="stat-label">Total Orders</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-coins"></i></div><div><div class="stat-value">৳<?= number_format($revenue) ?></div><div class="stat-label">Revenue</div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd">
                        <h5><i class="fa-solid fa-receipt me-2" style="color:var(--primary);"></i>Recent Orders</h5>
                        <a href="/KrishiDisha/dealer/sales.php" class="btn-kd btn-kd-outline" style="padding:6px 14px; font-size:12px;">View All</a>
                    </div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Buyer</th><th>Crop</th><th>Qty</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_orders as $o): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($o['buyer']) ?></td>
                                <td><?= htmlspecialchars($o['crop_name']) ?></td>
                                <td><?= $o['quantity_kg'] ?> kg</td>
                                <td style="color:var(--primary); font-weight:700;">৳<?= number_format($o['total_price']) ?></td>
                                <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$o['status']]??'badge-muted' ?>"><?= ucfirst($o['status']) ?></span></td>
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
                            <a href="/KrishiDisha/dealer/inventory.php" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-plus"></i> Buy from Farmer</a>
                            <a href="/KrishiDisha/dealer/sales.php" class="btn-kd btn-kd-outline w-100 justify-content-center"><i class="fa-solid fa-chart-line"></i> Manage Sales</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
