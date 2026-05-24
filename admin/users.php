<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['admin']);

// Handle approve/suspend/delete actions
if (isset($_GET['approve'])) {
    $pdo->prepare("UPDATE USER SET status='approved' WHERE id=?")->execute([(int)$_GET['approve']]);
    header('Location: users.php?msg=approved'); exit;
}
if (isset($_GET['suspend'])) {
    $pdo->prepare("UPDATE USER SET status='suspended' WHERE id=?")->execute([(int)$_GET['suspend']]);
    header('Location: users.php?msg=suspended'); exit;
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM USER WHERE id=? AND role != 'admin'")->execute([(int)$_GET['delete']]);
    header('Location: users.php?msg=deleted'); exit;
}

$role_filter   = $_GET['role']   ?? '';
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');

$where = ['1=1']; $params = [];
if ($role_filter)   { $where[] = "role = ?";    $params[] = $role_filter; }
if ($status_filter) { $where[] = "status = ?";  $params[] = $status_filter; }
if ($search)        { $where[] = "(name LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql   = "SELECT * FROM USER WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$messages = ['approved'=>'User approved.','suspended'=>'User suspended.','deleted'=>'User deleted.'];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';
$page_title = 'Manage Users';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">Manage Users</div>
        </div>
    </div>
    <div class="page-body">
        <?php if ($msg): ?>
        <div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> <?= $msg ?></div>
        <?php endif; ?>

        <!-- Filter bar -->
        <form class="filter-bar" method="GET">
            <input type="text" name="q" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
            <select name="role">
                <option value="">All Roles</option>
                <?php foreach (['admin','farmer','dealer','tourist','cook','expert','guide','general'] as $r): ?>
                <option value="<?= $r ?>" <?= $role_filter===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All Status</option>
                <option value="approved" <?= $status_filter==='approved'?'selected':'' ?>>Approved</option>
                <option value="pending"  <?= $status_filter==='pending'?'selected':'' ?>>Pending</option>
                <option value="suspended"<?= $status_filter==='suspended'?'selected':'' ?>>Suspended</option>
            </select>
            <button type="submit" class="btn-kd btn-kd-primary"><i class="fa-solid fa-search"></i> Filter</button>
            <a href="users.php" class="btn-kd btn-kd-outline">Reset</a>
        </form>

        <div class="card-kd">
            <div class="card-header-kd">
                <h5><i class="fa-solid fa-users me-2" style="color:var(--primary);"></i>All Users (<?= count($users) ?>)</h5>
            </div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="color:var(--text-muted); font-size:12px;"><?= $u['id'] ?></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                        </td>
                        <td><span class="badge-kd badge-info"><?= ucfirst($u['role']) ?></span></td>
                        <td style="font-size:13px;"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                        <td>
                            <?php $cls = ['approved'=>'badge-success','pending'=>'badge-warning','suspended'=>'badge-danger']; ?>
                            <span class="badge-kd <?= $cls[$u['status']] ?? 'badge-muted' ?>"><?= ucfirst($u['status']) ?></span>
                        </td>
                        <td style="font-size:12px; color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <?php if ($u['status'] !== 'approved'): ?>
                                <a href="?approve=<?= $u['id'] ?>" class="btn-kd btn-kd-primary" style="padding:4px 10px; font-size:11px;" data-confirm="Approve this user?"><i class="fa-solid fa-check"></i></a>
                                <?php endif; ?>
                                <?php if ($u['status'] !== 'suspended' && $u['role'] !== 'admin'): ?>
                                <a href="?suspend=<?= $u['id'] ?>" class="btn-kd btn-kd-gold" style="padding:4px 10px; font-size:11px; color:#fff;" data-confirm="Suspend this user?"><i class="fa-solid fa-ban"></i></a>
                                <?php endif; ?>
                                <?php if ($u['role'] !== 'admin'): ?>
                                <a href="?delete=<?= $u['id'] ?>" class="btn-kd btn-kd-danger" style="padding:4px 10px; font-size:11px;" data-confirm="DELETE this user? This cannot be undone."><i class="fa-solid fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">No users found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
