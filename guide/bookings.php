<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['guide']);
$guide = $pdo->prepare("SELECT * FROM GUIDE WHERE user_id=?"); $guide->execute([$_SESSION['user_id']]); $g = $guide->fetch(); $gid = $g['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    verifyCsrf();
    $valid = ['confirmed','completed','cancelled'];
    if (in_array($_POST['status'], $valid, true)) $pdo->prepare("UPDATE TOUR_BOOKING SET status=? WHERE id=? AND guide_id=?")->execute([$_POST['status'],(int)$_POST['id'],$gid]);
    header('Location: bookings.php'); exit;
}

$bookings = $pdo->prepare("SELECT tb.*, ft.title, ft.location, u.name as tourist_name FROM TOUR_BOOKING tb JOIN FARM_TOUR ft ON tb.tour_id=ft.id JOIN TOURIST t ON tb.tourist_id=t.id JOIN USER u ON t.user_id=u.id WHERE tb.guide_id=? ORDER BY tb.start_date DESC"); $bookings->execute([$gid]); $bookings = $bookings->fetchAll();
$page_title = 'My Bookings';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><i class="fa-solid fa-route me-2" style="color:#0891b2;"></i>My Tour Bookings</div></div></div>
    <div class="page-body">
        <div class="card-kd">
            <div class="card-header-kd"><h5>All Bookings (<?= count($bookings) ?>)</h5></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>Tourist</th><th>Farm Tour</th><th>Location</th><th>Dates</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($b['tourist_name']) ?></td>
                        <td><?= htmlspecialchars($b['title']) ?></td>
                        <td style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($b['location']) ?></td>
                        <td style="font-size:12px;"><?= $b['start_date'] ?> → <?= $b['end_date'] ?></td>
                        <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$b['status']]??'badge-muted' ?>"><?= ucfirst($b['status']) ?></span></td>
                        <td>
                            <?php if ($b['status']==='pending'): ?>
                            <form method="POST" style="display:inline;"><?= csrfField() ?><input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="status" value="confirmed"><button type="submit" class="btn-kd btn-kd-primary" style="padding:4px 8px;font-size:11px;">Accept</button></form>
                            <?php elseif ($b['status']==='confirmed'): ?>
                            <form method="POST" style="display:inline;"><?= csrfField() ?><input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="status" value="completed"><button type="submit" class="btn-kd btn-kd-gold" style="padding:4px 8px;font-size:11px;color:#fff;">Complete</button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?><tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">No bookings yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
