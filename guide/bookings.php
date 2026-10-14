<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['guide']);
$guide = $pdo->prepare("SELECT * FROM GUIDE WHERE user_id=?"); $guide->execute([$_SESSION['user_id']]); $g = $guide->fetch(); $gid = $g['id'] ?? 0;

require_once __DIR__.'/../includes/transactions.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
    try { changeState($pdo,(int)$_SESSION['user_id'],'tour_booking',(int)($_POST['id'] ?? 0),(string)($_POST['status'] ?? ''));flash('success','Status updated. Payment remains separate.'); }
    catch(Throwable $e) { flash('error',$e instanceof InvalidArgumentException ? $e->getMessage() : 'Status could not be updated.'); }
    header('Location: bookings.php');exit;
}
$bookings = $pdo->prepare("SELECT tb.*, ft.title, ft.location, u.name as tourist_name FROM TOUR_BOOKING tb JOIN FARM_TOUR ft ON tb.tour_id=ft.id JOIN TOURIST t ON tb.tourist_id=t.id JOIN USER u ON t.user_id=u.id WHERE tb.guide_id=? ORDER BY tb.start_date DESC"); $bookings=pagedRows($pdo,$bookings->queryString,[$gid],'bookings','Tour bookings',['title', 'tourist_name'],true);
$page_title = 'My Bookings';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><h1 class="topbar-title"><i class="fa-solid fa-route me-2" style="color:#126078;"></i>My Tour Bookings</h1></div></div>
    <div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><?= renderFlash() ?>
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
                        <td style="font-size:12px;"><?= e($b['start_date']) ?> → <?= e($b['end_date']) ?></td>
                        <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$b['status']]??'badge-muted' ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                        <td>
                            <?php if ($b['status']==='pending'): ?>

                            <?php elseif ($b['status']==='confirmed'): ?>

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
