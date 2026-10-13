<?php
require_once __DIR__.'/../includes/auth_check.php';requireAuth();$uid=(int)$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST') {$pdo->prepare('UPDATE NOTIFICATION SET is_read=1 WHERE user_id=?')->execute([$uid]);header('Location: notifications.php');exit;}
$stmt=$pdo->prepare('SELECT * FROM NOTIFICATION WHERE user_id=? ORDER BY id DESC LIMIT 100');$stmt->execute([$uid]);$notifications=$stmt->fetchAll();$page_title='Notifications';include __DIR__.'/../includes/header.php';
?>
<div class="layout-wrapper"><?php include __DIR__.'/../includes/sidebar.php'; ?><main class="main-content" id="main-content" tabindex="-1"><div class="page-body"><h1><?= t('Notifications') ?></h1><form method="POST"><?= csrfField() ?><button class="btn-kd btn-kd-outline">Mark all read / সব পড়া হয়েছে</button></form><?php foreach($notifications as $notice): ?><article class="card-kd mt-3"><div class="card-body-kd"><a href="<?= e($notice['link']) ?>"><?= e($notice['message']) ?></a><p><?= e($notice['created_at']) ?><?= $notice['is_read']?'':' · unread' ?></p></div></article><?php endforeach; ?></div></main></div><?php include __DIR__.'/../includes/footer.php'; ?>
