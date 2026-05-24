<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['admin']);

if (isset($_GET['approve'])) {
    $pdo->prepare("UPDATE USER SET status='approved' WHERE id=?")->execute([(int)$_GET['approve']]);
    header('Location: approvals.php?msg=approved'); exit;
}
if (isset($_GET['reject'])) {
    $pdo->prepare("DELETE FROM USER WHERE id=? AND status='pending'")->execute([(int)$_GET['reject']]);
    header('Location: approvals.php?msg=rejected'); exit;
}

$pending = $pdo->query("SELECT * FROM USER WHERE status='pending' ORDER BY created_at ASC")->fetchAll();
$msg     = $_GET['msg'] ?? '';
$page_title = 'Pending Approvals';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">Pending Approvals</div>
        </div>
        <div class="topbar-actions">
            <span class="badge-kd badge-warning"><i class="fa-solid fa-clock"></i> <?= count($pending) ?> pending</span>
        </div>
    </div>
    <div class="page-body">
        <?php if ($msg === 'approved'): ?>
        <div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> User approved successfully.</div>
        <?php elseif ($msg === 'rejected'): ?>
        <div class="alert-kd alert-kd-warning" data-autohide="3000"><i class="fa-solid fa-ban"></i> Registration rejected and removed.</div>
        <?php endif; ?>

        <?php if (empty($pending)): ?>
        <div class="card-kd">
            <div class="card-body-kd text-center py-5">
                <div style="font-size:64px; margin-bottom:16px;">✅</div>
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
                            <span class="badge-kd badge-info"><?= ucfirst($u['role']) ?></span>
                            <span class="badge-kd badge-warning">Pending</span>
                        </div>
                        <div style="font-size:12px; color:var(--text-muted); margin-bottom:16px;">
                            <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($u['phone'] ?? 'N/A') ?><br>
                            <i class="fa-solid fa-calendar me-1 mt-1"></i>Registered <?= date('d M Y, g:i A', strtotime($u['created_at'])) ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="?approve=<?= $u['id'] ?>" class="btn-kd btn-kd-primary flex-fill justify-content-center" data-confirm="Approve <?= htmlspecialchars($u['name']) ?>?">
                                <i class="fa-solid fa-check"></i> Approve
                            </a>
                            <a href="?reject=<?= $u['id'] ?>" class="btn-kd btn-kd-danger flex-fill justify-content-center" data-confirm="Reject and remove this registration?">
                                <i class="fa-solid fa-xmark"></i> Reject
                            </a>
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
