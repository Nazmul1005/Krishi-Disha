<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['farmer']);

$farmer = $pdo->prepare("SELECT * FROM FARMER WHERE user_id=?");
$farmer->execute([$_SESSION['user_id']]);
$f = $farmer->fetch();
$fid = $f['id'] ?? 0;

$msg = $err = '';

// Add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $crop_id  = (int)$_POST['crop_id'];
    $qty      = (float)$_POST['quantity_kg'];
    $price    = (float)$_POST['price_per_kg'];
    $desc     = trim($_POST['description'] ?? '');
    if ($crop_id && $qty > 0 && $price > 0) {
        $pdo->prepare("INSERT INTO PRODUCT (farmer_id,crop_id,quantity_kg,price_per_kg,description) VALUES (?,?,?,?,?)")
            ->execute([$fid, $crop_id, $qty, $price, $desc]);
        $msg = 'Product listed successfully!';
    } else { $err = 'Please fill all required fields.'; }
}

// Delete product
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM PRODUCT WHERE id=? AND farmer_id=?")->execute([(int)$_GET['delete'], $fid]);
    header('Location: produce.php?msg=deleted'); exit;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $msg = 'Product removed.';

$products = $pdo->prepare("SELECT p.*, c.name as crop_name FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id WHERE p.farmer_id=? ORDER BY p.created_at DESC");
$products->execute([$fid]);
$products = $products->fetchAll();

$crops = $pdo->query("SELECT id,name FROM CROP ORDER BY name")->fetchAll();
$page_title = 'My Produce';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title"><i class="fa-solid fa-basket-shopping me-2" style="color:var(--primary);"></i>My Produce</div>
        </div>
    </div>
    <div class="page-body">
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="3000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error" data-autohide="4000"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

        <div class="row g-4">
            <!-- Add form -->
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-plus me-2" style="color:var(--primary);"></i>List New Produce</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate>
                            <div class="form-group">
                                <label>Crop <span style="color:red">*</span></label>
                                <select name="crop_id" class="form-control" required>
                                    <option value="">Select crop</option>
                                    <?php foreach ($crops as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantity (kg) <span style="color:red">*</span></label>
                                <input type="number" name="quantity_kg" step="0.01" min="0.01" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Price per kg (৳) <span style="color:red">*</span></label>
                                <input type="number" name="price_per_kg" step="0.01" min="0.01" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Quality notes, harvest date..."></textarea>
                            </div>
                            <button type="submit" name="add_product" class="btn-kd btn-kd-primary w-100 justify-content-center">
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
                                <td><?= $p['quantity_kg'] ?> kg</td>
                                <td style="font-weight:700;color:var(--primary);">৳<?= $p['price_per_kg'] ?></td>
                                <td><?php $sc=['available'=>'badge-success','sold'=>'badge-muted','pending'=>'badge-warning']; ?><span class="badge-kd <?= $sc[$p['status']]??'badge-muted' ?>"><?= ucfirst($p['status']) ?></span></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y',strtotime($p['created_at'])) ?></td>
                                <td>
                                    <?php if ($p['status'] === 'available'): ?>
                                    <a href="?delete=<?= $p['id'] ?>" class="btn-kd btn-kd-danger" style="padding:4px 10px;font-size:11px;" data-confirm="Remove this product?">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?><tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">No products listed yet.</td></tr><?php endif; ?>
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
