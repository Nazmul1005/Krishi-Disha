<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['admin']);

$stats = [
    'total_users'    => $pdo->query("SELECT COUNT(*) FROM USER")->fetchColumn(),
    'pending'        => $pdo->query("SELECT COUNT(*) FROM USER WHERE status='pending'")->fetchColumn(),
    'total_orders'   => $pdo->query("SELECT COUNT(*) FROM `ORDER`")->fetchColumn(),
    'total_commission'=> $pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM ADMIN_COMMISSION")->fetchColumn(),
    'crops'          => $pdo->query("SELECT COUNT(*) FROM CROP")->fetchColumn(),
    'products'       => $pdo->query("SELECT COUNT(*) FROM PRODUCT WHERE status='available'")->fetchColumn(),
];

$recent_users = $pdo->query("SELECT id,name,email,role,status,created_at FROM USER ORDER BY created_at DESC LIMIT 8")->fetchAll();
$recent_payments = $pdo->query("SELECT p.*, u.name as payer_name FROM PAYMENT p JOIN USER u ON p.payer_id=u.id ORDER BY p.paid_at DESC LIMIT 6")->fetchAll();

$page_title = 'Admin Dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px; color:var(--primary);">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="topbar-title"><i class="fa-solid fa-gauge-high me-2" style="color:var(--primary);"></i>Admin Dashboard</div>
        </div>
        <div class="topbar-actions">
            <span class="badge-kd badge-success"><i class="fa-solid fa-circle" style="font-size:8px;"></i> System Online</span>
            <span style="font-size:14px; color:var(--text-muted);"><?= date('d M Y') ?></span>
        </div>
    </div>
    <div class="page-body">
        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card green">
                    <div class="stat-icon green"><i class="fa-solid fa-users"></i></div>
                    <div><div class="stat-value"><?= $stats['total_users'] ?></div><div class="stat-label">Total Users</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card gold">
                    <div class="stat-icon gold"><i class="fa-solid fa-clock"></i></div>
                    <div><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">Pending Approval</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i class="fa-solid fa-cart-shopping"></i></div>
                    <div><div class="stat-value"><?= $stats['total_orders'] ?></div><div class="stat-label">Total Orders</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card green">
                    <div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div><div class="stat-value">৳<?= number_format($stats['total_commission']) ?></div><div class="stat-label">Commission</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i class="fa-solid fa-seedling"></i></div>
                    <div><div class="stat-value"><?= $stats['crops'] ?></div><div class="stat-label">Crop Entries</div></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card gold">
                    <div class="stat-icon gold"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div><div class="stat-value"><?= $stats['products'] ?></div><div class="stat-label">Active Products</div></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Users -->
            <div class="col-lg-7">
                <div class="card-kd">
                    <div class="card-header-kd">
                        <h5><i class="fa-solid fa-users me-2" style="color:var(--primary);"></i>Recent Registrations</h5>
                        <a href="/KrishiDisha/admin/users.php" class="btn-kd btn-kd-outline" style="padding:6px 14px; font-size:12px;">View All</a>
                    </div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_users as $u): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td><span class="badge-kd badge-info"><?= ucfirst($u['role']) ?></span></td>
                                <td>
                                    <?php
                                    $cls = ['approved'=>'badge-success','pending'=>'badge-warning','suspended'=>'badge-danger'];
                                    $cls2 = $cls[$u['status']] ?? 'badge-muted';
                                    ?>
                                    <span class="badge-kd <?= $cls2 ?>"><?= ucfirst($u['status']) ?></span>
                                </td>
                                <td style="font-size:12px; color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <?php if ($u['status'] === 'pending'): ?>
                                    <a href="/KrishiDisha/admin/approvals.php?approve=<?= $u['id'] ?>" class="btn-kd btn-kd-primary" style="padding:4px 10px; font-size:11px;" data-confirm="Approve this user?">Approve</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="col-lg-5">
                <div class="card-kd">
                    <div class="card-header-kd">
                        <h5><i class="fa-solid fa-credit-card me-2" style="color:var(--primary);"></i>Recent Payments</h5>
                        <a href="/KrishiDisha/admin/commissions.php" class="btn-kd btn-kd-outline" style="padding:6px 14px; font-size:12px;">Commissions</a>
                    </div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Payer</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_payments as $p): ?>
                            <tr>
                                <td style="font-size:13px; font-weight:600;"><?= htmlspecialchars($p['payer_name']) ?></td>
                                <td><span class="badge-kd badge-info" style="font-size:10px;"><?= str_replace('_',' ', ucfirst($p['ref_type'])) ?></span></td>
                                <td style="font-weight:700; color:var(--primary);">৳<?= number_format($p['amount']) ?></td>
                                <td>
                                    <?php $pc = ['completed'=>'badge-success','pending'=>'badge-warning','failed'=>'badge-danger']; ?>
                                    <span class="badge-kd <?= $pc[$p['status']] ?? 'badge-muted' ?>"><?= ucfirst($p['status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card-kd mt-4">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-bolt me-2" style="color:var(--gold);"></i>Quick Actions</h5></div>
                    <div class="card-body-kd">
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <a href="/KrishiDisha/admin/approvals.php" class="btn-kd btn-kd-primary w-100 justify-content-center">
                                <i class="fa-solid fa-user-check"></i> Review Pending (<?= $stats['pending'] ?>)
                            </a>
                            <a href="/KrishiDisha/admin/users.php" class="btn-kd btn-kd-outline w-100 justify-content-center">
                                <i class="fa-solid fa-users"></i> Manage All Users
                            </a>
                            <a href="/KrishiDisha/admin/commissions.php" class="btn-kd btn-kd-gold w-100 justify-content-center" style="color:#fff;">
                                <i class="fa-solid fa-hand-holding-dollar"></i> View Commissions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
