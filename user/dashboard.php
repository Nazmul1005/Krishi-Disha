<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['general']);

$uid = $_SESSION['user_id'];
$orders = $pdo->prepare("SELECT COUNT(*) FROM `ORDER` WHERE user_id=?"); $orders->execute([$uid]); $oc = $orders->fetchColumn();
$spent  = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM `ORDER` WHERE user_id=? AND status='delivered'"); $spent->execute([$uid]); $sp = $spent->fetchColumn();

$my_orders = $pdo->prepare("SELECT o.*, c.name as crop_name, di.markup_price FROM `ORDER` o JOIN DEALER_INVENTORY di ON o.inventory_id=di.id JOIN PRODUCT p ON di.product_id=p.id JOIN CROP c ON p.crop_id=c.id WHERE o.user_id=? ORDER BY o.created_at DESC LIMIT 6");
$my_orders->execute([$uid]);
$my_orders = $my_orders->fetchAll();

$page_title = 'My Dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">👤 My Dashboard</div>
        </div>
    </div>
    <div class="page-body">
        <div class="row g-4 mb-4">
            <div class="col-md-6"><div class="stat-card blue"><div class="stat-icon blue"><i class="fa-solid fa-cart-shopping"></i></div><div><div class="stat-value"><?= $oc ?></div><div class="stat-label">My Orders</div></div></div></div>
            <div class="col-md-6"><div class="stat-card gold"><div class="stat-icon gold"><i class="fa-solid fa-coins"></i></div><div><div class="stat-value">৳<?= number_format($sp) ?></div><div class="stat-label">Total Spent</div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd"><h5>My Orders</h5><a href="/KrishiDisha/user/marketplace.php" class="btn-kd btn-kd-primary" style="padding:6px 14px; font-size:12px;"><i class="fa-solid fa-store"></i> Shop</a></div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Crop</th><th>Qty</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($my_orders as $o): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($o['crop_name']) ?></td>
                                <td><?= $o['quantity_kg'] ?> kg</td>
                                <td style="color:var(--primary); font-weight:700;">৳<?= number_format($o['total_price']) ?></td>
                                <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$o['status']]??'badge-muted' ?>"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($my_orders)): ?><tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">No orders yet. <a href="/KrishiDisha/user/marketplace.php">Start shopping</a></td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5>Explore</h5></div>
                    <div class="card-body-kd">
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <a href="/KrishiDisha/user/marketplace.php" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-store"></i> Marketplace</a>
                            <a href="/KrishiDisha/modules/book_consultation.php" class="btn-kd w-100 justify-content-center" style="background:linear-gradient(135deg,#4c1d95,#7c3aed);color:#fff;"><i class="fa-solid fa-user-doctor"></i> Book Consultation</a>
                            <a href="/KrishiDisha/user/nutrition.php" class="btn-kd btn-kd-gold w-100 justify-content-center" style="color:#fff;"><i class="fa-solid fa-apple-whole"></i> Nutrition Guide</a>
                            <a href="/KrishiDisha/modules/encyclopedia.php" class="btn-kd btn-kd-outline w-100 justify-content-center"><i class="fa-solid fa-book-atlas"></i> Crop Encyclopedia</a>
                            <a href="/KrishiDisha/modules/recommend.php" class="btn-kd btn-kd-outline w-100 justify-content-center"><i class="fa-solid fa-seedling"></i> Crop Recommender</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
