<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['farmer']);

$farmer = $pdo->prepare("SELECT f.* FROM FARMER f WHERE f.user_id=?");
$farmer->execute([$_SESSION['user_id']]);
$f = $farmer->fetch();

$fid = $f['id'] ?? 0;
$products    = $pdo->prepare("SELECT COUNT(*) FROM PRODUCT WHERE farmer_id=?"); $products->execute([$fid]); $pc = $products->fetchColumn();
$tours       = $pdo->prepare("SELECT COUNT(*) FROM FARM_TOUR WHERE farmer_id=?"); $tours->execute([$fid]); $tc = $tours->fetchColumn();
$consults    = $pdo->prepare("SELECT COUNT(*) FROM CONSULTATION WHERE farmer_id=?"); $consults->execute([$fid]); $cc = $consults->fetchColumn();
$revenue     = $pdo->prepare("SELECT COALESCE(SUM(price_per_kg * quantity_kg),0) FROM PRODUCT WHERE farmer_id=? AND status='sold'"); $revenue->execute([$fid]); $rev = $revenue->fetchColumn();

$recent_products = $pdo->prepare("SELECT p.*, c.name as crop_name FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id WHERE p.farmer_id=? ORDER BY p.created_at DESC LIMIT 5");
$recent_products->execute([$fid]);
$recent_products = $recent_products->fetchAll();

$page_title = 'Farmer Dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">🧑‍🌾 Farmer Dashboard</div>
        </div>
        <div class="topbar-actions">
            <span style="font-size:13px; color:var(--text-muted);"><?= htmlspecialchars($f['farm_name'] ?? 'My Farm') ?></span>
        </div>
    </div>
    <div class="page-body">
        <?php if ($f): ?>
        <div class="card-kd mb-4" style="background:linear-gradient(135deg,var(--primary-dark),var(--primary)); color:#fff; border:none;">
            <div class="card-body-kd">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 style="color:#fff; margin-bottom:6px;"><?= htmlspecialchars($f['farm_name'] ?? 'My Farm') ?></h4>
                        <p style="color:rgba(255,255,255,0.8); margin:0; font-size:14px;">
                            <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($f['farm_location'] ?? 'Location not set') ?> &nbsp;·&nbsp;
                            <i class="fa-solid fa-ruler-combined me-1"></i><?= $f['land_size_acres'] ?? 0 ?> acres &nbsp;·&nbsp;
                            <i class="fa-solid fa-mountain me-1"></i><?= htmlspecialchars($f['soil_type'] ?? 'N/A') ?> soil
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="/KrishiDisha/farmer/produce.php" class="btn-kd" style="background:rgba(255,255,255,0.2); color:#fff; padding:10px 20px;">
                            <i class="fa-solid fa-plus"></i> Add Produce
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card green">
                    <div class="stat-icon green"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div><div class="stat-value"><?= $pc ?></div><div class="stat-label">Products Listed</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card gold">
                    <div class="stat-icon gold"><i class="fa-solid fa-coins"></i></div>
                    <div><div class="stat-value">৳<?= number_format($rev) ?></div><div class="stat-label">Total Revenue</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i class="fa-solid fa-umbrella-beach"></i></div>
                    <div><div class="stat-value"><?= $tc ?></div><div class="stat-label">Farm Tours</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card red">
                    <div class="stat-icon red"><i class="fa-solid fa-user-doctor"></i></div>
                    <div><div class="stat-value"><?= $cc ?></div><div class="stat-label">Consultations</div></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd">
                        <h5><i class="fa-solid fa-basket-shopping me-2" style="color:var(--primary);"></i>Recent Produce</h5>
                        <a href="/KrishiDisha/farmer/produce.php" class="btn-kd btn-kd-outline" style="padding:6px 14px; font-size:12px;">Manage All</a>
                    </div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Crop</th><th>Quantity</th><th>Price/kg</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_products as $p): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($p['crop_name']) ?></td>
                                <td><?= $p['quantity_kg'] ?> kg</td>
                                <td style="color:var(--primary); font-weight:700;">৳<?= $p['price_per_kg'] ?></td>
                                <td><?php $sc=['available'=>'badge-success','sold'=>'badge-muted','pending'=>'badge-warning']; ?><span class="badge-kd <?= $sc[$p['status']]??'badge-muted' ?>"><?= ucfirst($p['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_products)): ?>
                            <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">No produce listed yet. <a href="/KrishiDisha/farmer/produce.php">Add your first product</a></td></tr>
                            <?php endif; ?>
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
                            <a href="/KrishiDisha/farmer/produce.php" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-plus"></i> List New Produce</a>
                            <a href="/KrishiDisha/farmer/farmland.php" class="btn-kd btn-kd-outline w-100 justify-content-center"><i class="fa-solid fa-tractor"></i> Manage Farm Tours</a>
                            <a href="/KrishiDisha/farmer/consultation.php" class="btn-kd btn-kd-gold w-100 justify-content-center" style="color:#fff;"><i class="fa-solid fa-user-doctor"></i> Book Expert</a>
                            <a href="/KrishiDisha/modules/recommend.php" class="btn-kd btn-kd-outline w-100 justify-content-center"><i class="fa-solid fa-seedling"></i> Crop Recommender</a>
                            <a href="/KrishiDisha/modules/calculator.php" class="btn-kd btn-kd-outline w-100 justify-content-center"><i class="fa-solid fa-calculator"></i> Profit Calculator</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
