<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['admin']);

// Handle approve/suspend/delete actions
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
    header('Location: users.php');exit;
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
$users=pagedRows($pdo,$stmt->queryString,$params,'users','Platform accounts',['name', 'email'],true);

$messages = ['approved'=>'User approved.','suspended'=>'User suspended.','deleted'=>'User deleted.'];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';
$page_title = 'Manage Users';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">

            <h1 class="topbar-title">Manage Users</h1>
        </div>
    </div>
    <div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><?= renderFlash() ?>
        <?php if ($msg): ?>
        <div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> <?= $msg ?></div>
        <?php endif; ?>

        <!-- Filter bar -->
        <form class="filter-bar" method="GET">
            <input type="text" name="q" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
            <select name="role">
                <option value="">All Roles</option>
                <?php foreach (['admin','farmer','dealer','tourist','cook','expert','guide','general'] as $r): ?>
                <option value="<?= $r ?>" <?= $role_filter===$r?'selected':'' ?>><?= e(ucfirst($r)) ?></option>
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
                        <td style="color:var(--text-muted); font-size:12px;"><?= e($u['id']) ?></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                        </td>
                        <td><span class="badge-kd badge-info"><?= e(ucfirst($u['role'])) ?></span></td>
                        <td style="font-size:13px;"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                        <td>
                            <?php $cls = ['approved'=>'badge-success','pending'=>'badge-warning','suspended'=>'badge-danger']; ?>
                            <span class="badge-kd <?= $cls[$u['status']] ?? 'badge-muted' ?>"><?= e(ucfirst($u['status'])) ?></span>
                        </td>
                        <td style="font-size:12px; color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <?php if ($u['status'] !== 'approved'): ?>
                                <form method="POST" action="" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="approve" value="<?= e($u['id']) ?>"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Review notes</label><input id="<?= e($fieldId) ?>" class="form-control" name="review_notes" maxlength="3000" required><label><input type="checkbox" name="review_ack" value="1" required> Identity/qualification checked</label><button type="submit" class="btn-kd btn-kd-primary" style="padding:4px 10px; font-size:11px;" data-confirm="Approve this user?"><i class="fa-solid fa-check"></i></button></form>
                                <?php endif; ?>
                                <?php if ($u['status'] !== 'suspended' && $u['role'] !== 'admin'): ?>
                                <form method="POST" action="" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="suspend" value="<?= e($u['id']) ?>"><button type="submit" class="btn-kd btn-kd-gold" style="padding:4px 10px; font-size:11px; color:#fff;" data-confirm="Suspend this user?"><i class="fa-solid fa-ban"></i></button></form>
                                <?php endif; ?>
                                <?php if ($u['role'] !== 'admin'): ?>
                                <form method="POST" action="" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="delete" value="<?= e($u['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger" style="padding:4px 10px; font-size:11px;" data-confirm="DELETE this user? This cannot be undone."><i class="fa-solid fa-trash"></i></button></form>
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
