<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth();

$uid = $_SESSION['user_id'];
$search   = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';

/**
 * Shared helper: record a completed payment + 5% admin commission for an order.
 */
function recordOrderPayment(PDO $pdo, int $uid, int $oid, float $total): void {
    $pdo->prepare("INSERT INTO PAYMENT (payer_id,ref_type,ref_id,amount,status) VALUES (?,'order',?,?,'completed')")
        ->execute([$uid, $oid, $total]);
    $pay_id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO ADMIN_COMMISSION (payment_id,commission_rate,commission_amount) VALUES (?,5.00,?)")
        ->execute([$pay_id, $total * 0.05]);
}

// ── Buy directly from a farmer ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_farmer'])) {
    verifyCsrf();
    $prod_id = (int)$_POST['product_id'];
    $qty     = (float)$_POST['quantity_kg'];
    $prod    = $pdo->prepare("SELECT p.*, c.name FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id WHERE p.id=?");
    $prod->execute([$prod_id]);
    $prod = $prod->fetch();
    if ($prod && $qty > 0 && $qty <= $prod['quantity_kg'] && $prod['status'] === 'available') {
        $total = $qty * $prod['price_per_kg'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO `ORDER` (user_id,product_id,quantity_kg,total_price) VALUES (?,?,?,?)")
                ->execute([$uid, $prod_id, $qty, $total]);
            $oid = $pdo->lastInsertId();
            $new_qty    = $prod['quantity_kg'] - $qty;
            $new_status = ($new_qty <= 0) ? 'sold' : 'available';
            $pdo->prepare("UPDATE PRODUCT SET quantity_kg=?, status=? WHERE id=?")->execute([$new_qty, $new_status, $prod_id]);
            recordOrderPayment($pdo, $uid, $oid, $total);
            $pdo->commit();
            flash('success', 'Order placed! Total: ' . taka($total));
        } catch (Exception $e) { $pdo->rollBack(); error_log('Farmer order failed: '.$e->getMessage()); flash('error', 'Order failed. Please try again.'); }
    } else { flash('error', 'Invalid quantity or product unavailable.'); }
    header('Location: marketplace.php'); exit;
}

// ── Buy from a dealer's inventory ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_dealer'])) {
    verifyCsrf();
    $inv_id = (int)$_POST['dealer_inventory_id'];
    $qty    = (float)$_POST['quantity_kg'];
    $inv    = $pdo->prepare("SELECT di.* FROM DEALER_INVENTORY di WHERE di.id=?");
    $inv->execute([$inv_id]);
    $inv = $inv->fetch();
    if ($inv && $qty > 0 && $qty <= $inv['stock_remaining'] && $inv['markup_price'] > 0) {
        $total = $qty * $inv['markup_price'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO `ORDER` (user_id,product_id,dealer_inventory_id,quantity_kg,total_price) VALUES (?,?,?,?,?)")
                ->execute([$uid, $inv['product_id'], $inv_id, $qty, $total]);
            $oid = $pdo->lastInsertId();
            $pdo->prepare("UPDATE DEALER_INVENTORY SET stock_remaining = stock_remaining - ? WHERE id=?")->execute([$qty, $inv_id]);
            recordOrderPayment($pdo, $uid, $oid, $total);
            $pdo->commit();
            flash('success', 'Order placed with dealer! Total: ' . taka($total));
        } catch (Exception $e) { $pdo->rollBack(); error_log('Dealer order failed: '.$e->getMessage()); flash('error', 'Order failed. Please try again.'); }
    } else { flash('error', 'Invalid quantity or stock unavailable.'); }
    header('Location: marketplace.php'); exit;
}

