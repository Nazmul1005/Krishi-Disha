<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['farmer']);

$farmer = $pdo->prepare("SELECT * FROM FARMER WHERE user_id=?");
$farmer->execute([$_SESSION['user_id']]);
$f = $farmer->fetch();
$fid = $f['id'] ?? 0;

$msg = $err = '';

// Add farm tour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $capacity    = (int)($_POST['capacity'] ?? 10);
    $price       = (float)($_POST['price_per_day'] ?? 0);
    if ($title && $location && $price > 0) {
        $pdo->prepare("INSERT INTO FARM_TOUR (farmer_id,title,description,location,capacity,price_per_day) VALUES (?,?,?,?,?,?)")
            ->execute([$fid, $title, $description, $location, $capacity, $price]);
        $msg = 'Farm tour listed successfully!';
    } else { $err = 'Please fill all required fields.'; }
}

if (isset($_GET['toggle'])) {
    $tour = $pdo->prepare("SELECT status FROM FARM_TOUR WHERE id=? AND farmer_id=?");
    $tour->execute([(int)$_GET['toggle'], $fid]);
    $tour = $tour->fetch();
    if ($tour) {
        $newStatus = $tour['status'] === 'active' ? 'inactive' : 'active';
        $pdo->prepare("UPDATE FARM_TOUR SET status=? WHERE id=? AND farmer_id=?")->execute([$newStatus, (int)$_GET['toggle'], $fid]);
    }
    header('Location: farmland.php'); exit;
}

$tours = $pdo->prepare("SELECT ft.*, (SELECT COUNT(*) FROM TOUR_BOOKING tb WHERE tb.tour_id=ft.id) as total_bookings FROM FARM_TOUR ft WHERE ft.farmer_id=? ORDER BY ft.created_at DESC");
$tours->execute([$fid]);
$tours = $tours->fetchAll();

$page_title = 'My Farm Lands';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title"><i class="fa-solid fa-tractor me-2" style="color:var(--primary);"></i>My Farm Lands & Tours</div>
        </div>
    </div>
    <div class="page-body">
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-plus me-2" style="color:var(--primary);"></i>List Farm for Tourism</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate>
                            <div class="form-group"><label>Tour Title <span style="color:red">*</span></label><input type="text" name="title" class="form-control" placeholder="e.g. Green Valley Eco-Tour" required></div>
                            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3" placeholder="What will tourists experience?"></textarea></div>
                            <div class="form-group"><label>Location <span style="color:red">*</span></label><input type="text" name="location" class="form-control" placeholder="District, Bangladesh" required></div>
                            <div class="form-group"><label>Max Capacity</label><input type="number" name="capacity" class="form-control" value="10" min="1"></div>
                            <div class="form-group"><label>Price per Day (৳) <span style="color:red">*</span></label><input type="number" name="price_per_day" step="50" min="500" class="form-control" required></div>
                            <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-plus"></i> Add Tour</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd"><h5>My Farm Tours (<?= count($tours) ?>)</h5></div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Title</th><th>Location</th><th>Price/Day</th><th>Bookings</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($tours as $t): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($t['title']) ?></td>
                                <td style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($t['location']) ?></td>
                                <td style="color:var(--primary);font-weight:700;">৳<?= number_format($t['price_per_day']) ?></td>
                                <td><?= $t['total_bookings'] ?></td>
                                <td><span class="badge-kd <?= $t['status']==='active'?'badge-success':'badge-muted' ?>"><?= ucfirst($t['status']) ?></span></td>
                                <td><a href="?toggle=<?= $t['id'] ?>" class="btn-kd btn-kd-outline" style="padding:4px 10px;font-size:11px;"><?= $t['status']==='active'?'Deactivate':'Activate' ?></a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tours)): ?><tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">No farm tours listed yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
