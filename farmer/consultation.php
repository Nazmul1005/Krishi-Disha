<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['farmer']);

$farmer = $pdo->prepare("SELECT * FROM FARMER WHERE user_id=?");
$farmer->execute([$_SESSION['user_id']]);
$f = $farmer->fetch();
$fid = $f['id'] ?? 0;

$msg = $err = '';

// Book consultation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expert_id = (int)$_POST['expert_id'];
    $date      = $_POST['scheduled_date'] ?? '';
    $duration  = (float)($_POST['duration_hours'] ?? 1);
    $topic     = trim($_POST['topic'] ?? '');
    if ($expert_id && $date && $topic) {
        $expert = $pdo->prepare("SELECT hourly_rate FROM EXPERT WHERE id=?");
        $expert->execute([$expert_id]);
        $expert = $expert->fetch();
        $fee = $expert['hourly_rate'] * $duration;
        $pdo->prepare("INSERT INTO CONSULTATION (farmer_id,expert_id,scheduled_date,duration_hours,topic,fee) VALUES (?,?,?,?,?,?)")
            ->execute([$fid, $expert_id, $date, $duration, $topic, $fee]);
        $msg = 'Consultation booked! Fee: ৳' . number_format($fee, 2);
    } else { $err = 'Please fill all required fields.'; }
}

$experts = $pdo->query("SELECT e.*, u.name FROM EXPERT e JOIN USER u ON e.user_id=u.id WHERE u.status='approved' AND e.availability='available'")->fetchAll();
$pre_expert = isset($_GET['expert']) ? (int)$_GET['expert'] : 0;

$my_consultations = $pdo->prepare("SELECT c.*, u.name as expert_name, e.specialization FROM CONSULTATION c JOIN EXPERT e ON c.expert_id=e.id JOIN USER u ON e.user_id=u.id WHERE c.farmer_id=? ORDER BY c.created_at DESC");
$my_consultations->execute([$fid]);
$my_consultations = $my_consultations->fetchAll();

$page_title = 'Consultations';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title"><i class="fa-solid fa-user-doctor me-2" style="color:#7c3aed;"></i>Expert Consultations</div>
        </div>
    </div>
    <div class="page-body">
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="4000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-calendar-plus me-2" style="color:#7c3aed;"></i>Book a Session</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate>
                            <div class="form-group">
                                <label>Select Expert <span style="color:red">*</span></label>
                                <select name="expert_id" class="form-control" required>
                                    <option value="">Choose an expert</option>
                                    <?php foreach ($experts as $ex): ?>
                                    <option value="<?= $ex['id'] ?>" <?= $pre_expert===$ex['id']?'selected':'' ?>>
                                        <?= htmlspecialchars($ex['name']) ?> — ৳<?= $ex['hourly_rate'] ?>/hr
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Date <span style="color:red">*</span></label><input type="date" name="scheduled_date" class="form-control" min="<?= date('Y-m-d') ?>" required></div>
                            <div class="form-group"><label>Duration (hours) <span style="color:red">*</span></label><input type="number" name="duration_hours" step="0.5" min="0.5" max="8" value="1" class="form-control" required></div>
                            <div class="form-group"><label>Topic <span style="color:red">*</span></label><textarea name="topic" class="form-control" rows="3" placeholder="Describe what you need advice on..." required></textarea></div>
                            <button type="submit" class="btn-kd w-100 justify-content-center" style="background:linear-gradient(135deg,#4c1d95,#7c3aed);color:#fff;padding:12px;">
                                <i class="fa-solid fa-calendar-check"></i> Book Consultation
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd"><h5>My Consultations (<?= count($my_consultations) ?>)</h5></div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Expert</th><th>Topic</th><th>Date</th><th>Duration</th><th>Fee</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($my_consultations as $c): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($c['expert_name']) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($c['specialization']) ?></div>
                                </td>
                                <td style="font-size:13px;"><?= mb_substr(htmlspecialchars($c['topic']),0,60) ?></td>
                                <td><?= $c['scheduled_date'] ?></td>
                                <td><?= $c['duration_hours'] ?> hr</td>
                                <td style="font-weight:700;color:#7c3aed;">৳<?= number_format($c['fee']) ?></td>
                                <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$c['status']]??'badge-muted' ?>"><?= ucfirst($c['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($my_consultations)): ?><tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">No consultations booked yet.</td></tr><?php endif; ?>
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
