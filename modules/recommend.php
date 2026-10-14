<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__.'/../includes/content.php';

$results = [];
$searched = false;
$mode = $_POST['mode'] ?? 'region';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    if ($mode === 'region') {
        $region = trim($_POST['region'] ?? '');
        $soil   = trim($_POST['soil'] ?? '');
        $season = trim($_POST['season'] ?? '');
        $where  = ["rc.review_status='reviewed' AND c.review_status='reviewed'"]; $params = [];
        if ($region) { $where[] = "rc.region LIKE ?"; $params[] = "%$region%"; }
        if ($soil)   { $where[] = "rc.soil_type LIKE ?"; $params[] = "%$soil%"; }
        if ($season) { $where[] = "rc.season IN (?,  'all')"; $params[] = $season; }
        $stmt = $pdo->prepare("SELECT c.*,rc.source_url AS suitability_source,rc.reviewed_at AS suitability_reviewed_at, rc.region, rc.soil_type, rc.season, rc.suitability_score, rc.notes FROM REGION_CROP rc JOIN CROP c ON rc.crop_id=c.id WHERE " . implode(' AND ',$where) . " ORDER BY rc.suitability_score DESC");
        $stmt->execute($params);
        $results = $stmt->fetchAll();
    } else {
        // Vitamin-based recommendation
        $vitamin_id = (int)($_POST['vitamin_id'] ?? 0);
        $min_amount = (float)($_POST['min_amount'] ?? 0);
        $stmt = $pdo->prepare("SELECT c.*, cv.amount_per_100g, v.name as vitamin_name, v.unit FROM CROP_VITAMIN cv JOIN CROP c ON cv.crop_id=c.id JOIN VITAMIN v ON cv.vitamin_id=v.id WHERE cv.review_status='reviewed' AND cv.vitamin_id=? AND cv.amount_per_100g >= ? ORDER BY cv.amount_per_100g DESC");
        $stmt->execute([$vitamin_id, $min_amount]);
        $results = $stmt->fetchAll();
    }
}

