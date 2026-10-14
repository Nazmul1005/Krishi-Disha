<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['admin']);
require_once __DIR__.'/../includes/content.php';
foreach(['add_crop','edit_crop','add_disease','edit_disease'] as $editAction) if(isset($_POST[$editAction])) {
    $source=sourceUrl($_POST['source_url'] ?? '');
    if(($_POST['review_ack'] ?? '')!=='1') throw new InvalidArgumentException('Confirm you reviewed this reference.');
    validateProposal(str_contains($editAction,'crop')?'crop':'disease',$_POST);
}
function markReviewed(PDO $pdo,string $table,int $id): void {
    $pdo->prepare("UPDATE `$table` SET source_url=?,review_status='reviewed',reviewed_by=?,reviewed_at=NOW() WHERE id=?")->execute([sourceUrl($_POST['source_url'] ?? ''),$_SESSION['user_id'],$id]);
    audit($pdo,'content.reviewed',$table,$id);
}


$tab = $_GET['tab'] ?? 'crops';
$msg = ''; $err = '';

// ─── Upload helper ─────────────────────────────────────────────────────────
function handleUpload($field,$subfolder="crops") { return saveImage($field,true); }

// ══════════════════════════════════════════════════════════════════════════════
// CROPS TAB CRUD
// ══════════════════════════════════════════════════════════════════════════════
if ($tab === 'crops') {
    // Add crop
    if (isset($_POST['add_crop'])) {
        $img = handleUpload('crop_image', 'crops');
        $pdo->prepare("INSERT INTO CROP (name,scientific_name,local_name,origin,history,trade_status,season,category,image)
                       VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([
                trim($_POST['name']), trim($_POST['scientific_name']), trim($_POST['local_name']),
                trim($_POST['origin']), trim($_POST['history']),
                $_POST['trade_status'], $_POST['season'], trim($_POST['category']),
                $img ?? 'assets/images/uploads/crops/default.jpg'
            ]);
        markReviewed($pdo,'CROP',(int)$pdo->lastInsertId());
        $msg = 'Crop added successfully!';
    }
    // Edit crop
    if (isset($_POST['edit_crop'])) {
        $id  = (int)$_POST['crop_id'];
        $cur = $pdo->prepare("SELECT image FROM CROP WHERE id=?"); $cur->execute([$id]); $cur=$cur->fetch();
        $img = handleUpload('crop_image', 'crops') ?? ($cur['image'] ?? '');
        $pdo->prepare("UPDATE CROP SET name=?,scientific_name=?,local_name=?,origin=?,history=?,trade_status=?,season=?,category=?,image=? WHERE id=?")
            ->execute([
                trim($_POST['name']), trim($_POST['scientific_name']), trim($_POST['local_name']),
                trim($_POST['origin']), trim($_POST['history']),
                $_POST['trade_status'], $_POST['season'], trim($_POST['category']), $img, $id
            ]);
        markReviewed($pdo,'CROP',$id);
        $msg = 'Crop updated!';
    }
    // Delete crop
    if (isset($_POST['delete_crop'])) {
        $pdo->prepare("DELETE FROM CROP WHERE id=?")->execute([(int)$_POST['delete_crop']]);
        header('Location: manage_content.php?tab=crops&msg=deleted'); exit;
    }
    if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $msg = 'Crop deleted.';
    $crops=pagedRows($pdo,"SELECT * FROM CROP ORDER BY name",[],'crops','Crop entries',['name', 'local_name'],false);
    $edit_crop = null;
    if (isset($_GET['edit'])) {
        $ec = $pdo->prepare("SELECT * FROM CROP WHERE id=?"); $ec->execute([(int)$_GET['edit']]); $edit_crop = $ec->fetch();
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// DISEASES TAB CRUD
// ══════════════════════════════════════════════════════════════════════════════
if ($tab === 'diseases') {
    if (isset($_POST['add_disease'])) {
        $img = handleUpload('disease_image', 'diseases');
        $pdo->prepare("INSERT INTO DISEASE (name,symptoms,solution,affected_part,image) VALUES (?,?,?,?,?)")
            ->execute([trim($_POST['name']), trim($_POST['symptoms']), trim($_POST['solution']), trim($_POST['affected_part']), $img]);
        $newDisease=(int)$pdo->lastInsertId();
        markReviewed($pdo,'DISEASE',$newDisease);
        $msg = 'Disease added!';
        // Link to crop if provided
        if (!empty($_POST['crop_ids'])) {
            $did = $newDisease;
            foreach ((array)$_POST['crop_ids'] as $cid) {
                $pdo->prepare("INSERT IGNORE INTO CROP_DISEASE (crop_id,disease_id) VALUES (?,?)")->execute([(int)$cid, $did]);
            }
        }
    }
    if (isset($_POST['edit_disease'])) {
        $id  = (int)$_POST['disease_id'];
        $cur = $pdo->prepare("SELECT image FROM DISEASE WHERE id=?"); $cur->execute([$id]); $cur=$cur->fetch();
        $img = handleUpload('disease_image', 'diseases') ?? ($cur['image'] ?? '');
        $pdo->prepare("UPDATE DISEASE SET name=?,symptoms=?,solution=?,affected_part=?,image=? WHERE id=?")
            ->execute([trim($_POST['name']), trim($_POST['symptoms']), trim($_POST['solution']), trim($_POST['affected_part']), $img, $id]);
        $pdo->prepare("DELETE FROM CROP_DISEASE WHERE disease_id=?")->execute([$id]);
        if (!empty($_POST['crop_ids'])) {
            foreach ((array)$_POST['crop_ids'] as $cid) {
                $pdo->prepare("INSERT IGNORE INTO CROP_DISEASE (crop_id,disease_id) VALUES (?,?)")->execute([(int)$cid, $id]);
            }
        }
        markReviewed($pdo,'DISEASE',$id);
        $msg = 'Disease updated!';
    }
    if (isset($_POST['delete_disease'])) {
        $pdo->prepare("DELETE FROM DISEASE WHERE id=?")->execute([(int)$_POST['delete_disease']]);
        header('Location: manage_content.php?tab=diseases&msg=deleted'); exit;
    }
    if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $msg = 'Disease deleted.';
    $diseases=pagedRows($pdo,"SELECT d.*, GROUP_CONCAT(c.name SEPARATOR ', ') as crops FROM DISEASE d LEFT JOIN CROP_DISEASE cd ON d.id=cd.disease_id LEFT JOIN CROP c ON cd.crop_id=c.id GROUP BY d.id ORDER BY d.name",[],'diseases','Disease entries',['name', 'crops'],false);
    $all_crops = $pdo->query("SELECT id,name FROM CROP ORDER BY name")->fetchAll();
    $edit_disease = null;
    if (isset($_GET['edit'])) {
        $ed = $pdo->prepare("SELECT * FROM DISEASE WHERE id=?"); $ed->execute([(int)$_GET['edit']]); $edit_disease = $ed->fetch();
        $ed_crops = $pdo->prepare("SELECT crop_id FROM CROP_DISEASE WHERE disease_id=?"); $ed_crops->execute([$edit_disease['id']]); $edit_disease['crop_ids'] = $ed_crops->fetchAll(PDO::FETCH_COLUMN);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// FARM TOURS TAB CRUD
// ══════════════════════════════════════════════════════════════════════════════
if ($tab === 'tours') {
    if (isset($_POST['add_tour'])) {
        $img = handleUpload('tour_image', 'tours');
        $fid = (int)$_POST['farmer_id'];
        $pdo->prepare("INSERT INTO FARM_TOUR (farmer_id,title,description,location,capacity,price_per_day,image,status) VALUES (?,?,?,?,?,?,?,'active')")
            ->execute([$fid, trim($_POST['title']), trim($_POST['description']), trim($_POST['location']), (int)$_POST['capacity'], (float)$_POST['price_per_day'], $img]);
        $msg = 'Farm tour added!';
    }
    if (isset($_POST['edit_tour'])) {
        $id  = (int)$_POST['tour_id'];
        $cur = $pdo->prepare("SELECT image FROM FARM_TOUR WHERE id=?"); $cur->execute([$id]); $cur=$cur->fetch();
        $img = handleUpload('tour_image', 'tours') ?? ($cur['image'] ?? '');
        $pdo->prepare("UPDATE FARM_TOUR SET title=?,description=?,location=?,capacity=?,price_per_day=?,image=?,status=? WHERE id=?")
            ->execute([trim($_POST['title']), trim($_POST['description']), trim($_POST['location']), (int)$_POST['capacity'], (float)$_POST['price_per_day'], $img, $_POST['status'], $id]);
        $msg = 'Farm tour updated!';
    }
    if (isset($_POST['delete_tour'])) {
        $pdo->prepare("UPDATE FARM_TOUR SET status='inactive' WHERE id=?")->execute([(int)$_POST['delete_tour']]);
        header('Location: manage_content.php?tab=tours&msg=deleted'); exit;
    }
    if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $msg = 'Tour deleted.';
    $tours=pagedRows($pdo,"SELECT ft.*,u.name as farmer_name FROM FARM_TOUR ft JOIN FARMER f ON ft.farmer_id=f.id JOIN USER u ON f.user_id=u.id ORDER BY ft.created_at DESC",[],'tours','Farm tours',['title', 'farmer_name'],true);
    $farmers  = $pdo->query("SELECT f.id, u.name FROM FARMER f JOIN USER u ON f.user_id=u.id ORDER BY u.name")->fetchAll();
    $edit_tour= null;
    if (isset($_GET['edit'])) {
        $et = $pdo->prepare("SELECT * FROM FARM_TOUR WHERE id=?"); $et->execute([(int)$_GET['edit']]); $edit_tour=$et->fetch();
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// MARKETPLACE TAB CRUD
// ══════════════════════════════════════════════════════════════════════════════
if ($tab === 'marketplace') {
    if (isset($_POST['edit_product'])) {
        $id  = (int)$_POST['product_id'];
        $cur = $pdo->prepare("SELECT image FROM PRODUCT WHERE id=?"); $cur->execute([$id]); $cur=$cur->fetch();
        $img = handleUpload('product_image', 'products') ?? ($cur['image'] ?? '');
        $pdo->prepare("UPDATE PRODUCT SET quantity_kg=?,price_per_kg=?,description=?,status=?,image=? WHERE id=?")
            ->execute([(float)$_POST['quantity_kg'], (float)$_POST['price_per_kg'], trim($_POST['description']), $_POST['status'], $img, $id]);
        $msg = 'Product updated!';
    }
    if (isset($_POST['approve_product'])) {
        $pdo->prepare("UPDATE PRODUCT SET status='available' WHERE id=?")->execute([(int)$_POST['approve_product']]);
        header('Location: manage_content.php?tab=marketplace&msg=approved'); exit;
    }
    if (isset($_POST['delete_product'])) {
        $pdo->prepare("UPDATE PRODUCT SET status='pending' WHERE id=?")->execute([(int)$_POST['delete_product']]);
        header('Location: manage_content.php?tab=marketplace&msg=deleted'); exit;
    }
    if (isset($_GET['msg'])) { $mmap=['approved'=>'Product approved!','deleted'=>'Product deleted.']; $msg=$mmap[$_GET['msg']]??''; }
    $products=pagedRows($pdo,"SELECT p.*,c.name as crop_name,c.category,u.name as farmer_name FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id JOIN FARMER f ON p.farmer_id=f.id JOIN USER u ON f.user_id=u.id ORDER BY p.created_at DESC",[],'products','Produce listings',['crop_name', 'farmer_name'],true);
    $edit_product=null;
    if (isset($_GET['edit'])) {
        $ep=$pdo->prepare("SELECT p.*,c.name as crop_name FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id WHERE p.id=?"); $ep->execute([(int)$_GET['edit']]); $edit_product=$ep->fetch();
    }
}

$page_title = 'Manage Content';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">

<div class="topbar">
    <div class="d-flex align-items-center gap-3">

        <h1 class="topbar-title"><i class="fa-solid fa-pen-to-square me-2" style="color:var(--primary);"></i>Manage Content</h1>
    </div>
    <div class="topbar-actions">
        <a href="/KrishiDisha/admin/proposals.php" class="btn-kd btn-kd-outline" style="font-size:12px;padding:6px 14px;">
            <i class="fa-solid fa-inbox"></i> Proposals
        </a>
    </div>
</div>

<div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?>
<?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="4000"><i class="fa-solid fa-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Tab Nav -->
<div style="display:flex;gap:6px;margin-bottom:24px;background:var(--surface3);padding:6px;border-radius:12px;flex-wrap:wrap;">
    <?php
    $tabs = [
        'crops'       => ['fa-seedling',      'Crops'],
        'diseases'    => ['fa-bug',            'Diseases'],
        'tours'       => ['fa-umbrella-beach', 'Farm Tours'],
        'marketplace' => ['fa-store',          'Marketplace'],
    ];
    foreach ($tabs as $key => [$icon, $label]):
        $active = $tab === $key;
    ?>
    <a href="?tab=<?= $key ?>" style="flex:1;min-width:120px;text-align:center;padding:10px 14px;border-radius:8px;font-family:'Nunito',sans-serif;font-weight:700;font-size:13px;text-decoration:none;
        background:<?= $active ? 'linear-gradient(135deg,var(--primary),var(--primary-light))' : 'transparent' ?>;
        color:<?= $active ? '#fff' : 'var(--text-muted)' ?>;">
        <i class="fa-solid <?= $icon ?> me-1"></i><?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ─────────────────── CROPS ─────────────────── -->
<?php if ($tab === 'crops'): ?>
<div class="row g-4">
    <!-- Form -->
    <div class="col-lg-4">
        <div class="card-kd">
            <div class="card-header-kd">
                <h5><i class="fa-solid <?= $edit_crop ? 'fa-pen' : 'fa-plus' ?> me-2" style="color:var(--primary);"></i><?= $edit_crop ? 'Edit Crop' : 'Add New Crop' ?></h5>
                <?php if ($edit_crop): ?><a href="?tab=crops" class="btn-kd btn-kd-outline" style="padding:4px 10px;font-size:11px;">Cancel</a><?php endif; ?>
            </div>
            <div class="card-body-kd">
                <form method="POST" enctype="multipart/form-data" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                    <?php if ($edit_crop): ?><input type="hidden" name="crop_id" value="<?= e($edit_crop['id']) ?>"><?php endif; ?>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Crop Name <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_crop['name']??'') ?>" required></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Scientific Name</label><input id="<?= e($fieldId) ?>" type="text" name="scientific_name" class="form-control" value="<?= htmlspecialchars($edit_crop['scientific_name']??'') ?>"></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Local Name (বাংলা)</label><input id="<?= e($fieldId) ?>" type="text" name="local_name" class="form-control" value="<?= htmlspecialchars($edit_crop['local_name']??'') ?>"></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Origin</label><input id="<?= e($fieldId) ?>" type="text" name="origin" class="form-control" value="<?= htmlspecialchars($edit_crop['origin']??'') ?>"></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Category <span style="color:red">*</span></label>
                        <input id="<?= e($fieldId) ?>" type="text" name="category" class="form-control" list="catlist" value="<?= htmlspecialchars($edit_crop['category']??'') ?>" required>
                        <datalist id="catlist"><option value="Grain"><option value="Vegetable"><option value="Fruit"><option value="Legume"><option value="Oilseed"><option value="Fiber"><option value="Cash Crop"></datalist>
                    </div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Season</label>
                        <select id="<?= e($fieldId) ?>" name="season" class="form-control">
                            <?php foreach (['summer','winter','rainy','all'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($edit_crop['season']??'')===$s?'selected':'' ?>><?= e(ucfirst($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Trade Status</label>
                        <select id="<?= e($fieldId) ?>" name="trade_status" class="form-control">
                            <?php foreach (['local','export','both'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($edit_crop['trade_status']??'')===$t?'selected':'' ?>><?= e(ucfirst($t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">History / Description</label><textarea id="<?= e($fieldId) ?>" name="history" class="form-control" rows="4"><?= htmlspecialchars($edit_crop['history']??'') ?></textarea></div>
                    <div class="form-group">
                        <label>Crop Photo</label>
                        <?php if (!empty($edit_crop['image']) && mediaAvailable($edit_crop['image'])): ?>
                        <img width="480" height="320" decoding="async" alt="" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($edit_crop['image']) ?>" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
                        <?php endif; ?>
                        <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Reference source URL</label><input id="<?= e($fieldId) ?>" class="form-control" type="url" name="source_url" value="<?= e($edit_crop['source_url'] ?? '') ?>" required><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>"><input type="checkbox" name="review_ack" value="1" required> I reviewed this source and its agricultural accuracy.</label><input id="<?= e($fieldId) ?>" type="file" name="crop_image" class="form-control" accept="image/*">
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">JPG, PNG, WebP accepted</div>
                    </div>
                    <button type="submit" name="<?= $edit_crop ? 'edit_crop' : 'add_crop' ?>" class="btn-kd btn-kd-primary w-100 justify-content-center">
                        <i class="fa-solid <?= $edit_crop ? 'fa-save' : 'fa-plus' ?>"></i> <?= $edit_crop ? 'Save Changes' : 'Add Crop' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="col-lg-8">
        <div class="card-kd">
            <div class="card-header-kd">
                <h5>All Crops (<?= count($crops) ?>)</h5>
                <input type="text" id="cropSearch" placeholder="Filter..." data-filter-table="cropTable" style="padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;outline:none;width:180px;">
            </div>
            <div class="card-body-kd p-0">
                <table class="table-kd" id="cropTable">
                    <thead><tr><th>Photo</th><th>Name</th><th>Category</th><th>Season</th><th>Trade</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($crops as $c): ?>
                    <tr>
                        <td>
                            <?php if (!empty($c['image']) && mediaAvailable($c['image'])): ?>
                            <img width="480" height="320" decoding="async" alt="" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($c['image']) ?>" style="width:48px;height:40px;object-fit:cover;border-radius:6px;">
                            <?php else: ?>
                            <div style="width:48px;height:40px;background:var(--surface3);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px;">
                                <?php $icons=['Grain'=>'<i aria-hidden="true" class="fa-solid fa-wheat-awn"></i>','Vegetable'=>'<i aria-hidden="true" class="fa-solid fa-carrot"></i>','Fruit'=>'<i aria-hidden="true" class="fa-solid fa-apple-whole"></i>','Fiber'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Oilseed'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Legume'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Cash Crop'=>'<i aria-hidden="true" class="fa-solid fa-coins"></i>']; echo $icons[$c['category']]??'<i aria-hidden="true" class="fa-solid fa-seedling"></i>'; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></div>
                            <div style="font-size:11px;color:var(--text-muted);font-style:italic;"><?= htmlspecialchars($c['scientific_name']??'') ?></div>
                        </td>
                        <td><span class="badge-kd badge-info" style="font-size:10px;"><?= e($c['category']) ?></span></td>
                        <td><span class="badge-kd badge-success" style="font-size:10px;"><?= e(ucfirst($c['season'])) ?></span></td>
                        <td><span class="badge-kd badge-muted" style="font-size:10px;"><?= e(ucfirst($c['trade_status'])) ?></span></td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="?tab=crops&edit=<?= e($c['id']) ?>" class="btn-kd btn-kd-outline" style="padding:4px 8px;font-size:11px;"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="?tab=crops" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="delete_crop" value="<?= e($c['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger" style="padding:4px 8px;font-size:11px;" data-confirm="Delete crop '<?= htmlspecialchars($c['name']) ?>'?"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ─────────────────── DISEASES ─────────────────── -->
<?php if ($tab === 'diseases'): ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card-kd">
            <div class="card-header-kd">
                <h5><i class="fa-solid <?= $edit_disease ? 'fa-pen' : 'fa-plus' ?> me-2" style="color:var(--danger);"></i><?= $edit_disease ? 'Edit Disease' : 'Add Disease' ?></h5>
                <?php if ($edit_disease): ?><a href="?tab=diseases" class="btn-kd btn-kd-outline" style="padding:4px 10px;font-size:11px;">Cancel</a><?php endif; ?>
            </div>
            <div class="card-body-kd">
                <form method="POST" enctype="multipart/form-data" class="form-kd"><?= csrfField() ?><?= requestKeyField() ?>
                    <?php if ($edit_disease): ?><input type="hidden" name="disease_id" value="<?= e($edit_disease['id']) ?>"><?php endif; ?>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Disease Name <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_disease['name']??'') ?>" required></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Affected Plant Part</label><input id="<?= e($fieldId) ?>" type="text" name="affected_part" class="form-control" value="<?= htmlspecialchars($edit_disease['affected_part']??'') ?>" placeholder="e.g. Leaves, Stems"></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Symptoms</label><textarea id="<?= e($fieldId) ?>" name="symptoms" class="form-control" rows="3"><?= htmlspecialchars($edit_disease['symptoms']??'') ?></textarea></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Solution / Treatment</label><textarea id="<?= e($fieldId) ?>" name="solution" class="form-control" rows="3"><?= htmlspecialchars($edit_disease['solution']??'') ?></textarea></div>
                    <div class="form-group">
                        <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Affects Crops (multi-select)</label>
                        <select id="<?= e($fieldId) ?>" name="crop_ids[]" class="form-control" multiple size="5">
                            <?php foreach ($all_crops as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= (isset($edit_disease['crop_ids']) && in_array($c['id'], $edit_disease['crop_ids']))?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Hold Ctrl/Cmd to select multiple</div>
                    </div>
                    <div class="form-group">
                        <label>Disease Photo</label>
                        <?php if (!empty($edit_disease['image']) && mediaAvailable($edit_disease['image'])): ?>
                        <img width="480" height="320" decoding="async" alt="" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($edit_disease['image']) ?>" style="width:100%;height:90px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
                        <?php endif; ?>
                        <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Reference source URL</label><input id="<?= e($fieldId) ?>" class="form-control" type="url" name="source_url" value="<?= e($edit_disease['source_url'] ?? '') ?>" required><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>"><input type="checkbox" name="review_ack" value="1" required> I reviewed this treatment with a qualified local expert and current labels.</label><input id="<?= e($fieldId) ?>" type="file" name="disease_image" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" name="<?= $edit_disease ? 'edit_disease' : 'add_disease' ?>" class="btn-kd btn-kd-danger w-100 justify-content-center">
                        <i class="fa-solid <?= $edit_disease ? 'fa-save' : 'fa-plus' ?>"></i> <?= $edit_disease ? 'Save Changes' : 'Add Disease' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card-kd">
            <div class="card-header-kd"><h5>All Diseases (<?= count($diseases) ?>)</h5></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>Photo</th><th>Name</th><th>Affected</th><th>Crops</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($diseases as $d): ?>
                    <tr>
                        <td>
                            <?php if (!empty($d['image']) && mediaAvailable($d['image'])): ?>
                            <img width="480" height="320" decoding="async" alt="" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($d['image']) ?>" style="width:48px;height:40px;object-fit:cover;border-radius:6px;">
                            <?php else: ?>
                            <div style="width:48px;height:40px;background:#fee2e2;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px;"><i aria-hidden="true" class="fa-solid fa-bug"></i></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($d['name']) ?></td>
                        <td><span class="badge-kd badge-danger" style="font-size:10px;"><?= htmlspecialchars($d['affected_part']??'') ?></span></td>
                        <td style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($d['crops']??'—') ?></td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="?tab=diseases&edit=<?= e($d['id']) ?>" class="btn-kd btn-kd-outline" style="padding:4px 8px;font-size:11px;"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="?tab=diseases" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="delete_disease" value="<?= e($d['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger" style="padding:4px 8px;font-size:11px;" data-confirm="Delete this disease?"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ─────────────────── FARM TOURS ─────────────────── -->
<?php if ($tab === 'tours'): ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-kd">
            <div class="card-header-kd">
                <h5><i class="fa-solid <?= $edit_tour ? 'fa-pen' : 'fa-plus' ?> me-2" style="color:#126078;"></i><?= $edit_tour ? 'Edit Tour' : 'Add Farm Tour' ?></h5>
                <?php if ($edit_tour): ?><a href="?tab=tours" class="btn-kd btn-kd-outline" style="padding:4px 10px;font-size:11px;">Cancel</a><?php endif; ?>
            </div>
            <div class="card-body-kd">
                <form method="POST" enctype="multipart/form-data" class="form-kd"><?= csrfField() ?><?= requestKeyField() ?>
                    <?php if ($edit_tour): ?><input type="hidden" name="tour_id" value="<?= e($edit_tour['id']) ?>"><?php endif; ?>
                    <?php if (!$edit_tour): ?>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Farmer <span style="color:red">*</span></label>
                        <select id="<?= e($fieldId) ?>" name="farmer_id" class="form-control" required>
                            <option value="">Select farmer</option>
                            <?php foreach ($farmers as $fm): ?><option value="<?= e($fm['id']) ?>"><?= htmlspecialchars($fm['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Tour Title <span style="color:red">*</span></label><input id="<?= e($fieldId) ?>" type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit_tour['title']??'') ?>" required></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Description</label><textarea id="<?= e($fieldId) ?>" name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_tour['description']??'') ?></textarea></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Location</label><input id="<?= e($fieldId) ?>" type="text" name="location" class="form-control" value="<?= htmlspecialchars($edit_tour['location']??'') ?>"></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Max Capacity</label><input id="<?= e($fieldId) ?>" type="number" name="capacity" class="form-control" value="<?= $edit_tour['capacity']??10 ?>" min="1"></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Group price per day (৳)</label><input id="<?= e($fieldId) ?>" type="number" name="price_per_day" step="50" class="form-control" value="<?= $edit_tour['price_per_day']??'' ?>"></div>
                    <?php if ($edit_tour): ?>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Status</label>
                        <select id="<?= e($fieldId) ?>" name="status" class="form-control">
                            <?php foreach (['active','inactive','pending'] as $st): ?>
                            <option value="<?= $st ?>" <?= ($edit_tour['status']??'')===$st?'selected':'' ?>><?= e(ucfirst($st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Tour Photo</label>
                        <?php if (!empty($edit_tour['image']) && mediaAvailable($edit_tour['image'])): ?>
                        <img width="480" height="320" decoding="async" alt="" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($edit_tour['image']) ?>" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
                        <?php endif; ?>
                        <input type="file" name="tour_image" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" name="<?= $edit_tour ? 'edit_tour' : 'add_tour' ?>" class="btn-kd btn-kd-primary w-100 justify-content-center" style="background:linear-gradient(135deg,#0c4a6e,#126078);">
                        <i class="fa-solid <?= $edit_tour ? 'fa-save' : 'fa-plus' ?>"></i> <?= $edit_tour ? 'Save Changes' : 'Add Tour' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-kd">
            <div class="card-header-kd"><h5>All Farm Tours (<?= count($tours) ?>)</h5></div>
            <div class="card-body-kd p-0">
                <table class="table-kd">
                    <thead><tr><th>Photo</th><th>Title</th><th>Farmer</th><th>Location</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($tours as $t): ?>
                    <tr>
                        <td>
                            <?php if (!empty($t['image']) && mediaAvailable($t['image'])): ?>
                            <img width="480" height="320" decoding="async" alt="" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($t['image']) ?>" style="width:56px;height:42px;object-fit:cover;border-radius:6px;">
                            <?php else: ?>
                            <div style="width:56px;height:42px;background:linear-gradient(135deg,#0c4a6e,#126078);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px;"><i aria-hidden="true" class="fa-solid fa-leaf"></i></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($t['title']) ?></td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($t['farmer_name']) ?></td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($t['location']) ?></td>
                        <td style="font-weight:700;color:var(--primary);">৳<?= number_format($t['price_per_day']) ?></td>
                        <td>
                            <?php $sc=['active'=>'badge-success','inactive'=>'badge-muted','pending'=>'badge-warning']; ?>
                            <span class="badge-kd <?= $sc[$t['status']]??'badge-muted' ?>" style="font-size:10px;"><?= e(ucfirst($t['status'])) ?></span>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="?tab=tours&edit=<?= e($t['id']) ?>" class="btn-kd btn-kd-outline" style="padding:4px 8px;font-size:11px;"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="?tab=tours" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="delete_tour" value="<?= e($t['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger" style="padding:4px 8px;font-size:11px;" data-confirm="Delete this tour?"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ─────────────────── MARKETPLACE ─────────────────── -->
<?php if ($tab === 'marketplace'): ?>
<?php if ($edit_product): ?>
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card-kd">
            <div class="card-header-kd">
                <h5><i class="fa-solid fa-pen me-2" style="color:var(--primary);"></i>Edit Product</h5>
                <a href="?tab=marketplace" class="btn-kd btn-kd-outline" style="padding:4px 10px;font-size:11px;">Cancel</a>
            </div>
            <div class="card-body-kd">
                <form method="POST" enctype="multipart/form-data" class="form-kd"><?= csrfField() ?><?= requestKeyField() ?>
                    <input type="hidden" name="product_id" value="<?= e($edit_product['id']) ?>">
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Crop</label><input id="<?= e($fieldId) ?>" type="text" class="form-control" value="<?= htmlspecialchars($edit_product['crop_name']) ?>" readonly></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Quantity (kg)</label><input id="<?= e($fieldId) ?>" type="number" name="quantity_kg" step="0.01" class="form-control" value="<?= e($edit_product['quantity_kg']) ?>"></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Price per kg (৳)</label><input id="<?= e($fieldId) ?>" type="number" name="price_per_kg" step="0.01" class="form-control" value="<?= e($edit_product['price_per_kg']) ?>"></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Description</label><textarea id="<?= e($fieldId) ?>" name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_product['description']??'') ?></textarea></div>
                    <div class="form-group"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Status</label>
                        <select id="<?= e($fieldId) ?>" name="status" class="form-control">
                            <?php foreach (['available','pending','sold'] as $st): ?>
                            <option value="<?= $st ?>" <?= ($edit_product['status']??'')===$st?'selected':'' ?>><?= e(ucfirst($st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Photo</label>
                        <?php if (!empty($edit_product['image']) && mediaAvailable($edit_product['image'])): ?>
                        <img width="480" height="320" decoding="async" alt="" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($edit_product['image']) ?>" style="width:100%;height:90px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
                        <?php endif; ?>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" name="edit_product" class="btn-kd btn-kd-primary w-100 justify-content-center"><i class="fa-solid fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="card-kd">
    <div class="card-header-kd">
        <h5>All Marketplace Products (<?= count($products) ?>)</h5>
        <input type="text" id="prodSearch" placeholder="Filter..." data-filter-table="prodTable" style="padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;outline:none;width:180px;">
    </div>
    <div class="card-body-kd p-0">
        <table class="table-kd" id="prodTable">
            <thead><tr><th>Photo</th><th>Crop</th><th>Farmer</th><th>Qty (kg)</th><th>Price/kg</th><th>Status</th><th>Listed</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <?php if (!empty($p['image']) && mediaAvailable($p['image'])): ?>
                    <img width="480" height="320" decoding="async" alt="" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($p['image']) ?>" style="width:56px;height:42px;object-fit:cover;border-radius:6px;">
                    <?php else: ?>
                    <?php $icons=['Grain'=>'<i aria-hidden="true" class="fa-solid fa-wheat-awn"></i>','Vegetable'=>'<i aria-hidden="true" class="fa-solid fa-carrot"></i>','Fruit'=>'<i aria-hidden="true" class="fa-solid fa-apple-whole"></i>','Fiber'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Oilseed'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Legume'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Cash Crop'=>'<i aria-hidden="true" class="fa-solid fa-coins"></i>']; ?>
                    <div style="width:56px;height:42px;background:var(--surface3);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px;"><?= $icons[$p['category']]??'<i aria-hidden="true" class="fa-solid fa-seedling"></i>' ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600;"><?= htmlspecialchars($p['crop_name']) ?></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($p['farmer_name']) ?></td>
                <td><?= e($p['quantity_kg']) ?> kg</td>
                <td style="font-weight:700;color:var(--primary);">৳<?= e($p['price_per_kg']) ?></td>
                <td>
                    <?php $sc=['available'=>'badge-success','pending'=>'badge-warning','sold'=>'badge-muted']; ?>
                    <span class="badge-kd <?= $sc[$p['status']]??'badge-muted' ?>" style="font-size:10px;"><?= e(ucfirst($p['status'])) ?></span>
                </td>
                <td style="font-size:11px;color:var(--text-muted);"><?= date('d M Y',strtotime($p['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <?php if ($p['status']==='pending'): ?>
                        <form method="POST" action="?tab=marketplace" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="approve_product" value="<?= e($p['id']) ?>"><button type="submit" class="btn-kd btn-kd-primary" style="padding:4px 8px;font-size:11px;" data-confirm="Approve this product?"><i class="fa-solid fa-check"></i></button></form>
                        <?php endif; ?>
                        <a href="?tab=marketplace&edit=<?= e($p['id']) ?>" class="btn-kd btn-kd-outline" style="padding:4px 8px;font-size:11px;"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" action="?tab=marketplace" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="delete_product" value="<?= e($p['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger" style="padding:4px 8px;font-size:11px;" data-confirm="Delete this product?"><i class="fa-solid fa-trash"></i></button></form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div><!-- page-body -->
</div><!-- main-content -->
</div><!-- layout-wrapper -->


<?php include __DIR__ . '/../includes/footer.php'; ?>
