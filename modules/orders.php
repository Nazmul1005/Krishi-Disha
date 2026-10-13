<?php
require_once __DIR__.'/../includes/auth_check.php';requireAuth();require_once __DIR__.'/../includes/transactions.php';
$uid=(int)$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST') {
    try { changeState($pdo,$uid,'order',(int)($_POST['id'] ?? 0),(string)($_POST['status'] ?? ''));flash('success','Order updated.'); }
    catch(Throwable $e) { flash('error',$e instanceof InvalidArgumentException ? $e->getMessage() : 'Could not update order.'); }
    header('Location: '.$_SERVER['PHP_SELF']);exit;
}
$page=max(1,min(10000,(int)($_GET['page'] ?? 1)));$offset=($page-1)*25;
$stmt=$pdo->prepare("SELECT o.*,c.name AS crop_name,bu.name AS buyer_name,COALESCE(d.user_id,f.user_id) AS seller FROM `ORDER` o JOIN PRODUCT p ON o.product_id=p.id JOIN CROP c ON p.crop_id=c.id JOIN FARMER f ON p.farmer_id=f.id LEFT JOIN DEALER_INVENTORY di ON o.dealer_inventory_id=di.id LEFT JOIN DEALER d ON di.dealer_id=d.id JOIN USER bu ON o.user_id=bu.id WHERE ".(!empty($sellerOnly)?'COALESCE(d.user_id,f.user_id)=?':'(o.user_id=? OR COALESCE(d.user_id,f.user_id)=?)')." ORDER BY o.id DESC LIMIT 25 OFFSET $offset");$stmt->execute(!empty($sellerOnly)?[$uid]:[$uid,$uid]);$orders=$stmt->fetchAll();
$page_title='Produce orders';include __DIR__.'/../includes/header.php';
?>
<div class="layout-wrapper"><?php include __DIR__.'/../includes/sidebar.php'; ?><main class="main-content" id="main-content" tabindex="-1"><div class="page-body"><h1><?= t('Produce orders','ফসলের অর্ডার') ?></h1><?= renderFlash() ?>
<p>Confirm stock and pickup/delivery arrangements before accepting. Cash collection is recorded separately after fulfillment.</p>
<?php foreach($orders as $order): $seller=(int)$order['seller']===$uid; ?>
<article class="card-kd mb-3"><div class="card-body-kd"><h2 class="h5">#<?= e($order['id']) ?> · <?= e($order['crop_name']) ?></h2><p><?= e($order['quantity_kg']) ?> kg · <?= taka($order['total_price']) ?> · <?= e($order['status']) ?></p>
<p><?= e($order['fulfillment']) ?>: <?= e($order['delivery_address']) ?> · <?= e($order['contact_phone']) ?> · Buyer: <?= e($order['buyer_name']) ?></p>
<?php $next=$seller ? ['pending'=>'confirmed','confirmed'=>'delivered'][$order['status']] ?? null : null; if($next): ?>
<form method="POST"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($order['id']) ?>"><button class="btn-kd btn-kd-primary" name="status" value="<?= e($next) ?>"><?= e(ucfirst($next)) ?></button></form>
<?php endif; if(($seller && in_array($order['status'],['pending','confirmed'],true)) || (!$seller && $order['status']==='pending')): ?>
<form method="POST"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($order['id']) ?>"><button class="btn-kd btn-kd-danger" name="status" value="cancelled">Cancel</button></form><?php endif; ?>
</div></article><?php endforeach; if(!$orders) echo emptyState('No orders yet'); ?>
<nav aria-label="Order pages"><?php if($page>1): ?><a href="?page=<?= $page-1 ?>">Previous</a><?php endif; ?> <a href="?page=<?= $page+1 ?>">Next</a></nav><a href="/KrishiDisha/modules/payments.php">Cash collection and payment history</a></div></main></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
