<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['cook']);
$cook = $pdo->prepare("SELECT * FROM COOK WHERE user_id=?"); $cook->execute([$_SESSION['user_id']]); $ck = $cook->fetch(); $cid = $ck['id'] ?? 0;
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? '');
    $prep  = (int)($_POST['prep_time_min'] ?? 0); $cook_time = (int)($_POST['cook_time_min'] ?? 0);
    $serv  = (int)($_POST['servings'] ?? 2); $auth = isset($_POST['is_authentic']) ? 1 : 0;
    if ($name) {
        $pdo->prepare("INSERT INTO RECIPE (cook_id,name,description,prep_time_min,cook_time_min,servings,is_authentic) VALUES (?,?,?,?,?,?,?)")->execute([$cid,$name,$desc,$prep,$cook_time,$serv,$auth]);
        $msg = 'Recipe added!';
    } else { $err = 'Recipe name is required.'; }
}

$recipes = $pdo->prepare("SELECT * FROM RECIPE WHERE cook_id=? ORDER BY created_at DESC"); $recipes->execute([$cid]); $recipes = $recipes->fetchAll();
$page_title = 'My Recipes';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><i class="fa-solid fa-book-open me-2" style="color:#ea580c;"></i>My Recipes</div></div></div>
    <div class="page-body">
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-plus me-2" style="color:#ea580c;"></i>Add Recipe</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate>
                            <div class="form-group"><label>Recipe Name <span style="color:red">*</span></label><input type="text" name="name" class="form-control" required placeholder="e.g. Shorshe Ilish"></div>
                            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3" placeholder="Describe the dish..."></textarea></div>
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                                <div class="form-group"><label>Prep (min)</label><input type="number" name="prep_time_min" class="form-control" value="15" min="0"></div>
                                <div class="form-group"><label>Cook (min)</label><input type="number" name="cook_time_min" class="form-control" value="30" min="0"></div>
                                <div class="form-group"><label>Serves</label><input type="number" name="servings" class="form-control" value="2" min="1"></div>
                            </div>
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                                    <input type="checkbox" name="is_authentic" style="width:16px;height:16px;"> Mark as Authentic
                                </label>
                            </div>
                            <button type="submit" class="btn-kd w-100 justify-content-center" style="background:linear-gradient(135deg,#ea580c,#f97316);color:#fff;padding:12px;"><i class="fa-solid fa-plus"></i> Add Recipe</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    <?php foreach ($recipes as $r): ?>
                    <div class="col-md-6">
                        <div class="card-kd">
                            <div class="card-body-kd">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                                    <h5 style="margin:0;font-size:15px;"><?= htmlspecialchars($r['name']) ?></h5>
                                    <?php if ($r['is_authentic']): ?><span class="badge-kd badge-success" style="font-size:10px;">⭐ Authentic</span><?php endif; ?>
                                </div>
                                <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;line-height:1.5;"><?= mb_substr(htmlspecialchars($r['description']),0,100) ?>...</p>
                                <div style="display:flex;gap:10px;font-size:11px;color:var(--text-muted);">
                                    <span><i class="fa-solid fa-clock"></i> <?= $r['prep_time_min'] ?>+<?= $r['cook_time_min'] ?>m</span>
                                    <span><i class="fa-solid fa-users"></i> Serves <?= $r['servings'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recipes)): ?><div class="col-12"><div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;">🍛</div><p style="color:var(--text-muted);">No recipes yet.</p></div></div></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
