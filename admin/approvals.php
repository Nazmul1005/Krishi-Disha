<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['admin']);

if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $action=null;$id=0;
        foreach(['approve','suspend','delete','reject'] as $candidate) if(isset($_POST[$candidate])) { $action=$candidate;$id=(int)$_POST[$candidate];break; }
        if(!$action) throw new InvalidArgumentException('Select an action.');
        transaction($pdo,function()use($pdo,$action,$id) {
            $stmt=$pdo->prepare('SELECT * FROM USER WHERE id=? FOR UPDATE');$stmt->execute([$id]);$user=$stmt->fetch();
            if(!$user || $user['role']==='admin') throw new InvalidArgumentException('Administrator accounts must be managed through the secure CLI.');
            if($action==='approve' && !(int)$user['email_verified']) throw new InvalidArgumentException('Email must be verified before approval.');
            $status=$action==='approve' ? 'approved' : 'suspended';
            if($action==='approve') {
                $notes=textInput($_POST['review_notes'] ?? '',3000);if(($_POST['review_ack'] ?? '')!=='1') throw new InvalidArgumentException('Confirm identity/qualification review.');
                $pdo->prepare('UPDATE USER SET status=?,auth_version=auth_version+1,approval_notes=?,approved_by=?,approved_at=NOW() WHERE id=?')->execute([$status,$notes,$_SESSION['user_id'],$id]);
            } else $pdo->prepare('UPDATE USER SET status=?,auth_version=auth_version+1 WHERE id=?')->execute([$status,$id]);
            audit($pdo,'account.'.$action,'USER',$id,['previous'=>$user['status'],'status'=>$status]);
        });flash('success','Account updated. Records have been retained.');
    } catch(Throwable $e) { flash('error',$e instanceof InvalidArgumentException ? $e->getMessage() : 'Account could not be updated.'); }
    header('Location: approvals.php');exit;
}
$pending=pagedRows($pdo,"SELECT * FROM USER WHERE status='pending' ORDER BY created_at ASC",[],'applications','Provider applications',['name', 'email'],false);
$msg     = $_GET['msg'] ?? '';
$page_title = 'Pending Approvals';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">

            <h1 class="topbar-title">Pending Approvals</h1>
        </div>
        <div class="topbar-actions">
            <span class="badge-kd badge-warning"><i class="fa-solid fa-clock"></i> <?= count($pending) ?> pending</span>
        </div>
    </div>
    <div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><?= renderFlash() ?>
        <?php if ($msg === 'approved'): ?>
        <div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> User approved successfully.</div>
        <?php elseif ($msg === 'rejected'): ?>
        <div class="alert-kd alert-kd-warning" data-autohide="3000"><i class="fa-solid fa-ban"></i> Registration suspended; records retained.</div>
        <?php endif; ?>

        <?php if (empty($pending)): ?>
        <div class="card-kd">
            <div class="card-body-kd text-center py-5">
                <div style="font-size:64px; margin-bottom:16px;"><i aria-hidden="true" class="fa-solid fa-circle-check"></i></div>
                <h4 style="color:var(--primary-dark);">All Clear!</h4>
                <p style="color:var(--text-muted);">No pending registrations at this time.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($pending as $u): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-kd">
                    <div class="card-body-kd">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:48px;height:48px;background:var(--surface3);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--primary);">
                                <i class="fa-solid fa-circle-user"></i>
                            </div>
                            <div>
                                <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars($u['name']) ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                        </div>
                        <div style="display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
                            <span class="badge-kd badge-info"><?= e(ucfirst($u['role'])) ?></span>
                            <span class="badge-kd badge-warning">Pending</span>
                        </div>
                        <div style="font-size:12px; color:var(--text-muted); margin-bottom:16px;">
                            <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($u['phone'] ?? 'N/A') ?><br>
                            <i class="fa-solid fa-calendar me-1 mt-1"></i>Registered <?= date('d M Y, g:i A', strtotime($u['created_at'])) ?>
                        </div>
                        <p><?= nl2br(e($u['application_details'] ?? 'No application details supplied. Contact applicant before approval.')) ?></p><div class="d-flex gap-2">
                            <form method="POST" action="" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="approve" value="<?= e($u['id']) ?>"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Review notes</label><input id="<?= e($fieldId) ?>" class="form-control" name="review_notes" maxlength="3000" required><label><input type="checkbox" name="review_ack" value="1" required> Identity/qualification checked</label><button type="submit" class="btn-kd btn-kd-primary flex-fill justify-content-center" data-confirm="Approve <?= htmlspecialchars($u['name']) ?>?">
                                <i class="fa-solid fa-check"></i> Approve
                            </button></form>
                            <form method="POST" action="" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="reject" value="<?= e($u['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger flex-fill justify-content-center" data-confirm="Suspend this registration?">
                                <i class="fa-solid fa-xmark"></i> Reject
                            </button></form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
