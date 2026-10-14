<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['farmer']);

$farmer = $pdo->prepare("SELECT * FROM FARMER WHERE user_id=?");
$farmer->execute([$_SESSION['user_id']]);
$f = $farmer->fetch();
$fid = $f['id'] ?? 0;

$msg = $err = '';

if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        if(isset($_POST['add_product'])) {
            $qty=positiveDecimal($_POST['quantity_kg'] ?? '');$price=positiveDecimal($_POST['price_per_kg'] ?? '',100000);$minimum=positiveDecimal($_POST['minimum_order_kg'] ?? '0.50');
            // Harvest dates may be in the past, but never in the future.
            $raw=$_POST['harvest_date'] ?? ''; $date=DateTimeImmutable::createFromFormat('!Y-m-d',$raw);
            if(!$date || $date->format('Y-m-d')!==$raw || $raw>date('Y-m-d')) throw new InvalidArgumentException('Enter the actual harvest date.');
            if((float)$minimum>(float)$qty) throw new InvalidArgumentException('Minimum order exceeds stock.');
            $pdo->prepare("INSERT INTO PRODUCT(farmer_id,crop_id,quantity_kg,price_per_kg,description,variety,grade,harvest_date,pickup_location,minimum_order_kg,status) VALUES (?,?,?,?,?,?,?,?,?,?,'pending')")->execute([$fid,(int)($_POST['crop_id'] ?? 0),$qty,$price,textInput($_POST['description'] ?? '',5000,false),textInput($_POST['variety'] ?? '',150),textInput($_POST['grade'] ?? '',100),$raw,textInput($_POST['pickup_location'] ?? '',255),$minimum]);flash('success','Listing submitted for approval.');
        } elseif(isset($_POST['delete'])) {
            $pdo->prepare("UPDATE PRODUCT SET status='pending' WHERE id=? AND farmer_id=?")->execute([(int)$_POST['delete'],$fid]);flash('success','Listing withdrawn from sale; order records retained.');
        }
    } catch(Throwable $e) { flash('error',$e instanceof InvalidArgumentException ? $e->getMessage() : 'Listing could not be updated.'); }
    header('Location: produce.php');exit;
}

$products = $pdo->prepare("SELECT p.*, c.name as crop_name FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id WHERE p.farmer_id=? ORDER BY p.created_at DESC");
$products=pagedRows($pdo,$products->queryString,[$fid],'produce','Your produce',['crop_name', 'variety', 'pickup_location'],true);

$crops = $pdo->query("SELECT id,name FROM CROP ORDER BY name")->fetchAll();
$page_title = 'My Produce';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">

            <h1 class="topbar-title"><i class="fa-solid fa-basket-shopping me-2" style="color:var(--primary);"></i>My Produce</h1>
        </div>
    </div>
    <div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><?= renderFlash() ?>
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error" data-autohide="4000"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

        <div class="row g-4">
            <!-- Add form -->
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-plus me-2" style="color:var(--primary);"></i>List New Produce</h5></div>
                    <div class="card-body-kd">
                        <form id="produce-form" method="POST" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Crop <span style="color:red">*</span></label>
                                <select id="<?= e($fieldId) ?>" name="crop_id" class="form-control" required>
                                    <option value="">Select crop</option>
                                    <?php foreach ($crops as $c): ?><option value="<?= e($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Quantity (kg) <span style="color:red">*</span></label>
                                <input id="<?= e($fieldId) ?>" type="number" name="quantity_kg" step="0.01" min="0.01" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Price per kg (৳) <span style="color:red">*</span></label>
                                <input id="<?= e($fieldId) ?>" type="number" name="price_per_kg" step="0.01" min="0.01" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Description</label>
                                <textarea id="<?= e($fieldId) ?>" name="description" class="form-control" rows="3" placeholder="Quality notes, harvest date..."></textarea>
                            </div>
                            <?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Variety</label><input id="<?= e($fieldId) ?>" class="form-control" name="variety" maxlength="150" required><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Grade / quality</label><input id="<?= e($fieldId) ?>" class="form-control" name="grade" maxlength="100" required><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Harvest date</label><input id="<?= e($fieldId) ?>" class="form-control" type="date" name="harvest_date" max="<?= date('Y-m-d') ?>" required><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Pickup location</label><input id="<?= e($fieldId) ?>" class="form-control" name="pickup_location" maxlength="255" required><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Minimum order (kg)</label><input id="<?= e($fieldId) ?>" class="form-control" name="minimum_order_kg" type="number" min="0.01" step="0.01" value="0.50" required><button type="submit" name="add_product" class="btn-kd btn-kd-primary w-100 justify-content-center">
                                <i class="fa-solid fa-plus"></i> List Produce
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products table -->
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd">
                        <h5>My Listed Products (<?= count($products) ?>)</h5>
                    </div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Crop</th><th>Qty</th><th>Price/kg</th><th>Status</th><th>Listed</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($p['crop_name']) ?></div>
                                    <?php if ($p['description']): ?><div style="font-size:11px;color:var(--text-muted);"><?= mb_substr(htmlspecialchars($p['description']),0,50) ?></div><?php endif; ?>
                                </td>
                                <td><?= e($p['quantity_kg']) ?> kg</td>
                                <td style="font-weight:700;color:var(--primary);">৳<?= e($p['price_per_kg']) ?></td>
                                <td><?php $sc=['available'=>'badge-success','sold'=>'badge-muted','pending'=>'badge-warning']; ?><span class="badge-kd <?= $sc[$p['status']]??'badge-muted' ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y',strtotime($p['created_at'])) ?></td>
                                <td>
                                    <?php if ($p['status'] === 'available'): ?>
                                    <form method="POST" action="" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="delete" value="<?= e($p['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger" style="padding:4px 10px;font-size:11px;" data-confirm="Remove this product?">
                                        <i class="fa-solid fa-trash"></i>
                                    </button></form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?><tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">No products listed yet. <a href="#produce-form">List your first produce</a></td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
