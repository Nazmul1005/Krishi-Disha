<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth();

$uid = $_SESSION['user_id'];
$msg = $err = '';
$search   = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';

// Place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_inventory'])) {
    $inv_id = (int)$_POST['inventory_id'];
    $qty    = (float)$_POST['quantity_kg'];
    $inv    = $pdo->prepare("SELECT di.*, p.crop_id FROM DEALER_INVENTORY di JOIN PRODUCT p ON di.product_id=p.id WHERE di.id=?");
    $inv->execute([$inv_id]);
    $inv = $inv->fetch();
    if ($inv && $qty > 0 && $qty <= $inv['stock_remaining']) {
        $total = $qty * $inv['markup_price'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO `ORDER` (user_id,inventory_id,quantity_kg,total_price) VALUES (?,?,?,?)")
                ->execute([$uid, $inv_id, $qty, $total]);
            $oid = $pdo->lastInsertId();
            $pdo->prepare("UPDATE DEALER_INVENTORY SET stock_remaining=stock_remaining-? WHERE id=?")->execute([$qty, $inv_id]);
            $pdo->prepare("INSERT INTO PAYMENT (payer_id,ref_type,ref_id,amount,status) VALUES (?,?,?,?,'completed')")->execute([$uid,'order',$oid,$total]);
            $pay_id = $pdo->lastInsertId();
            $commission = $total * 0.05;
            $pdo->prepare("INSERT INTO ADMIN_COMMISSION (payment_id,commission_rate,commission_amount) VALUES (?,5.00,?)")->execute([$pay_id,$commission]);
            $pdo->commit();
            $msg = "Order placed! Total: ৳" . number_format($total, 2);
        } catch(Exception $e) { $pdo->rollBack(); $err = 'Order failed. Please try again.'; }
    } else { $err = 'Invalid quantity or product unavailable.'; }
}

// Fetch inventory
$where = ['di.stock_remaining > 0']; $params = [];
if ($search)   { $where[] = "(c.name LIKE ? OR c.scientific_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($category) { $where[] = "c.category = ?"; $params[] = $category; }

$stmt = $pdo->prepare("
    SELECT di.*, p.description, c.name as crop_name, c.category, c.season, u.name as dealer_name
    FROM DEALER_INVENTORY di
    JOIN PRODUCT p ON di.product_id=p.id
    JOIN CROP c ON p.crop_id=c.id
    JOIN DEALER d ON di.dealer_id=d.id
    JOIN USER u ON d.user_id=u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.name ASC
");
$stmt->execute($params);
$items = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT c.category FROM DEALER_INVENTORY di JOIN PRODUCT p ON di.product_id=p.id JOIN CROP c ON p.crop_id=c.id WHERE di.stock_remaining>0 ORDER BY c.category")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Marketplace';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title"><i class="fa-solid fa-store me-2" style="color:var(--primary);"></i>Marketplace</div>
        </div>
        <div class="topbar-actions"><span class="badge-kd badge-success"><?= count($items) ?> products available</span></div>
    </div>
    <div class="page-body">
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="5000"><i class="fa-solid fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

        <form class="filter-bar" method="GET">
            <input type="text" name="q" placeholder="Search crops..." value="<?= htmlspecialchars($search) ?>" style="flex:2;">
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?><option value="<?= $cat ?>" <?= $category===$cat?'selected':'' ?>><?= $cat ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn-kd btn-kd-primary"><i class="fa-solid fa-search"></i> Search</button>
            <a href="marketplace.php" class="btn-kd btn-kd-outline">Reset</a>
        </form>

        <div class="row g-4">
            <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-kd">
                    <div style="height:140px;background:linear-gradient(135deg,var(--surface3),var(--accent3));display:flex;align-items:center;justify-content:center;font-size:64px;">
                        <?php $icons=['Grain'=>'🌾','Vegetable'=>'🥕','Fruit'=>'🍎','Fiber'=>'🪢','Oilseed'=>'🌻','Legume'=>'🫘','Cash Crop'=>'💰']; echo $icons[$item['category']]??'🌱'; ?>
                    </div>
                    <div class="card-body-kd">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                            <h5 style="margin:0;"><?= htmlspecialchars($item['crop_name']) ?></h5>
                            <span class="badge-kd badge-info" style="font-size:10px;"><?= $item['category'] ?></span>
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Sold by: <?= htmlspecialchars($item['dealer_name']) ?></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                            <div>
                                <div style="font-size:24px;font-weight:800;color:var(--primary);font-family:'Nunito',sans-serif;">৳<?= $item['markup_price'] ?><span style="font-size:12px;font-weight:400;color:var(--text-muted);">/kg</span></div>
                                <div style="font-size:12px;color:var(--text-muted);"><i class="fa-solid fa-weight-hanging me-1"></i><?= $item['stock_remaining'] ?> kg in stock</div>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="inventory_id" value="<?= $item['id'] ?>">
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="number" name="quantity_kg" step="0.5" min="0.5" max="<?= $item['stock_remaining'] ?>" placeholder="kg" class="form-control" style="flex:1;padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:14px;outline:none;" required>
                                <button type="submit" name="order_inventory" class="btn-kd btn-kd-primary" style="flex:1;justify-content:center;" data-confirm="Place this order?">
                                    <i class="fa-solid fa-cart-shopping"></i> Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <div class="col-12"><div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;">🛒</div><h4 style="color:var(--text-muted);">No products available</h4></div></div></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
