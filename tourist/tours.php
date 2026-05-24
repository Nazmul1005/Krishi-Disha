<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['tourist']);
$tourist = $pdo->prepare("SELECT * FROM TOURIST WHERE user_id=?"); $tourist->execute([$_SESSION['user_id']]); $t = $tourist->fetch(); $tid = $t['id'] ?? 0;
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tour_id    = (int)$_POST['tour_id'];
    $guide_id   = (int)($_POST['guide_id'] ?? 0) ?: null;
    $start      = $_POST['start_date'] ?? '';
    $end        = $_POST['end_date'] ?? '';
    $visitors   = (int)($_POST['num_visitors'] ?? 1);
    $tour       = $pdo->prepare("SELECT * FROM FARM_TOUR WHERE id=? AND status='active'"); $tour->execute([$tour_id]); $tour = $tour->fetch();
    if ($tour && $start && $end && $start <= $end) {
        $days  = (strtotime($end) - strtotime($start)) / 86400 + 1;
        $total = $tour['price_per_day'] * $days;
        if ($guide_id) {
            $g = $pdo->prepare("SELECT daily_rate FROM GUIDE WHERE id=?"); $g->execute([$guide_id]); $g = $g->fetch();
            if ($g) $total += $g['daily_rate'] * $days;
        }
        $pdo->prepare("INSERT INTO TOUR_BOOKING (tourist_id,tour_id,guide_id,start_date,end_date,num_visitors,total_price) VALUES (?,?,?,?,?,?,?)")->execute([$tid,$tour_id,$guide_id,$start,$end,$visitors,$total]);
        $msg = "Tour booked! Total: ৳" . number_format($total);
    } else { $err = 'Invalid selection or dates.'; }
}

$tours  = $pdo->query("SELECT ft.*, u.name as farmer FROM FARM_TOUR ft JOIN FARMER f ON ft.farmer_id=f.id JOIN USER u ON f.user_id=u.id WHERE ft.status='active'")->fetchAll();
$guides = $pdo->query("SELECT g.*, u.name FROM GUIDE g JOIN USER u ON g.user_id=u.id WHERE g.availability='available'")->fetchAll();
$my_bookings = $pdo->prepare("SELECT tb.*, ft.title, ft.location, ug.name as guide_name FROM TOUR_BOOKING tb JOIN FARM_TOUR ft ON tb.tour_id=ft.id LEFT JOIN GUIDE g ON tb.guide_id=g.id LEFT JOIN USER ug ON g.user_id=ug.id WHERE tb.tourist_id=? ORDER BY tb.created_at DESC"); $my_bookings->execute([$tid]); $my_bookings = $my_bookings->fetchAll();
$pre_tour = isset($_GET['book']) ? (int)$_GET['book'] : 0;
$page_title = 'Farm Tours';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><i class="fa-solid fa-map me-2" style="color:#0891b2;"></i>Farm Tours</div></div></div>
    <div class="page-body">
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="4000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-calendar-plus me-2" style="color:#0891b2;"></i>Book a Tour</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate>
                            <div class="form-group"><label>Farm Tour <span style="color:red">*</span></label>
                            <select name="tour_id" class="form-control" required><option value="">Select farm tour</option>
                            <?php foreach ($tours as $to): ?><option value="<?= $to['id'] ?>" <?= $pre_tour===$to['id']?'selected':'' ?>><?= htmlspecialchars($to['title']) ?> — ৳<?= number_format($to['price_per_day']) ?>/day</option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Hire a Guide (optional)</label>
                            <select name="guide_id" class="form-control"><option value="">No guide</option>
                            <?php foreach ($guides as $g): ?><option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?> — ৳<?= number_format($g['daily_rate']) ?>/day</option><?php endforeach; ?></select></div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                <div class="form-group"><label>Start Date <span style="color:red">*</span></label><input type="date" name="start_date" min="<?= date('Y-m-d') ?>" class="form-control" required></div>
                                <div class="form-group"><label>End Date <span style="color:red">*</span></label><input type="date" name="end_date" min="<?= date('Y-m-d') ?>" class="form-control" required></div>
                            </div>
                            <div class="form-group"><label>Number of Visitors</label><input type="number" name="num_visitors" min="1" max="20" value="1" class="form-control"></div>
                            <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-calendar-check"></i> Book Tour</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd"><h5>My Bookings (<?= count($my_bookings) ?>)</h5></div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Farm</th><th>Guide</th><th>Dates</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($my_bookings as $b): ?>
                            <tr>
                                <td><div style="font-weight:600;"><?= htmlspecialchars($b['title']) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($b['location']) ?></div></td>
                                <td style="font-size:13px;"><?= $b['guide_name'] ? htmlspecialchars($b['guide_name']) : '—' ?></td>
                                <td style="font-size:12px;"><?= $b['start_date'] ?> → <?= $b['end_date'] ?></td>
                                <td style="color:var(--primary);font-weight:700;">৳<?= number_format($b['total_price']) ?></td>
                                <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$b['status']]??'badge-muted' ?>"><?= ucfirst($b['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($my_bookings)): ?><tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">No bookings yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
