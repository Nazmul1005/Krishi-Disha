<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$results = [];
$searched = false;
$mode = $_POST['mode'] ?? 'region';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    if ($mode === 'region') {
        $region = trim($_POST['region'] ?? '');
        $soil   = trim($_POST['soil'] ?? '');
        $season = trim($_POST['season'] ?? '');
        $where  = ['1=1']; $params = [];
        if ($region) { $where[] = "rc.region LIKE ?"; $params[] = "%$region%"; }
        if ($soil)   { $where[] = "rc.soil_type LIKE ?"; $params[] = "%$soil%"; }
        if ($season) { $where[] = "rc.season IN (?,  'all')"; $params[] = $season; }
        $stmt = $pdo->prepare("SELECT c.*, rc.region, rc.soil_type, rc.season, rc.suitability_score, rc.notes FROM REGION_CROP rc JOIN CROP c ON rc.crop_id=c.id WHERE " . implode(' AND ',$where) . " ORDER BY rc.suitability_score DESC");
        $stmt->execute($params);
        $results = $stmt->fetchAll();
    } else {
        // Vitamin-based recommendation
        $vitamin_id = (int)($_POST['vitamin_id'] ?? 0);
        $min_amount = (float)($_POST['min_amount'] ?? 0);
        $stmt = $pdo->prepare("SELECT c.*, cv.amount_per_100g, v.name as vitamin_name, v.unit FROM CROP_VITAMIN cv JOIN CROP c ON cv.crop_id=c.id JOIN VITAMIN v ON cv.vitamin_id=v.id WHERE cv.vitamin_id=? AND cv.amount_per_100g >= ? ORDER BY cv.amount_per_100g DESC");
        $stmt->execute([$vitamin_id, $min_amount]);
        $results = $stmt->fetchAll();
    }
}

