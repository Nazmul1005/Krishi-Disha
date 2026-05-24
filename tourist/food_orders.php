<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['tourist']);
$tourist = $pdo->prepare("SELECT * FROM TOURIST WHERE user_id=?"); $tourist->execute([$_SESSION['user_id']]); $t = $tourist->fetch(); $tid = $t['id'] ?? 0;
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id   = (int)$_POST['recipe_id'];
    $cook_id     = (int)$_POST['cook_id'];
    $qty         = (int)($_POST['quantity'] ?? 1);
    $deliver_date= $_POST['delivery_date'] ?? '';
    $recipe = $pdo->prepare("SELECT * FROM RECIPE WHERE id=? AND cook_id=?"); $recipe->execute([$recipe_id,$cook_id]); $recipe = $recipe->fetch();
    if ($recipe && $qty > 0 && $deliver_date) {
        $price = $qty * 300; // base price
        $pdo->prepare("INSERT INTO FOOD_ORDER (tourist_id,recipe_id,cook_id,quantity,total_price,delivery_date) VALUES (?,?,?,?,?,?)")->execute([$tid,$recipe_id,$cook_id,$qty,$price,$deliver_date]);
        $msg = "Food order placed! Total: ৳" . number_format($price);
    } else { $err = 'Invalid selection.'; }
}

$cooks = $pdo->query("SELECT c.*, u.name FROM COOK c JOIN USER u ON c.user_id=u.id WHERE c.availability='available' AND u.status='approved'")->fetchAll();
$recipes = $pdo->query("SELECT r.*, u.name as cook_name FROM RECIPE r JOIN COOK c ON r.cook_id=c.id JOIN USER u ON c.user_id=u.id ORDER BY r.is_authentic DESC")->fetchAll();
$my_orders = $pdo->prepare("SELECT fo.*, r.name as recipe_name, u.name as cook_name FROM FOOD_ORDER fo JOIN RECIPE r ON fo.recipe_id=r.id JOIN COOK c ON fo.cook_id=c.id JOIN USER u ON c.user_id=u.id WHERE fo.tourist_id=? ORDER BY fo.created_at DESC"); $my_orders->execute([$tid]); $my_orders = $my_orders->fetchAll();
$page_title = 'Food Orders';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar"><div class="d-flex align-items-center gap-3"><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><i class="fa-solid fa-utensils me-2" style="color:#ea580c;"></i>Authentic Food Orders</div></div></div>
    <div class="page-body">
        <?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="4000"><i class="fa-solid fa-check"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-bowl-food me-2" style="color:#ea580c;"></i>Order Authentic Food</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate>
                            <div class="form-group"><label>Select Cook <span style="color:red">*</span></label>
                            <select name="cook_id" id="cook_sel" class="form-control" required onchange="filterRecipes()"><option value="">Select a cook</option>
                            <?php foreach ($cooks as $ck): ?><option value="<?= $ck['id'] ?>"><?= htmlspecialchars($ck['name']) ?> — <?= htmlspecialchars($ck['specialty']??'') ?></option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Recipe <span style="color:red">*</span></label>
                            <select name="recipe_id" class="form-control" required><option value="">Select recipe</option>
                            <?php foreach ($recipes as $r): ?><option value="<?= $r['id'] ?>" data-cook="<?= $r['cook_id'] ?>"><?= htmlspecialchars($r['name']) ?> <?= $r['is_authentic']?'⭐':'' ?></option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Quantity <span style="color:red">*</span></label><input type="number" name="quantity" min="1" max="20" value="1" class="form-control" required></div>
                            <div class="form-group"><label>Delivery Date <span style="color:red">*</span></label><input type="date" name="delivery_date" min="<?= date('Y-m-d',strtotime('+1 day')) ?>" class="form-control" required></div>
                            <button type="submit" class="btn-kd w-100 justify-content-center" style="background:linear-gradient(135deg,#ea580c,#f97316);color:#fff;padding:12px;"><i class="fa-solid fa-bowl-food"></i> Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd"><h5>My Food Orders (<?= count($my_orders) ?>)</h5></div>
                    <div class="card-body-kd p-0">
                        <table class="table-kd">
                            <thead><tr><th>Recipe</th><th>Cook</th><th>Qty</th><th>Total</th><th>Delivery</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($my_orders as $o): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($o['recipe_name']) ?></td>
                                <td><?= htmlspecialchars($o['cook_name']) ?></td>
                                <td><?= $o['quantity'] ?></td>
                                <td style="color:#ea580c;font-weight:700;">৳<?= number_format($o['total_price']) ?></td>
                                <td style="font-size:12px;"><?= $o['delivery_date'] ?></td>
                                <td><?php $sc=['pending'=>'badge-warning','preparing'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger']; ?><span class="badge-kd <?= $sc[$o['status']]??'badge-muted' ?>"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($my_orders)): ?><tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">No food orders yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div></div>
<script>function filterRecipes(){const cid=document.getElementById('cook_sel').value;document.querySelectorAll('[name="recipe_id"] option[data-cook]').forEach(o=>{o.style.display=(!cid||o.dataset.cook===cid)?'':'none';});}</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
