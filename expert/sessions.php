<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['expert']);
$expert = $pdo->prepare("SELECT * FROM EXPERT WHERE user_id=?"); $expert->execute([$_SESSION['user_id']]); $ex = $expert->fetch(); $eid = $ex['id'] ?? 0;

if (isset($_GET['status']) && isset($_GET['id'])) {
    $valid = ['confirmed','completed','cancelled'];
    if (in_array($_GET['status'], $valid)) $pdo->prepare("UPDATE CONSULTATION SET status=? WHERE id=? AND expert_id=?")->execute([$_GET['status'],(int)$_GET['id'],$eid]);
    header('Location: sessions.php'); exit;
}

$sessions = $pdo->prepare("SELECT c.*, u.name as farmer_name FROM CONSULTATION c JOIN FARMER f ON c.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE c.expert_id=? ORDER BY c.scheduled_date DESC"); $sessions->execute([$eid]); $sessions = $sessions->fetchAll();
$page_title = 'My Sessions';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><i class="fa-solid fa-calendar-check me-2" style="color:#7c3aed;"></i>My Consultation Sessions</div></div></div>
    <div class="page-body">
        <div class="card-kd">
            <div class="card-header-kd"><h5>All Sessions (<?= count($sessions) ?>)</h5></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>Farmer</th><th>Topic</th><th>Date</th><th>Duration</th><th>Fee</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($s['farmer_name']) ?></td>
                        <td style="font-size:13px;"><?= mb_substr(htmlspecialchars($s['topic']),0,60) ?></td>
                        <td><?= $s['scheduled_date'] ?></td>
                        <td><?= $s['duration_hours'] ?> hr</td>
                        <td style="color:#7c3aed;font-weight:700;">৳<?= number_format($s['fee']) ?></td>
                        <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$s['status']]??'badge-muted' ?>"><?= ucfirst($s['status']) ?></span></td>
                        <td>
                            <?php if ($s['status']==='pending'): ?><a href="?id=<?= $s['id'] ?>&status=confirmed" class="btn-kd btn-kd-primary" style="padding:4px 8px;font-size:11px;">Accept</a>
                            <?php elseif ($s['status']==='confirmed'): ?><a href="?id=<?= $s['id'] ?>&status=completed" class="btn-kd btn-kd-gold" style="padding:4px 8px;font-size:11px;color:#fff;">Complete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sessions)): ?><tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">No sessions yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
