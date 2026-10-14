<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$crops   = $pdo->query("SELECT id,name FROM CROP ORDER BY name")->fetchAll();
$vitamins= $pdo->query("SELECT * FROM VITAMIN ORDER BY name")->fetchAll();
$methods = $pdo->query("SELECT * FROM COOKING_METHOD ORDER BY name")->fetchAll();

$results = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    $crop_id  = (int)($_POST['crop_id'] ?? 0);
    $method_id= (int)($_POST['method_id'] ?? 0);
    if ($crop_id) {
        $where = "WHERE nr.review_status='reviewed' AND nr.crop_id=?"; $params = [$crop_id];
        if ($method_id) { $where .= " AND nr.method_id=?"; $params[] = $method_id; }
        $stmt = $pdo->prepare("SELECT nr.*, v.name as vitamin_name, v.unit, cm.name as method_name, cv.amount_per_100g as raw_amount FROM NUTRIENT_RETENTION nr JOIN VITAMIN v ON nr.vitamin_id=v.id JOIN COOKING_METHOD cm ON nr.method_id=cm.id LEFT JOIN CROP_VITAMIN cv ON cv.crop_id=nr.crop_id AND cv.vitamin_id=nr.vitamin_id AND cv.review_status='reviewed' $where ORDER BY v.name, cm.name");
        $stmt->execute($params);
        $results = $stmt->fetchAll();
    }
}

$recipes = $pdo->query("SELECT r.*, u.name as cook_name FROM RECIPE r JOIN COOK c ON r.cook_id=c.id JOIN USER u ON c.user_id=u.id WHERE u.status='approved' AND u.email_verified=1 ORDER BY r.is_authentic DESC, r.created_at DESC LIMIT 6")->fetchAll();

$page_title = 'Nutrition & Cooking';
$isAuth = isLoggedIn();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if ($isAuth): ?><div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content" id="main-content" tabindex="-1">
<?php else: ?><div style=""><?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><?php endif; ?>
        <h1 class="topbar-title"><i class="fa-solid fa-apple-whole me-2" style="color:#a4420d;"></i>Nutrition & Authentic Food</h1>
    </div>
</div>

<div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><aside class="alert-kd alert-kd-info">Only reviewed nutrition references are shown. Values depend on edible portion, raw/cooked basis, cooking time, water and variety. Missing data is not zero. These are not individualized dietary recommendations.</aside>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-kd">
                <div class="card-header-kd"><h5><i class="fa-solid fa-flask me-2" style="color:#a4420d;"></i>Nutrient Retention Checker</h5></div>
                <div class="card-body-kd">
                    <p style="font-size:13px;color:var(--text-muted);margin-bottom:18px;">See how different cooking methods affect vitamin content in crops.</p>
                    <form method="POST" class="form-kd"><?= csrfField() ?><?= requestKeyField() ?>
                        <div class="form-group">
                            <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Select Crop <span style="color:red">*</span></label>
                            <select id="<?= e($fieldId) ?>" name="crop_id" class="form-control" required>
                                <option value="">Choose a crop</option>
                                <?php foreach ($crops as $c): ?><option value="<?= e($c['id']) ?>" <?= ($_POST['crop_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Cooking Method (optional)</label>
                            <select id="<?= e($fieldId) ?>" name="method_id" class="form-control">
                                <option value="">All Methods</option>
                                <?php foreach ($methods as $m): ?><option value="<?= e($m['id']) ?>" <?= ($_POST['method_id']??'')==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center">
                            <i class="fa-solid fa-chart-bar"></i> Check Retention
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($searched && !empty($results)): ?>
            <div class="card-kd mt-4">
                <div class="card-header-kd"><h5>Retention Results</h5></div>
                <div class="card-body-kd">
                    <?php
                    $grouped = [];
                    foreach ($results as $r) { $grouped[$r['vitamin_name']][] = $r; }
                    foreach ($grouped as $vname => $items):
                    ?>
                    <div style="margin-bottom:20px;">
                        <div style="font-weight:700;color:var(--primary-dark);font-size:14px;margin-bottom:10px;">
                            <?= htmlspecialchars($vname) ?> (Raw: <?= $items[0]['raw_amount'] ?? 'N/A' ?> <?= e($items[0]['unit']) ?>)
                        </div>
                        <?php foreach ($items as $item): ?><p class="small">Source: <a href="<?= e($item['source_url']) ?>" target="_blank" rel="noopener">Reference</a> · Reviewed <?= e($item['reviewed_at']) ?><br><?= e($item['preparation_notes'] ?? '') ?></p>
                        <div style="margin-bottom:8px;">
                            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                                <span><?= htmlspecialchars($item['method_name']) ?></span>
                                <strong style="color:<?= $item['retention_percentage'] >= 80 ? 'var(--primary)' : ($item['retention_percentage'] >= 60 ? 'var(--gold)' : 'var(--danger)') ?>;"><?= e($item['retention_percentage']) ?>%</strong>
                            </div>
                            <div style="height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                                <div title="<?= e($item['source_url']) ?> — reviewed <?= e($item['reviewed_at']) ?>" class="retention-bar" data-pct="<?= e($item['retention_percentage']) ?>" style="height:100%;width:0%;border-radius:4px;background:<?= $item['retention_percentage'] >= 80 ? 'linear-gradient(90deg,var(--primary),var(--primary-light))' : ($item['retention_percentage'] >= 60 ? 'linear-gradient(90deg,var(--gold),var(--gold-light))' : 'linear-gradient(90deg,var(--danger),#ff6b6b)') ?>;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif ($searched): ?>
            <div class="card-kd mt-4"><div class="card-body-kd text-center py-4"><p style="color:var(--text-muted);">No nutrient retention data for this selection.</p></div></div>
            <?php endif; ?>
        </div>

        <div class="col-lg-7">
            <div class="card-kd">
                <div class="card-header-kd">
                    <h5><i class="fa-solid fa-utensils me-2" style="color:#a4420d;"></i>Authentic Recipes</h5>
                </div>
                <div class="card-body-kd">
                    <div class="row g-3">
                        <?php foreach ($recipes as $r): ?>
                        <div class="col-md-6">
                            <div style="background:var(--surface3);border-radius:var(--radius);padding:16px;border:1px solid var(--border);">
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                                    <div style="font-size:32px;">🍛</div>
                                    <div>
                                        <div style="font-weight:700;font-size:14px;"><?= htmlspecialchars($r['name']) ?></div>
                                        <div style="font-size:11px;color:var(--text-muted);">by <?= htmlspecialchars($r['cook_name']) ?></div>
                                    </div>
                                    <?php if ($r['is_authentic']): ?><span class="badge-kd badge-success" style="font-size:10px;margin-left:auto;">Authentic</span><?php endif; ?>
                                </div>
                                <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;line-height:1.5;"><?= mb_substr(htmlspecialchars($r['description']),0,100) ?>...</p>
                                <div style="display:flex;gap:8px;font-size:11px;color:var(--text-muted);">
                                    <span><i class="fa-solid fa-clock"></i> Prep: <?= e($r['prep_time_min']) ?>m</span>
                                    <span><i class="fa-solid fa-fire"></i> Cook: <?= e($r['cook_time_min']) ?>m</span>
                                    <span><i class="fa-solid fa-users"></i> Serves <?= e($r['servings']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($recipes)): ?><div class="col-12"><p class="text-center" style="color:var(--text-muted);">No recipes available yet.</p></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isAuth): ?></div></div><?php else: ?></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