$vitamins = $pdo->query("SELECT * FROM VITAMIN ORDER BY name")->fetchAll();
$regions  = $pdo->query("SELECT DISTINCT region FROM REGION_CROP ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$soils    = $pdo->query("SELECT DISTINCT soil_type FROM REGION_CROP ORDER BY soil_type")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Crop Reference Matches';
$isAuth = isLoggedIn();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if ($isAuth): ?><div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content" id="main-content" tabindex="-1">
<?php else: ?><div style=""><?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><?php endif; ?>
        <h1 class="topbar-title"><i class="fa-solid fa-seedling me-2" style="color:var(--primary);"></i>Crop Reference Matches</h1>
    </div>
</div>

<div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><?= knowledgeNotice() ?><p>Scores are reviewed reference assessments, not a prediction of farm performance. Soil pH, salinity, irrigation, flood risk, variety and planting dates still need local advice. <a href="https://www.bamis.gov.bd/en/bulletin/district/" target="_blank" rel="noopener">Check BAMIS district weather advisories</a>.</p>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-kd">
                <div class="card-header-kd"><h5><i class="fa-solid fa-sliders me-2" style="color:var(--primary);"></i>Find Best Crops</h5></div>
                <div class="card-body-kd">
                    <!-- Mode tabs -->
                    <div style="display:flex; background:var(--surface3); border-radius:8px; padding:4px; margin-bottom:20px;">
                        <button type="button" id="btnRegion" data-mode="region" style="flex:1;padding:8px;border:none;border-radius:6px;font-family:'Nunito',sans-serif;font-weight:700;font-size:13px;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">By Region</button>
                        <button type="button" id="btnVitamin" data-mode="vitamin" style="flex:1;padding:8px;border:none;border-radius:6px;font-family:'Nunito',sans-serif;font-weight:700;font-size:13px;cursor:pointer;background:transparent;color:var(--text-muted);">By Vitamin</button>
                    </div>

                    <form method="POST" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                        <input type="hidden" name="mode" id="modeInput" value="<?= htmlspecialchars($mode) ?>">

                        <!-- Region mode -->
                        <div id="regionFields">
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Region / District</label>
                                <select id="<?= e($fieldId) ?>" name="region" class="form-control">
                                    <option value="">Any Region</option>
                                    <?php foreach ($regions as $r): ?><option value="<?= $r ?>" <?= ($_POST['region']??'')===$r?'selected':'' ?>><?= $r ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Soil Type</label>
                                <select id="<?= e($fieldId) ?>" name="soil" class="form-control">
                                    <option value="">Any Soil</option>
                                    <?php foreach ($soils as $s): ?><option value="<?= $s ?>" <?= ($_POST['soil']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Season</label>
                                <select id="<?= e($fieldId) ?>" name="season" class="form-control">
                                    <option value="">Any Season</option>
                                    <?php foreach (['summer','winter','rainy','all'] as $s): ?><option value="<?= $s ?>" <?= ($_POST['season']??'')===$s?'selected':'' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Vitamin mode -->
                        <div id="vitaminFields" style="display:none;">
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Vitamin / Nutrient</label>
                                <select id="<?= e($fieldId) ?>" name="vitamin_id" class="form-control">
                                    <option value="">Select Vitamin</option>
                                    <?php foreach ($vitamins as $v): ?><option value="<?= e($v['id']) ?>" <?= ($_POST['vitamin_id']??'')==$v['id']?'selected':'' ?>><?= e($v['name']) ?> (<?= e($v['unit']) ?>)</option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Minimum Amount per 100g</label>
                                <input id="<?= e($fieldId) ?>" type="number" name="min_amount" step="0.01" class="form-control" value="<?= htmlspecialchars($_POST['min_amount'] ?? '0') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                            <i class="fa-solid fa-magnifying-glass"></i> Get Recommendations
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if ($searched): ?>
            <?php if (empty($results)): ?>
            <div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;"><i aria-hidden="true" class="fa-solid fa-seedling"></i></div><h4 style="color:var(--text-muted);">No matching crops found</h4><p style="color:var(--text-muted);">Try adjusting your criteria.</p></div></div>
            <?php else: ?>
            <div style="font-family:'Nunito',sans-serif;font-weight:700;font-size:16px;color:var(--primary-dark);margin-bottom:16px;">
                <i class="fa-solid fa-check-circle" style="color:var(--primary);"></i> <?= count($results) ?> reference match<?= count($results)>1?'s':'' ?> found
            </div>
            <div class="row g-3">
                <?php foreach ($results as $r): ?>
                <div class="col-md-6">
                    <div class="card-kd" style="border-left:4px solid var(--primary-light);">
                        <div class="card-body-kd">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                                <div style="font-size:36px;"><?php $icons=['Grain'=>'<i aria-hidden="true" class="fa-solid fa-wheat-awn"></i>','Vegetable'=>'<i aria-hidden="true" class="fa-solid fa-carrot"></i>','Fruit'=>'<i aria-hidden="true" class="fa-solid fa-apple-whole"></i>','Fiber'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Oilseed'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Legume'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Cash Crop'=>'<i aria-hidden="true" class="fa-solid fa-coins"></i>']; echo $icons[$r['category']]??'<i aria-hidden="true" class="fa-solid fa-seedling"></i>'; ?></div>
                                <div>
                                    <div style="font-weight:800;font-family:'Nunito',sans-serif;font-size:16px;"><?= htmlspecialchars($r['name']) ?></div>
                                    <div style="font-size:12px;font-style:italic;color:var(--text-muted);"><?= htmlspecialchars($r['scientific_name']??'') ?></div>
                                </div>
                            </div>
                            <?php if ($mode === 'region'): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;">
                                <span class="badge-kd badge-success" style="font-size:10px;"><?= e($r['region']) ?></span>
                                <span class="badge-kd badge-info" style="font-size:10px;"><?= e($r['soil_type']) ?></span>
                                <span class="badge-kd badge-muted" style="font-size:10px;"><?= e(ucfirst($r['season'])) ?></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:10px;background:#e5e7eb;border-radius:5px;overflow:hidden;">
                                    <div style="width:<?= ($r['suitability_score']/10*100) ?>%;height:100%;background:linear-gradient(90deg,var(--primary),var(--primary-light));border-radius:5px;"></div>
                                </div>
                                <span style="font-weight:800;color:var(--primary);font-size:13px;"><?= e($r['suitability_score']) ?>/10</span>
                            </div>
                            <p class="small">Source: <a href="<?= e($r['suitability_source']) ?>" target="_blank" rel="noopener">Reference</a> · Reviewed <?= e($r['suitability_reviewed_at']) ?></p><?php if ($r['notes']): ?><p style="font-size:12px;color:var(--text-muted);margin-top:8px;margin-bottom:0;"><?= htmlspecialchars($r['notes']) ?></p><?php endif; ?>
                            <?php else: ?>
                            <div class="badge-kd badge-success" style="font-size:12px;">
                                <?= e($r['vitamin_name']) ?>: <strong><?= e($r['amount_per_100g']) ?> <?= e($r['unit']) ?></strong> / 100g
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="card-kd"><div class="card-body-kd text-center py-5">
                <div style="font-size:72px;margin-bottom:16px;"><i aria-hidden="true" class="fa-solid fa-wheat-awn"></i></div>
                <h4 style="color:var(--primary-dark);">Reviewed crop reference matches</h4>
                <p style="color:var(--text-muted);">Fill in the form to get personalized crop suggestions based on your region, soil type, and season — or find crops rich in specific vitamins.</p>
            </div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isAuth): ?></div></div><?php else: ?></div><?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
