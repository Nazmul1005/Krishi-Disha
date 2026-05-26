<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth();

$section = $_GET['section'] ?? 'crop';
$msg = ''; $err = '';

function handleUploadSuggest($field, $subfolder = 'crops') {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $f   = $_FILES[$field];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) return null;
    $dir = __DIR__ . '/../assets/images/uploads/' . $subfolder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fname = uniqid('sugg_', true) . '.' . $ext;
    if (move_uploaded_file($f['tmp_name'], $dir . $fname)) {
        return 'assets/images/uploads/' . $subfolder . '/' . $fname;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? 'crop';
    $uid = $_SESSION['user_id'];
    $data = $_POST;
    $title = '';

    if ($section === 'crop') {
        $title = trim($_POST['name'] ?? 'New Crop');
        $img = handleUploadSuggest('crop_image', 'crops');
        if ($img) $data['image'] = $img;
        unset($data['crop_image']);
    } elseif ($section === 'disease') {
        $title = trim($_POST['name'] ?? 'New Disease');
        $img = handleUploadSuggest('disease_image', 'diseases');
        if ($img) $data['image'] = $img;
        unset($data['disease_image']);
    } else {
        $title = trim($_POST['title'] ?? 'New Suggestion');
    }

    if ($title) {
        $pdo->prepare("INSERT INTO DATA_PROPOSAL (user_id, section, action, title, proposed_data) VALUES (?, ?, 'create', ?, ?)")
            ->execute([$uid, $section, $title, json_encode($data)]);
        $msg = 'Your suggestion has been submitted for admin review. Thank you!';
    } else {
        $err = 'Please fill in all required fields.';
    }
}

