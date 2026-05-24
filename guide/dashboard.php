<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['guide']);

$guide = $pdo->prepare("SELECT g.* FROM GUIDE g WHERE g.user_id=?");
$guide->execute([$_SESSION['user_id']]);
$g = $guide->fetch();
$gid = $g['id'] ?? 0;

$bookings  = $pdo->prepare("SELECT COUNT(*) FROM TOUR_BOOKING WHERE guide_id=?"); $bookings->execute([$gid]); $bc = $bookings->fetchColumn();
$completed = $pdo->prepare("SELECT COUNT(*) FROM TOUR_BOOKING WHERE guide_id=? AND status='completed'"); $completed->execute([$gid]); $cc = $completed->fetchColumn();

$upcoming = $pdo->prepare("SELECT tb.*, ft.title, ft.location, u.name as tourist_name FROM TOUR_BOOKING tb JOIN FARM_TOUR ft ON tb.tour_id=ft.id JOIN TOURIST t ON tb.tourist_id=t.id JOIN USER u ON t.user_id=u.id WHERE tb.guide_id=? ORDER BY tb.start_date ASC LIMIT 6");
$upcoming->execute([$gid]);
$upcoming = $upcoming->fetchAll();

$page_title = 'Guide Dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">🗺️ Guide Dashboard</div>
        </div>
    </div>
    <div class="page-body">
        <div class="row g-4 mb-4">
            <div class="col-md-6"><div class="stat-card blue"><div class="stat-icon blue"><i class="fa-solid fa-route"></i></div><div><div class="stat-value"><?= $bc ?></div><div class="stat-label">Total Bookings</div></div></div></div>
            <div class="col-md-6"><div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div><div class="stat-value"><?= $cc ?></div><div class="stat-label">Completed Tours</div></div></div></div>
        </div>
        <div class="card-kd">
            <div class="card-header-kd"><h5>Upcoming Tours</h5><a href="/KrishiDisha/guide/bookings.php" class="btn-kd btn-kd-outline" style="padding:6px 14px; font-size:12px;">All</a></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>Tourist</th><th>Farm</th><th>Dates</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $b): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($b['tourist_name']) ?></td>
                        <td><?= htmlspecialchars($b['title']) ?></td>
                        <td style="font-size:12px;"><?= $b['start_date'] ?> → <?= $b['end_date'] ?></td>
                        <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$b['status']]??'badge-muted' ?>"><?= ucfirst($b['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($upcoming)): ?><tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">No bookings yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
