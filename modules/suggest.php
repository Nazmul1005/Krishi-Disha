<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth();

$section = $_GET['section'] ?? 'crop';
$msg = ''; $err = '';

function handleUploadSuggest($field,$subfolder="crops") { return saveImage($field,false); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? 'crop';
    $uid = $_SESSION['user_id'];
    $allowed=['name','scientific_name','local_name','origin','history','trade_status','season','category','symptoms','solution','affected_part','crop_names','crop_id','region','soil_type','suitability_score','vitamin_id','method_id','retention_percentage','preparation_notes','source_url'];
    $data=array_intersect_key($_POST,array_flip($allowed));
    throttle($pdo,'proposal-user',(string)$uid,10,3600);
    $title = '';

    if ($section === 'crop') {
        $title = trim($_POST['name'] ?? 'New Crop');
        $img = handleUploadSuggest('crop_image', 'crops');
        if ($img) $data['image'] = $img;

    } elseif ($section === 'disease') {
        $title = trim($_POST['name'] ?? 'New Disease');
        $img = handleUploadSuggest('disease_image', 'diseases');
        if ($img) $data['image'] = $img;

    } else {
        $title = trim($_POST['title'] ?? 'New Suggestion');
    }

    if (!in_array($section,['crop','disease','recommender','nutrition'],true)) { http_response_code(400);die('Invalid suggestion type.'); }
    if ($title && strlen(json_encode($data)) <= 30000) {
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
<?php if ($isAuth): ?><div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content" id="main-content" tabindex="-1"><?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><?php endif; ?>
        <h1 class="topbar-title"><i class="fa-solid fa-lightbulb me-2" style="color:var(--gold);"></i>Suggest Content</h1>
    </div>
    <div class="topbar-actions">
        <span class="badge-kd badge-warning"><i class="fa-solid fa-clock me-1"></i>Requires Admin Approval</span>
    </div>
</div>

<div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?>
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
                    <div style="font-size:40px;margin-bottom:8px;"><i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i></div>
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
                <form method="POST" enctype="multipart/form-data" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                    <input type="hidden" name="section" value="crop">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Crop Name <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="text" name="name" class="form-control" required placeholder="e.g. Garlic"></div></div>
                        <div class="col-md-6"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Scientific Name</label><input id="<?= e($fieldId) ?>" type="text" name="scientific_name" class="form-control" placeholder="e.g. Allium sativum"></div></div>
                        <div class="col-md-6"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Local Name (বাংলা)</label><input id="<?= e($fieldId) ?>" type="text" name="local_name" class="form-control" placeholder="e.g. রসুন"></div></div>
                        <div class="col-md-6"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Origin</label><input id="<?= e($fieldId) ?>" type="text" name="origin" class="form-control" placeholder="e.g. Central Asia"></div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Category</label>
                            <input id="<?= e($fieldId) ?>" type="text" name="category" class="form-control" list="catlist" placeholder="e.g. Vegetable">
                            <datalist id="catlist"><option value="Grain"><option value="Vegetable"><option value="Fruit"><option value="Legume"><option value="Oilseed"><option value="Fiber"><option value="Cash Crop"></datalist>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Season</label>
                            <select id="<?= e($fieldId) ?>" name="season" class="form-control">
                                <?php foreach (['summer','winter','rainy','all'] as $s): ?><option value="<?= $s ?>"><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Trade Status</label>
                            <select id="<?= e($fieldId) ?>" name="trade_status" class="form-control">
                                <option value="local">Local Only</option><option value="export">Export</option><option value="both">Both</option>
                            </select>
                        </div></div>
                        <div class="col-12"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">History / Description <span style="color:red">*</span></label><textarea id="<?= e($fieldId) ?>" name="history" class="form-control" rows="4" required placeholder="Write about the crop's history, cultivation in Bangladesh, uses..."></textarea></div></div>
                        <div class="col-12"><div class="form-group">
                            <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Crop Photo</label>
                            <input id="<?= e($fieldId) ?>" type="file" name="crop_image" class="form-control" accept="image/*">
                            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">JPG, PNG or WebP — max 5MB</div>
                        </div></div>
                    </div>
                    <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Reference source URL (required for publication)</label><input id="<?= e($fieldId) ?>" class="form-control" type="url" name="source_url" maxlength="500" required><button type="submit" class="btn-kd btn-kd-primary mt-2 justify-content-center" style="min-width:200px;">
                        <i class="fa-solid fa-paper-plane"></i> Submit for Review
                    </button>
                </form>
            </div>

            <?php elseif ($section === 'disease'): ?>
            <div class="card-header-kd" style="background:linear-gradient(135deg,#fee2e2,#fca5a5);">
                <h5><i class="fa-solid fa-bug me-2" style="color:#dc2626;"></i>Suggest a New Disease / Pest</h5>
            </div>
            <div class="card-body-kd">
                <form method="POST" enctype="multipart/form-data" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                    <input type="hidden" name="section" value="disease">
                    <div class="row g-3">
                        <div class="col-md-8"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Disease / Pest Name <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="text" name="name" class="form-control" required placeholder="e.g. Wheat Stem Fly"></div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Affected Plant Part</label><input id="<?= e($fieldId) ?>" type="text" name="affected_part" class="form-control" placeholder="e.g. Leaves, Stems"></div></div>
                        <div class="col-12"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Symptoms <span style="color:red">*</span></label><textarea id="<?= e($fieldId) ?>" name="symptoms" class="form-control" rows="3" required placeholder="Describe the visible symptoms..."></textarea></div></div>
                        <div class="col-12"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Solution / Treatment <span style="color:red">*</span></label><textarea id="<?= e($fieldId) ?>" name="solution" class="form-control" rows="3" required placeholder="Organic or chemical control methods..."></textarea></div></div>
                        <div class="col-12"><div class="form-group">
                            <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Affected Crops (comma-separated)</label>
                            <input id="<?= e($fieldId) ?>" type="text" name="crop_names" class="form-control" placeholder="e.g. Wheat, Barley, Rice">
                        </div></div>
                        <div class="col-12"><div class="form-group">
                            <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Disease Photo</label>
                            <input id="<?= e($fieldId) ?>" type="file" name="disease_image" class="form-control" accept="image/*">
                        </div></div>
                    </div>
                    <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Reference source URL (required for publication)</label><input id="<?= e($fieldId) ?>" class="form-control" type="url" name="source_url" maxlength="500" required><button type="submit" class="btn-kd btn-kd-danger mt-2 justify-content-center" style="min-width:200px;">
                        <i class="fa-solid fa-paper-plane"></i> Submit for Review
                    </button>
                </form>
            </div>

            <?php elseif ($section === 'recommender'): ?>
            <div class="card-header-kd" style="background:linear-gradient(135deg,#d1fae5,#6ee7b7);">
                <h5><i class="fa-solid fa-map-pin me-2" style="color:#059669;"></i>Suggest Region Suitability Data</h5>
            </div>
            <div class="card-body-kd">
                <form method="POST" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                    <input type="hidden" name="section" value="recommender">
                    <input type="hidden" name="title" value="Region Suitability Suggestion">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Crop <span style="color:red">*</span></label>
                            <select id="<?= e($fieldId) ?>" name="crop_id" class="form-control" required>
                                <option value="">Select crop</option>
                                <?php foreach ($crops as $c): ?><option value="<?= e($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-6"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Region / District <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="text" name="region" class="form-control" list="reglist" required placeholder="e.g. Khulna"><datalist id="reglist"><?php foreach ($regions as $r): ?><option value="<?= $r ?>"><?php endforeach; ?></datalist></div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Soil Type</label><input id="<?= e($fieldId) ?>" type="text" name="soil_type" class="form-control" placeholder="e.g. Loamy"></div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Season</label>
                            <select id="<?= e($fieldId) ?>" name="season" class="form-control">
                                <?php foreach (['summer','winter','rainy','all'] as $s): ?><option value="<?= $s ?>"><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Suitability Score (1-10)</label><input id="<?= e($fieldId) ?>" type="number" name="suitability_score" min="1" max="10" class="form-control" value="7"></div></div>
                        <div class="col-12"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Notes</label><textarea id="<?= e($fieldId) ?>" name="notes" class="form-control" rows="3" placeholder="Additional context..."></textarea></div></div>
                    </div>
                    <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Reference source URL (required for publication)</label><input id="<?= e($fieldId) ?>" class="form-control" type="url" name="source_url" maxlength="500" required><button type="submit" class="btn-kd btn-kd-primary mt-2 justify-content-center" style="min-width:200px;background:linear-gradient(135deg,#059669,#34d399);">
                        <i class="fa-solid fa-paper-plane"></i> Submit for Review
                    </button>
                </form>
            </div>

            <?php elseif ($section === 'nutrition'): ?>
            <div class="card-header-kd" style="background:linear-gradient(135deg,#ffedd5,#fed7aa);">
                <h5><i class="fa-solid fa-flask me-2" style="color:#a4420d;"></i>Suggest Nutrient Retention Data</h5>
            </div>
            <div class="card-body-kd">
                <form method="POST" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                    <input type="hidden" name="section" value="nutrition"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Preparation conditions, time, water and source basis</label><textarea id="<?= e($fieldId) ?>" class="form-control" name="preparation_notes" maxlength="3000" required></textarea>
                    <input type="hidden" name="title" value="Nutrient Retention Suggestion">
                    <div class="row g-3">
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Crop <span style="color:red">*</span></label>
                            <select id="<?= e($fieldId) ?>" name="crop_id" class="form-control" required>
                                <option value="">Select crop</option>
                                <?php foreach ($crops as $c): ?><option value="<?= e($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Vitamin / Nutrient <span style="color:red">*</span></label>
                            <select id="<?= e($fieldId) ?>" name="vitamin_id" class="form-control" required>
                                <option value="">Select vitamin</option>
                                <?php foreach ($vitamins as $v): ?><option value="<?= e($v['id']) ?>"><?= htmlspecialchars($v['name']) ?> (<?= e($v['unit']) ?>)</option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-4"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Cooking Method <span style="color:red">*</span></label>
                            <select id="<?= e($fieldId) ?>" name="method_id" class="form-control" required>
                                <option value="">Select method</option>
                                <?php foreach ($methods as $m): ?><option value="<?= e($m['id']) ?>"><?= htmlspecialchars($m['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div></div>
                        <div class="col-md-6"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Retention % (0-100) <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="number" name="retention_percentage" min="0" max="100" step="0.1" class="form-control" required placeholder="e.g. 75.5"></div></div>
                        <div class="col-md-6"><div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Source / Reference</label><input id="<?= e($fieldId) ?>" type="text" name="source" class="form-control" placeholder="e.g. USDA, research paper"></div></div>
                    </div>
                    <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Reference source URL (required for publication)</label><input id="<?= e($fieldId) ?>" class="form-control" type="url" name="source_url" maxlength="500" required><button type="submit" class="btn-kd btn-kd-primary mt-2 justify-content-center" style="min-width:200px;background:linear-gradient(135deg,#a4420d,#fb923c);">
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
