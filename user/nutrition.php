<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth();

$crops   = $pdo->query("SELECT id,name FROM CROP ORDER BY name")->fetchAll();
$vitamins= $pdo->query("SELECT * FROM VITAMIN ORDER BY name")->fetchAll();
$methods = $pdo->query("SELECT * FROM COOKING_METHOD ORDER BY name")->fetchAll();
$results = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    $crop_id   = (int)($_POST['crop_id'] ?? 0);
    $method_id = (int)($_POST['method_id'] ?? 0);
    if ($crop_id) {
        $where = "WHERE nr.review_status='reviewed' AND nr.crop_id=?"; $params = [$crop_id];
        if ($method_id) { $where .= " AND nr.method_id=?"; $params[] = $method_id; }
        $stmt = $pdo->prepare("SELECT nr.*, v.name as vitamin_name, v.unit, cm.name as method_name, cv.amount_per_100g as raw_amount FROM NUTRIENT_RETENTION nr JOIN VITAMIN v ON nr.vitamin_id=v.id JOIN COOKING_METHOD cm ON nr.method_id=cm.id LEFT JOIN CROP_VITAMIN cv ON cv.crop_id=nr.crop_id AND cv.vitamin_id=nr.vitamin_id AND cv.review_status='reviewed' $where ORDER BY v.name, cm.name");
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Also get vitamin profile for the crop
        $vprofile = $pdo->prepare("SELECT cv.amount_per_100g, v.name, v.unit FROM CROP_VITAMIN cv JOIN VITAMIN v ON cv.vitamin_id=v.id WHERE cv.review_status='reviewed' AND cv.crop_id=? ORDER BY cv.amount_per_100g DESC");
        $vprofile->execute([$crop_id]);
        $vprofile = $vprofile->fetchAll();
    }
}

$page_title = 'Nutrition Guide';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><h1 class="topbar-title"><i class="fa-solid fa-apple-whole me-2" style="color:#a4420d;"></i>Nutrition Guide</h1></div></div>
    <div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><aside class="alert-kd alert-kd-info">Only reviewed nutrition references are shown. Values depend on edible portion, raw/cooked basis, cooking time, water and variety. Missing data is not zero. These are not individualized dietary recommendations.</aside>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-flask me-2" style="color:#a4420d;"></i>Check Nutritional Retention</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd"><?= csrfField() ?><?= requestKeyField() ?>
                            <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Crop <span style="color:red">*</span></label>
                            <select id="<?= e($fieldId) ?>" name="crop_id" class="form-control"><option value="">Select crop</option>
                            <?php foreach ($crops as $c): ?><option value="<?= e($c['id']) ?>" <?= ($_POST['crop_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
                            <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Cooking Method</label>
                            <select id="<?= e($fieldId) ?>" name="method_id" class="form-control"><option value="">All Methods</option>
                            <?php foreach ($methods as $m): ?><option value="<?= e($m['id']) ?>" <?= ($_POST['method_id']??'')==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option><?php endforeach; ?></select></div>
                            <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-chart-bar"></i> Analyze</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <?php if ($searched && !empty($vprofile)): ?>
                <div class="card-kd mb-4">
                    <div class="card-header-kd"><h5>Raw Vitamin Content (per 100g)</h5></div>
                    <div class="card-body-kd">
                        <div style="display:flex;flex-wrap:wrap;gap:10px;">
                            <?php foreach ($vprofile as $v): ?>
                            <div style="background:var(--surface3);border-radius:10px;padding:12px 16px;text-align:center;min-width:100px;">
                                <div style="font-size:20px;font-weight:800;color:var(--primary);font-family:'Nunito',sans-serif;"><?= e($v['amount_per_100g']) ?></div>
                                <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($v['unit']) ?></div>
                                <div style="font-size:12px;font-weight:600;margin-top:4px;"><?= htmlspecialchars($v['name']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($searched && !empty($results)): ?>
                <div class="card-kd">
                    <div class="card-header-kd"><h5>Retention by Cooking Method</h5></div>
                    <div class="card-body-kd">
                        <?php $grouped=[]; foreach ($results as $r) $grouped[$r['vitamin_name']][]=$r; foreach ($grouped as $vname => $items): ?>
                        <div style="margin-bottom:20px;">
                            <div style="font-weight:700;color:var(--primary-dark);margin-bottom:8px;"><?= htmlspecialchars($vname) ?></div>
                            <?php foreach ($items as $item): ?><p class="small">Source: <a href="<?= e($item['source_url']) ?>" target="_blank" rel="noopener">Reference</a> · Reviewed <?= e($item['reviewed_at']) ?><br><?= e($item['preparation_notes'] ?? '') ?></p>
                            <div style="margin-bottom:8px;">
                                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                                    <span><?= htmlspecialchars($item['method_name']) ?></span>
                                    <strong style="color:<?= $item['retention_percentage']>=80?'var(--primary)':($item['retention_percentage']>=60?'var(--gold)':'var(--danger)') ?>;"><?= e($item['retention_percentage']) ?>%</strong>
                                </div>
                                <div style="height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                                    <div title="<?= e($item['source_url']) ?> — reviewed <?= e($item['reviewed_at']) ?>" class="retention-bar" data-pct="<?= e($item['retention_percentage']) ?>" style="height:100%;width:0%;border-radius:4px;background:<?= $item['retention_percentage']>=80?'linear-gradient(90deg,var(--primary),var(--primary-light))':($item['retention_percentage']>=60?'linear-gradient(90deg,var(--gold),var(--gold-light))':'linear-gradient(90deg,var(--danger),#ff6b6b)') ?>;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php elseif ($searched): ?>
                <div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;"><i aria-hidden="true" class="fa-solid fa-leaf"></i></div><h4>Select a crop above to see its nutrition data.</h4></div></div>
                <?php else: ?>
                <div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:72px;"><i aria-hidden="true" class="fa-solid fa-leaf"></i></div><h4 style="color:var(--primary-dark);">Your Nutrition Intelligence Tool</h4><p style="color:var(--text-muted);">Select a crop to see vitamin content and how different cooking methods affect nutrient retention.</p></div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