// ── Fetch farmer produce ──────────────────────────────────────
$where = ['p.status = "available" AND p.quantity_kg > 0']; $params = [];
if ($search)   { $where[] = "(c.name LIKE ? OR c.scientific_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($category) { $where[] = "c.category = ?"; $params[] = $category; }

$stmt = $pdo->prepare("
    SELECT p.*, c.name as crop_name, c.category, c.season, u.name as farmer_name
    FROM PRODUCT p
    JOIN CROP c ON p.crop_id=c.id
    JOIN FARMER f ON p.farmer_id=f.id
    JOIN USER u ON f.user_id=u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.name ASC
");
$stmt->execute($params);
$items = $stmt->fetchAll();

// ── Fetch dealer inventory (resale listings) ──────────────────
$dwhere = ['di.stock_remaining > 0']; $dparams = [];
if ($search)   { $dwhere[] = "(c.name LIKE ? OR c.scientific_name LIKE ?)"; $dparams[] = "%$search%"; $dparams[] = "%$search%"; }
if ($category) { $dwhere[] = "c.category = ?"; $dparams[] = $category; }

$dstmt = $pdo->prepare("
    SELECT di.id AS inventory_id, di.markup_price, di.stock_remaining,
           c.name as crop_name, c.category, u.name as dealer_name
    FROM DEALER_INVENTORY di
    JOIN PRODUCT p ON di.product_id=p.id
    JOIN CROP c ON p.crop_id=c.id
    JOIN DEALER d ON di.dealer_id=d.id
    JOIN USER u ON d.user_id=u.id
    WHERE " . implode(' AND ', $dwhere) . "
    ORDER BY c.name ASC
");
$dstmt->execute($dparams);
$dealer_items = $dstmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT c.category FROM CROP c
    WHERE c.id IN (SELECT crop_id FROM PRODUCT WHERE status='available' AND quantity_kg > 0)
       OR c.id IN (SELECT p.crop_id FROM DEALER_INVENTORY di JOIN PRODUCT p ON di.product_id=p.id WHERE di.stock_remaining > 0)
    ORDER BY c.category")->fetchAll(PDO::FETCH_COLUMN);

$total_listings = count($items) + count($dealer_items);

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
        <div class="topbar-actions">
            <span class="badge-kd badge-success"><?= $total_listings ?> listings available</span>
            <?php if (currentRole() === 'admin'): ?>
            <a href="/KrishiDisha/admin/manage_content.php?tab=marketplace" class="btn-kd btn-kd-outline" style="padding:6px 14px;font-size:12px;">
                <i class="fa-solid fa-pen-to-square"></i> Manage Products
            </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="page-body">
        <?= renderFlash() ?>

        <form class="filter-bar" method="GET">
            <input type="text" name="q" placeholder="Search crops..." value="<?= htmlspecialchars($search) ?>" style="flex:2;">
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?><option value="<?= $cat ?>" <?= $category===$cat?'selected':'' ?>><?= $cat ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn-kd btn-kd-primary"><i class="fa-solid fa-search"></i> Search</button>
            <a href="marketplace.php" class="btn-kd btn-kd-outline">Reset</a>
        </form>

        <?php if ($total_listings === 0): ?>
        <div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;">🛒</div><h4 style="color:var(--text-muted);">No listings available</h4></div></div>
        <?php endif; ?>

        <?php if ($items): ?>
        <h5 style="margin:8px 0 16px;color:var(--primary-dark);"><i class="fa-solid fa-tractor me-2" style="color:var(--primary);"></i>Direct from Farmers</h5>
        <div class="row g-4">
            <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-kd">
                    <div style="height:140px;overflow:hidden;">
                        <?php $icons=['Grain'=>'🌾','Vegetable'=>'🥕','Fruit'=>'🍎','Fiber'=>'🪢','Oilseed'=>'🌻','Legume'=>'🫘','Cash Crop'=>'💰']; ?>
                        <?php if (!empty($item['image']) && file_exists(__DIR__.'/../'.$item['image'])): ?>
                        <img src="/KrishiDisha/<?= htmlspecialchars($item['image']) ?>" style="width:100%;height:140px;object-fit:cover;">
                        <?php else: ?>
                        <div style="height:140px;background:linear-gradient(135deg,var(--surface3),var(--accent3));display:flex;align-items:center;justify-content:center;font-size:64px;"><?= $icons[$item['category']]??'🌱' ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body-kd">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                            <h5 style="margin:0;"><?= htmlspecialchars($item['crop_name']) ?></h5>
                            <span class="badge-kd badge-info" style="font-size:10px;"><?= $item['category'] ?></span>
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Sold by: <?= htmlspecialchars($item['farmer_name']) ?> (Farmer)</div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                            <div>
                                <div style="font-size:24px;font-weight:800;color:var(--primary);font-family:'Nunito',sans-serif;">৳<?= $item['price_per_kg'] ?><span style="font-size:12px;font-weight:400;color:var(--text-muted);">/kg</span></div>
                                <div style="font-size:12px;color:var(--text-muted);"><i class="fa-solid fa-weight-hanging me-1"></i><?= $item['quantity_kg'] ?> kg available</div>
                            </div>
                        </div>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="number" name="quantity_kg" step="0.5" min="0.5" max="<?= $item['quantity_kg'] ?>" placeholder="kg" class="form-control" style="flex:1;padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:14px;outline:none;" required>
                                <button type="submit" name="order_farmer" class="btn-kd btn-kd-primary" style="flex:1;justify-content:center;" data-confirm="Place this order?">
                                    <i class="fa-solid fa-cart-shopping"></i> Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($dealer_items): ?>
        <h5 style="margin:28px 0 16px;color:var(--primary-dark);"><i class="fa-solid fa-store me-2" style="color:#2563eb;"></i>From Dealers</h5>
        <div class="row g-4">
            <?php foreach ($dealer_items as $d): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-kd">
                    <div style="height:140px;overflow:hidden;">
                        <?php $icons=['Grain'=>'🌾','Vegetable'=>'🥕','Fruit'=>'🍎','Fiber'=>'🪢','Oilseed'=>'🌻','Legume'=>'🫘','Cash Crop'=>'💰']; ?>
                        <div style="height:140px;background:linear-gradient(135deg,#dbeafe,#eff6ff);display:flex;align-items:center;justify-content:center;font-size:64px;"><?= $icons[$d['category']]??'🌱' ?></div>
                    </div>
                    <div class="card-body-kd">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                            <h5 style="margin:0;"><?= htmlspecialchars($d['crop_name']) ?></h5>
                            <span class="badge-kd badge-info" style="font-size:10px;background:#dbeafe;color:#1e40af;">Dealer</span>
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Sold by: <?= htmlspecialchars($d['dealer_name']) ?> (Dealer)</div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                            <div>
                                <div style="font-size:24px;font-weight:800;color:#2563eb;font-family:'Nunito',sans-serif;">৳<?= $d['markup_price'] ?><span style="font-size:12px;font-weight:400;color:var(--text-muted);">/kg</span></div>
                                <div style="font-size:12px;color:var(--text-muted);"><i class="fa-solid fa-weight-hanging me-1"></i><?= $d['stock_remaining'] ?> kg in stock</div>
                            </div>
                        </div>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="dealer_inventory_id" value="<?= $d['inventory_id'] ?>">
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="number" name="quantity_kg" step="0.5" min="0.5" max="<?= $d['stock_remaining'] ?>" placeholder="kg" class="form-control" style="flex:1;padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:14px;outline:none;" required>
                                <button type="submit" name="order_dealer" class="btn-kd btn-kd-primary" style="flex:1;justify-content:center;background:linear-gradient(135deg,#2563eb,#60a5fa);" data-confirm="Place this order?">
                                    <i class="fa-solid fa-cart-shopping"></i> Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
