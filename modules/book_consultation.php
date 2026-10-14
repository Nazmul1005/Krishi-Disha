<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['farmer', 'general', 'tourist']);

$msg = $err = '';

require_once __DIR__.'/../includes/transactions.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
    try { if(isset($_POST['cancel'])) { changeState($pdo,(int)$_SESSION['user_id'],'consultation',(int)$_POST['cancel'],'cancelled');flash('success','Booking cancelled.');header('Location: book_consultation.php');exit; } $id=bookConsultation($pdo,(int)$_SESSION['user_id'],requestKey(),$_POST);flash('success','Booking #'.$id.' requested. Cash is due after the service.'); }
    catch(Throwable $e) { error_log('Booking rejected: '.$e->getMessage());flash('error',$e instanceof InvalidArgumentException ? $e->getMessage() : 'Could not book. Please try again.'); }
    header('Location: book_consultation.php');exit;
}
$experts = $pdo->query("SELECT e.user_id, e.hourly_rate as rate, e.specialization, u.name, 'Expert' as role FROM EXPERT e JOIN USER u ON e.user_id=u.id WHERE u.status='approved' AND e.availability='available'")->fetchAll();
$guides = $pdo->query("SELECT g.user_id, (g.daily_rate/8) as rate, g.languages as specialization, u.name, 'Guide' as role FROM GUIDE g JOIN USER u ON g.user_id=u.id WHERE u.status='approved' AND g.availability='available'")->fetchAll();

$providers = array_merge($experts, $guides);

$pre_provider = isset($_GET['provider']) ? (int)$_GET['provider'] : 0;

$my_consultations = $pdo->prepare("SELECT c.*, u.name as provider_name, u.role as provider_role FROM CONSULTATION c JOIN USER u ON c.provider_id=u.id WHERE c.client_id=? ORDER BY c.created_at DESC");
$my_consultations=pagedRows($pdo,$my_consultations->queryString,[$_SESSION['user_id']],'consultations','Your consultations',['provider_name', 'topic'],true);

$page_title = 'Consultations';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">

            <h1 class="topbar-title"><i class="fa-solid fa-user-doctor me-2" style="color:#7c3aed;"></i>Book Consultation</h1>
        </div>
    </div>
    <div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><?= renderFlash() ?>
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="4000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-calendar-plus me-2" style="color:#7c3aed;"></i>Book a Session</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Select Provider <span style="color:red">*</span></label>
                                <select id="<?= e($fieldId) ?>" name="provider_id" class="form-control" required>
                                    <option value="">Choose expert or guide</option>
                                    <?php foreach ($providers as $pr): ?>
                                    <option value="<?= e($pr['user_id']) ?>" <?= $pre_provider===$pr['user_id']?'selected':'' ?>>
                                        <?= htmlspecialchars($pr['name']) ?> (<?= e($pr['role']) ?>) — ৳<?= number_format($pr['rate']) ?>/hr
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Date <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="datetime-local" name="scheduled_at" class="form-control" min="<?= date('Y-m-d\TH:i') ?>" required></div>
                            <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Duration (hours) <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="number" name="duration_hours" step="0.5" min="0.5" max="8" value="1" class="form-control" required></div>
                            <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Topic <span style="color:red">*</span></label><textarea id="<?= e($fieldId) ?>" name="topic" class="form-control" rows="3" placeholder="Describe what you need advice on..." required></textarea></div>
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
                            <thead><tr><th>Provider</th><th>Topic</th><th>Date</th><th>Duration</th><th>Fee</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($my_consultations as $c): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($c['provider_name']) ?> <span class="badge-kd badge-muted" style="font-size:10px;"><?= e(ucfirst($c['provider_role'])) ?></span></div>
                                </td>
                                <td style="font-size:13px;"><?= mb_substr(htmlspecialchars($c['topic']),0,60) ?></td>
                                <td><?= e($c['scheduled_at'] ?? $c['scheduled_date']) ?></td>
                                <td><?= e($c['duration_hours']) ?> hr</td>
                                <td style="font-weight:700;color:#7c3aed;">৳<?= number_format($c['fee']) ?></td>
                                <td><?php $sc=['pending'=>'badge-warning','confirmed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$c['status']]??'badge-muted' ?>"><?= e(ucfirst($c['status'])) ?></span><?php if($c['status']==='pending'): ?><?= actionButton('cancel',(int)$c['id'],'Cancel') ?><?php endif; ?></td>
                                <td>
                                    <a href="/KrishiDisha/modules/consultation_chat.php?id=<?= e($c['id']) ?>" class="btn-kd btn-kd-outline" style="padding:4px 10px; font-size:12px;">
                                        <i class="fa-solid fa-comments"></i> Chat
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($my_consultations)): ?><tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">No consultations booked yet.</td></tr><?php endif; ?>
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
