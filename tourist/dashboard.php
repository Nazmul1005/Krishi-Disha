<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['tourist']);

$tourist = $pdo->prepare("SELECT t.* FROM TOURIST t WHERE t.user_id=?");
$tourist->execute([$_SESSION['user_id']]);
$t = $tourist->fetch();
$tid = $t['id'] ?? 0;

$bookings  = $pdo->prepare("SELECT COUNT(*) FROM TOUR_BOOKING WHERE tourist_id=?"); $bookings->execute([$tid]); $bc = $bookings->fetchColumn();
$forders   = $pdo->prepare("SELECT COUNT(*) FROM FOOD_ORDER WHERE tourist_id=?");   $forders->execute([$tid]);  $fc = $forders->fetchColumn();
$spend     = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM TOUR_BOOKING WHERE tourist_id=?"); $spend->execute([$tid]); $sp = $spend->fetchColumn();

$recent_bookings = $pdo->prepare("SELECT tb.*, ft.title, ft.location FROM TOUR_BOOKING tb JOIN FARM_TOUR ft ON tb.tour_id=ft.id WHERE tb.tourist_id=? ORDER BY tb.created_at DESC LIMIT 5");
$recent_bookings->execute([$tid]);
$recent_bookings = $recent_bookings->fetchAll();

$page_title = 'Tourist Dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">🧳 Tourist Dashboard</div>
        </div>
    </div>
    <div class="page-body">
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="stat-card blue"><div class="stat-icon blue"><i class="fa-solid fa-map"></i></div><div><div class="stat-value"><?= $bc ?></div><div class="stat-label">Farm Bookings</div></div></div></div>
            <div class="col-md-4"><div class="stat-card gold"><div class="stat-icon gold"><i class="fa-solid fa-utensils"></i></div><div><div class="stat-value"><?= $fc ?></div><div class="stat-label">Food Orders</div></div></div></div>
            <div class="col-md-4"><div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-coins"></i></div><div><div class="stat-value">৳<?= number_format($sp) ?></div><div class="stat-label">Total Spent</div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-map-location me-2" style="color:var(--primary);"></i>My Tour Bookings</h5>
                    <a href="/KrishiDisha/tourist/tours.php" class="btn-kd btn-kd-outline" style="padding:6px 14px; font-size:12px;">Book New</a></div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Farm</th><th>Location</th><th>Dates</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_bookings as $b): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($b['title']) ?></td>
                                <td style="font-size:13px; color:var(--text-muted);"><?= htmlspecialchars($b['location']) ?></td>
                                <td style="font-size:12px;"><?= $b['start_date'] ?> → <?= $b['end_date'] ?></td>
                                <td style="color:var(--primary); font-weight:700;">৳<?= number_format($b['total_price']) ?></td>
                                <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$b['status']]??'badge-muted' ?>"><?= ucfirst($b['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_bookings)): ?><tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">No bookings yet. <a href="/KrishiDisha/tourist/tours.php">Browse farm tours</a></td></tr><?php endif; ?>
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
                            <a href="/KrishiDisha/tourist/tours.php" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-map"></i> Browse Farm Tours</a>
                            <a href="/KrishiDisha/tourist/food_orders.php" class="btn-kd btn-kd-gold w-100 justify-content-center" style="color:#fff;"><i class="fa-solid fa-utensils"></i> Order Authentic Food</a>
                            <a href="/KrishiDisha/modules/tourism.php" class="btn-kd btn-kd-outline w-100 justify-content-center"><i class="fa-solid fa-umbrella-beach"></i> Agri-Tourism Guide</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