$vitamins = $pdo->query("SELECT * FROM VITAMIN ORDER BY name")->fetchAll();
$regions  = $pdo->query("SELECT DISTINCT region FROM REGION_CROP ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$soils    = $pdo->query("SELECT DISTINCT soil_type FROM REGION_CROP ORDER BY soil_type")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Crop Recommender';
$isAuth = isLoggedIn();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if ($isAuth): ?><div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content">
<?php else: ?><nav class="kd-navbar"><div class="container"><div class="d-flex align-items-center justify-content-between"><a href="/KrishiDisha/index.php" class="navbar-brand" style="display:flex;align-items:center;gap:10px;font-family:'Nunito',sans-serif;font-size:20px;font-weight:800;color:#fff;"><div style="width:34px;height:34px;background:linear-gradient(135deg,#52b788,#2d6a4f);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-leaf" style="color:#fff;font-size:15px;"></i></div>KrishiDisha</a><a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-primary" style="padding:8px 18px;font-size:13px;">Login</a></div></div></nav><div style="padding-top:70px;"><?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button><?php endif; ?>
        <div class="topbar-title"><i class="fa-solid fa-seedling me-2" style="color:var(--primary);"></i>Crop Recommender</div>
    </div>
</div>

<div class="page-body">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-kd">
                <div class="card-header-kd"><h5><i class="fa-solid fa-sliders me-2" style="color:var(--primary);"></i>Find Best Crops</h5></div>
                <div class="card-body-kd">
                    <!-- Mode tabs -->
                    <div style="display:flex; background:var(--surface3); border-radius:8px; padding:4px; margin-bottom:20px;">
                        <button type="button" id="btnRegion" onclick="setMode('region')" style="flex:1;padding:8px;border:none;border-radius:6px;font-family:'Nunito',sans-serif;font-weight:700;font-size:13px;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;">By Region</button>
                        <button type="button" id="btnVitamin" onclick="setMode('vitamin')" style="flex:1;padding:8px;border:none;border-radius:6px;font-family:'Nunito',sans-serif;font-weight:700;font-size:13px;cursor:pointer;background:transparent;color:var(--text-muted);">By Vitamin</button>
                    </div>

                    <form method="POST" class="form-kd" data-validate>
                        <input type="hidden" name="mode" id="modeInput" value="<?= htmlspecialchars($mode) ?>">

                        <!-- Region mode -->
                        <div id="regionFields">
                            <div class="form-group">
                                <label>Region / District</label>
                                <select name="region" class="form-control">
                                    <option value="">Any Region</option>
                                    <?php foreach ($regions as $r): ?><option value="<?= $r ?>" <?= ($_POST['region']??'')===$r?'selected':'' ?>><?= $r ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Soil Type</label>
                                <select name="soil" class="form-control">
                                    <option value="">Any Soil</option>
                                    <?php foreach ($soils as $s): ?><option value="<?= $s ?>" <?= ($_POST['soil']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Season</label>
                                <select name="season" class="form-control">
                                    <option value="">Any Season</option>
                                    <?php foreach (['summer','winter','rainy','all'] as $s): ?><option value="<?= $s ?>" <?= ($_POST['season']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Vitamin mode -->
                        <div id="vitaminFields" style="display:none;">
                            <div class="form-group">
                                <label>Vitamin / Nutrient</label>
                                <select name="vitamin_id" class="form-control">
                                    <option value="">Select Vitamin</option>
                                    <?php foreach ($vitamins as $v): ?><option value="<?= $v['id'] ?>" <?= ($_POST['vitamin_id']??'')==$v['id']?'selected':'' ?>><?= $v['name'] ?> (<?= $v['unit'] ?>)</option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Minimum Amount per 100g</label>
                                <input type="number" name="min_amount" step="0.01" class="form-control" value="<?= htmlspecialchars($_POST['min_amount'] ?? '0') ?>">
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
            <div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;">🌱</div><h4 style="color:var(--text-muted);">No matching crops found</h4><p style="color:var(--text-muted);">Try adjusting your criteria.</p></div></div>
            <?php else: ?>
            <div style="font-family:'Nunito',sans-serif;font-weight:700;font-size:16px;color:var(--primary-dark);margin-bottom:16px;">
                <i class="fa-solid fa-check-circle" style="color:var(--primary);"></i> <?= count($results) ?> recommended crop<?= count($results)>1?'s':'' ?> found
            </div>
            <div class="row g-3">
                <?php foreach ($results as $r): ?>
                <div class="col-md-6">
                    <div class="card-kd" style="border-left:4px solid var(--primary-light);">
                        <div class="card-body-kd">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                                <div style="font-size:36px;"><?php $icons=['Grain'=>'🌾','Vegetable'=>'🥕','Fruit'=>'🍎','Fiber'=>'🪢','Oilseed'=>'🌻','Legume'=>'🫘','Cash Crop'=>'💰']; echo $icons[$r['category']]??'🌱'; ?></div>
                                <div>
                                    <div style="font-weight:800;font-family:'Nunito',sans-serif;font-size:16px;"><?= htmlspecialchars($r['name']) ?></div>
                                    <div style="font-size:12px;font-style:italic;color:var(--text-muted);"><?= htmlspecialchars($r['scientific_name']??'') ?></div>
                                </div>
                            </div>
                            <?php if ($mode === 'region'): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;">
                                <span class="badge-kd badge-success" style="font-size:10px;"><?= $r['region'] ?></span>
                                <span class="badge-kd badge-info" style="font-size:10px;"><?= $r['soil_type'] ?></span>
                                <span class="badge-kd badge-muted" style="font-size:10px;"><?= ucfirst($r['season']) ?></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:10px;background:#e5e7eb;border-radius:5px;overflow:hidden;">
                                    <div style="width:<?= ($r['suitability_score']/10*100) ?>%;height:100%;background:linear-gradient(90deg,var(--primary),var(--primary-light));border-radius:5px;"></div>
                                </div>
                                <span style="font-weight:800;color:var(--primary);font-size:13px;"><?= $r['suitability_score'] ?>/10</span>
                            </div>
                            <?php if ($r['notes']): ?><p style="font-size:12px;color:var(--text-muted);margin-top:8px;margin-bottom:0;"><?= htmlspecialchars($r['notes']) ?></p><?php endif; ?>
                            <?php else: ?>
                            <div class="badge-kd badge-success" style="font-size:12px;">
                                <?= $r['vitamin_name'] ?>: <strong><?= $r['amount_per_100g'] ?> <?= $r['unit'] ?></strong> / 100g
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
                <div style="font-size:72px;margin-bottom:16px;">🌾</div>
                <h4 style="color:var(--primary-dark);">Intelligent Crop Recommendation</h4>
                <p style="color:var(--text-muted);">Fill in the form to get personalized crop suggestions based on your region, soil type, and season — or find crops rich in specific vitamins.</p>
            </div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isAuth): ?></div></div><?php else: ?></div><?php endif; ?>
<script>
function setMode(m) {
    document.getElementById('modeInput').value = m;
    document.getElementById('regionFields').style.display  = m==='region'  ? 'block' : 'none';
    document.getElementById('vitaminFields').style.display = m==='vitamin' ? 'block' : 'none';
    document.getElementById('btnRegion').style.background  = m==='region'  ? 'linear-gradient(135deg,var(--primary),var(--primary-light))' : 'transparent';
    document.getElementById('btnRegion').style.color       = m==='region'  ? '#fff' : 'var(--text-muted)';
    document.getElementById('btnVitamin').style.background = m==='vitamin' ? 'linear-gradient(135deg,var(--primary),var(--primary-light))' : 'transparent';
    document.getElementById('btnVitamin').style.color      = m==='vitamin' ? '#fff' : 'var(--text-muted)';
}
<?php if ($mode==='vitamin'): ?>setMode('vitamin');<?php endif; ?>
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
