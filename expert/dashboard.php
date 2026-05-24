<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['expert']);

$expert = $pdo->prepare("SELECT e.* FROM EXPERT e WHERE e.user_id=?");
$expert->execute([$_SESSION['user_id']]);
$ex = $expert->fetch();
$eid = $ex['id'] ?? 0;

$total_sessions   = $pdo->prepare("SELECT COUNT(*) FROM CONSULTATION WHERE provider_id=?"); $total_sessions->execute([$_SESSION['user_id']]); $ts = $total_sessions->fetchColumn();
$completed        = $pdo->prepare("SELECT COUNT(*) FROM CONSULTATION WHERE provider_id=? AND status='completed'"); $completed->execute([$_SESSION['user_id']]); $cs = $completed->fetchColumn();
$pending_sessions = $pdo->prepare("SELECT COUNT(*) FROM CONSULTATION WHERE provider_id=? AND status='pending'"); $pending_sessions->execute([$_SESSION['user_id']]); $ps = $pending_sessions->fetchColumn();
$earned           = $pdo->prepare("SELECT COALESCE(SUM(fee),0) FROM CONSULTATION WHERE provider_id=? AND status='completed'"); $earned->execute([$_SESSION['user_id']]); $earn = $earned->fetchColumn();

$upcoming = $pdo->prepare("SELECT c.*, u.name as client_name, u.role as client_role FROM CONSULTATION c JOIN USER u ON c.client_id=u.id WHERE c.provider_id=? AND c.status IN ('pending','confirmed') ORDER BY c.scheduled_date ASC LIMIT 5");
$upcoming->execute([$_SESSION['user_id']]);
$upcoming = $upcoming->fetchAll();

$page_title = 'Expert Dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">🔬 Expert Dashboard</div>
        </div>
        <div class="topbar-actions">
            <span class="badge-kd <?= ($ex['availability']??'') === 'available' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst($ex['availability'] ?? '') ?></span>
        </div>
    </div>
    <div class="page-body">
        <?php if ($ex): ?>
        <div class="card-kd mb-4" style="background:linear-gradient(135deg,#4c1d95,#7c3aed); color:#fff; border:none;">
            <div class="card-body-kd">
                <h5 style="color:#fff; margin-bottom:6px;"><?= htmlspecialchars($ex['specialization'] ?? '') ?></h5>
                <p style="color:rgba(255,255,255,0.8); margin:0; font-size:14px;">
                    <i class="fa-solid fa-graduation-cap me-1"></i><?= htmlspecialchars($ex['qualification'] ?? '') ?> &nbsp;·&nbsp;
                    <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i>৳<?= $ex['hourly_rate'] ?? 0 ?>/hr
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3"><div class="stat-card blue"><div class="stat-icon blue"><i class="fa-solid fa-calendar-days"></i></div><div><div class="stat-value"><?= $ts ?></div><div class="stat-label">Total Sessions</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div><div class="stat-value"><?= $cs ?></div><div class="stat-label">Completed</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card gold"><div class="stat-icon gold"><i class="fa-solid fa-clock"></i></div><div><div class="stat-value"><?= $ps ?></div><div class="stat-label">Pending</div></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-coins"></i></div><div><div class="stat-value">৳<?= number_format($earn) ?></div><div class="stat-label">Earned</div></div></div></div>
        </div>

        <div class="card-kd">
            <div class="card-header-kd"><h5><i class="fa-solid fa-calendar-check me-2" style="color:#7c3aed;"></i>Upcoming Sessions</h5>
            <a href="/KrishiDisha/expert/sessions.php" class="btn-kd btn-kd-outline" style="padding:6px 14px; font-size:12px;">All Sessions</a></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>Client</th><th>Topic</th><th>Date</th><th>Duration</th><th>Fee</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $s): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($s['client_name']) ?> <span class="badge-kd badge-muted" style="font-size:10px"><?= ucfirst($s['client_role']) ?></span></td>
                        <td style="font-size:13px;"><?= htmlspecialchars($s['topic']) ?></td>
                        <td><?= $s['scheduled_date'] ?></td>
                        <td><?= $s['duration_hours'] ?> hr</td>
                        <td style="color:#7c3aed; font-weight:700;">৳<?= number_format($s['fee']) ?></td>
                        <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$s['status']]??'badge-muted' ?>"><?= ucfirst($s['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($upcoming)): ?><tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">No upcoming sessions.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
