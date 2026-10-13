<?php
require_once __DIR__.'/../includes/auth_check.php';requireAuth(['farmer']);require_once __DIR__.'/../includes/transactions.php';
$uid=(int)$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST') {
    try { changeState($pdo,$uid,'tour_booking',(int)($_POST['id'] ?? 0),(string)($_POST['status'] ?? ''));flash('success','Booking updated.'); }catch(Throwable $e) {flash('error',$e instanceof InvalidArgumentException?$e->getMessage():'Booking update failed.');}header('Location: bookings.php');exit;
}
$stmt=$pdo->prepare('SELECT b.*,ft.title,u.name AS buyer,u.phone FROM TOUR_BOOKING b JOIN FARM_TOUR ft ON b.tour_id=ft.id JOIN FARMER f ON ft.farmer_id=f.id JOIN TOURIST t ON b.tourist_id=t.id JOIN USER u ON t.user_id=u.id WHERE f.user_id=? ORDER BY b.id DESC LIMIT 100');$stmt->execute([$uid]);$bookings=$stmt->fetchAll();$page_title='Farm tour bookings';include __DIR__.'/../includes/header.php';
?>
<div class="layout-wrapper"><?php include __DIR__.'/../includes/sidebar.php'; ?><main class="main-content" id="main-content" tabindex="-1"><div class="page-body"><h1>Farm tour bookings</h1><?= renderFlash() ?>
<?php foreach($bookings as $booking): ?><article class="card-kd mb-3"><div class="card-body-kd"><h2 class="h5">#<?= e($booking['id']) ?> <?= e($booking['title']) ?></h2><p><?= e($booking['buyer']) ?> · <?= e($booking['phone']) ?> · <?= e($booking['start_date']) ?> — <?= e($booking['end_date']) ?> · <?= e($booking['num_visitors']) ?> visitors · <?= e($booking['status']) ?></p>
<?php $next=['pending'=>'confirmed','confirmed'=>'completed'][$booking['status']] ?? null; if($next): ?><form method="POST"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($booking['id']) ?>"><button class="btn-kd btn-kd-primary" name="status" value="<?= e($next) ?>"><?= e(ucfirst($next)) ?></button><button class="btn-kd btn-kd-danger" name="status" value="cancelled">Cancel</button></form><?php endif; ?></div></article><?php endforeach; ?>
<a href="../modules/payments.php">Cash collection</a></div></main></div><?php include __DIR__.'/../includes/footer.php'; ?>