$crops   = $pdo->query("SELECT id, name FROM CROP ORDER BY name")->fetchAll();
$vitamins = $pdo->query("SELECT id, name, unit FROM VITAMIN ORDER BY name")->fetchAll();
$methods  = $pdo->query("SELECT id, name FROM COOKING_METHOD ORDER BY name")->fetchAll();
$regions  = $pdo->query("SELECT DISTINCT region FROM REGION_CROP ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Suggest Content';
$isAuth = isLoggedIn();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if ($isAuth): ?><div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content"><?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><?php endif; ?>
        <div class="topbar-title"><i class="fa-solid fa-lightbulb me-2" style="color:var(--gold);"></i>Suggest Content</div>
    </div>
    <div class="topbar-actions">
        <span class="badge-kd badge-warning"><i class="fa-solid fa-clock me-1"></i>Requires Admin Approval</span>
    </div>
</div>

<div class="page-body">
<?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="6000"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err):  ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="row g-4">
    <!-- Section Picker -->
    <div class="col-lg-3">
        <div class="card-kd">
            <div class="card-header-kd"><h5><i class="fa-solid fa-list-check me-2" style="color:var(--primary);"></i>What to Suggest</h5></div>
            <div class="card-body-kd" style="padding:8px;">
                <?php
                $sections = [
                    'crop'       => ['fa-seedling',      'New Crop',          'Suggest adding a new crop to the Encyclopedia'],
                    'disease'    => ['fa-bug',            'New Disease',       'Suggest a crop disease or pest'],
                    'recommender'=> ['fa-map-pin',        'Region Suitability','Suggest a crop-region-soil suitability entry'],
                    'nutrition'  => ['fa-flask',          'Nutrient Retention','Suggest nutrient data for a crop+method'],
                ];
                foreach ($sections as $key => [$icon, $label, $desc]): $active = $section === $key;
                ?>
                <a href="?section=<?= $key ?>" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;text-decoration:none;margin-bottom:4px;
                    background:<?= $active ? 'linear-gradient(135deg,var(--primary),var(--primary-light))' : 'transparent' ?>;
                    color:<?= $active ? '#fff' : 'var(--text)' ?>;">
                    <i class="fa-solid <?= $icon ?>" style="width:16px;"></i>
                    <div>
                        <div style="font-weight:700;font-size:13px;"><?= $label ?></div>
                        <div style="font-size:11px;opacity:0.75;"><?= $desc ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card-kd mt-3">
            <div class="card-body-kd">
                <div style="text-align:center;padding:12px 0;">
                    <div style="font-size:40px;margin-bottom:8px;">🔍</div>
                    <div style="font-weight:700;font-size:14px;color:var(--primary-dark);">Review Process</div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:8px;line-height:1.6;">
                        Your suggestions are reviewed by our admin team. Approved entries are immediately added to the platform.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="col-lg-9">
        <div class="card-kd">
            <?php if ($section === 'crop'): ?>
            <div class="card-header-kd" style="background:linear-gradient(135deg,#d8f3dc,#b7e4c7);">
                <h5><i class="fa-solid fa-seedling me-2" style="color:#2d6a4f;"></i>Suggest a New Crop</h5>
            </div>
            <div class="card-body-kd">
                <form method="POST" enctype="multipart/form-data" class="form-kd" data-validate>
                    <input type="hidden" name="section" value="crop">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="form-group"><label>Crop Name <span style="color:red">*</span></label><input type="text" name="name" class="form-control" required placeholder="e.g. Garlic"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Scientific Name</label><input type="text" name="scientific_name" class="form-control" placeholder="e.g. Allium sativum"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Local Name (বাংলা)</label><input type="text" name="local_name" class="form-control" placeholder="e.g. রসুন"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Origin</label><input type="text" name="origin" class="form-control" placeholder="e.g. Central Asia"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Category</label>
                            <input type="text" name="category" class="form-control" list="catlist" placeholder="e.g. Vegetable">
                            <datalist id="catlist"><option value="Grain"><option value="Vegetable"><option value="Fruit"><option value="Legume"><option value="Oilseed"><option value="Fiber"><option value="Cash Crop"></datalist>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><label>Season</label>
                            <select name="season" class="form-control">
                                <?php foreach (['summer','winter','rainy','all'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><label>Trade Status</label>
                            <select name="trade_status" class="form-control">
                                <option value="local">Local Only</option><option value="export">Export</option><option value="both">Both</option>
                            </select>
                        </div></div>
                        <div class="col-12"><div class="form-group"><label>History / Description <span style="color:red">*</span></label><textarea name="history" class="form-control" rows="4" required placeholder="Write about the crop's history, cultivation in Bangladesh, uses..."></textarea></div></div>
                        <div class="col-12"><div class="form-group">
                            <label>Crop Photo</label>
                            <input type="file" name="crop_image" class="form-control" accept="image/*">
                            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">JPG, PNG or WebP — max 5MB</div>
                        </div></div>
                    </div>
                    <button type="submit" class="btn-kd btn-kd-primary mt-2 justify-content-center" style="min-width:200px;">
                        <i class="fa-solid fa-paper-plane"></i> Submit for Review
                    </button>
                </form>
            </div>

            <?php elseif ($section === 'disease'): ?>
            <div class="card-header-kd" style="background:linear-gradient(135deg,#fee2e2,#fca5a5);">
                <h5><i class="fa-solid fa-bug me-2" style="color:#dc2626;"></i>Suggest a New Disease / Pest</h5>
            </div>
            <div class="card-body-kd">
                <form method="POST" enctype="multipart/form-data" class="form-kd" data-validate>
                    <input type="hidden" name="section" value="disease">
                    <div class="row g-3">
                        <div class="col-md-8"><div class="form-group"><label>Disease / Pest Name <span style="color:red">*</span></label><input type="text" name="name" class="form-control" required placeholder="e.g. Wheat Stem Fly"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Affected Plant Part</label><input type="text" name="affected_part" class="form-control" placeholder="e.g. Leaves, Stems"></div></div>
                        <div class="col-12"><div class="form-group"><label>Symptoms <span style="color:red">*</span></label><textarea name="symptoms" class="form-control" rows="3" required placeholder="Describe the visible symptoms..."></textarea></div></div>
                        <div class="col-12"><div class="form-group"><label>Solution / Treatment <span style="color:red">*</span></label><textarea name="solution" class="form-control" rows="3" required placeholder="Organic or chemical control methods..."></textarea></div></div>
                        <div class="col-12"><div class="form-group">
                            <label>Affected Crops (comma-separated)</label>
                            <input type="text" name="crop_names" class="form-control" placeholder="e.g. Wheat, Barley, Rice">
                        </div></div>
                        <div class="col-12"><div class="form-group">
                            <label>Disease Photo</label>
                            <input type="file" name="disease_image" class="form-control" accept="image/*">
                        </div></div>
                    </div>
                    <button type="submit" class="btn-kd btn-kd-danger mt-2 justify-content-center" style="min-width:200px;">
                        <i class="fa-solid fa-paper-plane"></i> Submit for Review
                    </button>
                </form>
            </div>

            <?php elseif ($section === 'recommender'): ?>
            <div class="card-header-kd" style="background:linear-gradient(135deg,#d1fae5,#6ee7b7);">
                <h5><i class="fa-solid fa-map-pin me-2" style="color:#059669;"></i>Suggest Region Suitability Data</h5>
            </div>
            <div class="card-body-kd">
                <form method="POST" class="form-kd" data-validate>
                    <input type="hidden" name="section" value="recommender">
                    <input type="hidden" name="title" value="Region Suitability Suggestion">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="form-group"><label>Crop <span style="color:red">*</span></label>
                            <select name="crop_id" class="form-control" required>
                                <option value="">Select crop</option>
                                <?php foreach ($crops as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-6"><div class="form-group"><label>Region / District <span style="color:red">*</span></label><input type="text" name="region" class="form-control" list="reglist" required placeholder="e.g. Khulna"><datalist id="reglist"><?php foreach ($regions as $r): ?><option value="<?= $r ?>"><?php endforeach; ?></datalist></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Soil Type</label><input type="text" name="soil_type" class="form-control" placeholder="e.g. Loamy"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Season</label>
                            <select name="season" class="form-control">
                                <?php foreach (['summer','winter','rainy','all'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><label>Suitability Score (1-10)</label><input type="number" name="suitability_score" min="1" max="10" class="form-control" value="7"></div></div>
                        <div class="col-12"><div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Additional context..."></textarea></div></div>
                    </div>
                    <button type="submit" class="btn-kd btn-kd-primary mt-2 justify-content-center" style="min-width:200px;background:linear-gradient(135deg,#059669,#34d399);">
                        <i class="fa-solid fa-paper-plane"></i> Submit for Review
                    </button>
                </form>
            </div>

            <?php elseif ($section === 'nutrition'): ?>
            <div class="card-header-kd" style="background:linear-gradient(135deg,#ffedd5,#fed7aa);">
                <h5><i class="fa-solid fa-flask me-2" style="color:#ea580c;"></i>Suggest Nutrient Retention Data</h5>
            </div>
            <div class="card-body-kd">
                <form method="POST" class="form-kd" data-validate>
                    <input type="hidden" name="section" value="nutrition">
                    <input type="hidden" name="title" value="Nutrient Retention Suggestion">
                    <div class="row g-3">
                        <div class="col-md-4"><div class="form-group"><label>Crop <span style="color:red">*</span></label>
                            <select name="crop_id" class="form-control" required>
                                <option value="">Select crop</option>
                                <?php foreach ($crops as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><label>Vitamin / Nutrient <span style="color:red">*</span></label>
                            <select name="vitamin_id" class="form-control" required>
                                <option value="">Select vitamin</option>
                                <?php foreach ($vitamins as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?> (<?= $v['unit'] ?>)</option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><label>Cooking Method <span style="color:red">*</span></label>
                            <select name="method_id" class="form-control" required>
                                <option value="">Select method</option>
                                <?php foreach ($methods as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-6"><div class="form-group"><label>Retention % (0-100) <span style="color:red">*</span></label><input type="number" name="retention_percentage" min="0" max="100" step="0.1" class="form-control" required placeholder="e.g. 75.5"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Source / Reference</label><input type="text" name="source" class="form-control" placeholder="e.g. USDA, research paper"></div></div>
                    </div>
                    <button type="submit" class="btn-kd btn-kd-primary mt-2 justify-content-center" style="min-width:200px;background:linear-gradient(135deg,#ea580c,#fb923c);">
                        <i class="fa-solid fa-paper-plane"></i> Submit for Review
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php if ($isAuth): ?></div></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
