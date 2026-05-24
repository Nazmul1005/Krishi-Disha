<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['admin']);

if (isset($_GET['settle'])) {
    $pdo->prepare("UPDATE ADMIN_COMMISSION SET settled=1, settled_at=NOW() WHERE id=?")->execute([(int)$_GET['settle']]);
    header('Location: commissions.php?msg=settled'); exit;
}

$commissions = $pdo->query("
    SELECT ac.*, p.amount, p.ref_type, p.ref_id, p.status as pay_status, u.name as payer
    FROM ADMIN_COMMISSION ac
    JOIN PAYMENT p ON ac.payment_id = p.id
    JOIN USER u ON p.payer_id = u.id
    ORDER BY ac.id DESC
")->fetchAll();

$total_earned  = $pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM ADMIN_COMMISSION")->fetchColumn();
$total_settled = $pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM ADMIN_COMMISSION WHERE settled=1")->fetchColumn();
$total_pending = $total_earned - $total_settled;

$page_title = 'Commission Tracker';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">Commission Tracker</div>
        </div>
    </div>
    <div class="page-body">
        <?php if (isset($_GET['msg'])): ?>
        <div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> Commission marked as settled.</div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card green">
                    <div class="stat-icon green"><i class="fa-solid fa-coins"></i></div>
                    <div><div class="stat-value">৳<?= number_format($total_earned, 2) ?></div><div class="stat-label">Total Commission Earned</div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i class="fa-solid fa-circle-check"></i></div>
                    <div><div class="stat-value">৳<?= number_format($total_settled, 2) ?></div><div class="stat-label">Settled</div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card gold">
                    <div class="stat-icon gold"><i class="fa-solid fa-clock"></i></div>
                    <div><div class="stat-value">৳<?= number_format($total_pending, 2) ?></div><div class="stat-label">Pending Settlement</div></div>
                </div>
            </div>
        </div>

        <div class="card-kd">
            <div class="card-header-kd">
                <h5><i class="fa-solid fa-receipt me-2" style="color:var(--primary);"></i>Commission Records (<?= count($commissions) ?>)</h5>
            </div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>#</th><th>Payer</th><th>Transaction Type</th><th>Txn Amount</th><th>Rate</th><th>Commission</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($commissions as $c): ?>
                    <tr>
                        <td style="color:var(--text-muted); font-size:12px;"><?= $c['id'] ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($c['payer']) ?></td>
                        <td><span class="badge-kd badge-info" style="font-size:10px;"><?= str_replace('_',' ', ucfirst($c['ref_type'])) ?></span></td>
                        <td style="font-weight:700;">৳<?= number_format($c['amount'], 2) ?></td>
                        <td><?= $c['commission_rate'] ?>%</td>
                        <td style="font-weight:700; color:var(--primary);">৳<?= number_format($c['commission_amount'], 2) ?></td>
                        <td>
                            <?php if ($c['settled']): ?>
                            <span class="badge-kd badge-success"><i class="fa-solid fa-check"></i> Settled</span>
                            <?php else: ?>
                            <span class="badge-kd badge-warning">Unsettled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$c['settled']): ?>
                            <a href="?settle=<?= $c['id'] ?>" class="btn-kd btn-kd-primary" style="padding:4px 12px; font-size:11px;" data-confirm="Mark as settled?">
                                <i class="fa-solid fa-check"></i> Settle
                            </a>
                            <?php else: ?>
                            <span style="font-size:11px; color:var(--text-muted);"><?= $c['settled_at'] ? date('d M Y', strtotime($c['settled_at'])) : '' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($commissions)): ?>
                    <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">No commission records yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
