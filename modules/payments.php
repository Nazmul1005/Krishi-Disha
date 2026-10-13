<?php
require_once __DIR__.'/../includes/auth_check.php';requireAuth();require_once __DIR__.'/../includes/transactions.php';
$uid=(int)$_SESSION['user_id'];$admin=currentRole()==='admin';
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $id=(int)($_POST['id'] ?? 0);$action=(string)($_POST['action'] ?? '');
        if($action==='collect') collectCash($pdo,$uid,$id,(string)($_POST['amount'] ?? ''),(string)($_POST['reference'] ?? ''));
        else paymentOperation($pdo,$uid,$id,$action,(string)($_POST['reference'] ?? ''),(int)($_POST['share_id'] ?? 0));
        flash('success','Cash movement recorded with an audit trail.');
    }catch(Throwable $e) {flash('error',$e instanceof InvalidArgumentException?$e->getMessage():'Unable to record payment.');}
    header('Location: payments.php');exit;
}
$page=max(1,min(10000,(int)($_GET['page'] ?? 1)));$offset=($page-1)*25;
$stmt=$pdo->prepare("SELECT p.*,u.name AS payer_name,v.name AS seller_name FROM PAYMENT p JOIN USER u ON p.payer_id=u.id LEFT JOIN USER v ON p.payee_id=v.id WHERE ".($admin?'1=1':'p.payer_id=? OR p.payee_id=? OR EXISTS(SELECT 1 FROM PAYMENT_SHARE ps WHERE ps.payment_id=p.id AND ps.payee_id=?)')." ORDER BY p.id DESC LIMIT 25 OFFSET $offset");$stmt->execute($admin?[]:[$uid,$uid,$uid]);$payments=$stmt->fetchAll();$page_title='Payments and cash collection';include __DIR__.'/../includes/header.php';
?>
<div class="layout-wrapper"><?php include __DIR__.'/../includes/sidebar.php'; ?><main class="main-content" id="main-content" tabindex="-1"><div class="page-body"><h1><?= t('Payments and cash collection','পেমেন্ট ও নগদ সংগ্রহ') ?></h1><?= renderFlash() ?><p>Only record money that actually changed hands. Keep the receipt or transfer reference. No online payment provider is connected.</p>
<?php foreach($payments as $payment): ?><article class="card-kd mb-3"><div class="card-body-kd"><h2 class="h5">#<?= e($payment['id']) ?> <?= e($payment['ref_type']) ?> #<?= e($payment['ref_id']) ?></h2><p><?= e($payment['payer_name']) ?> → <?= e($payment['seller_name']) ?> · <?= taka($payment['amount']) ?> · <?= e($payment['status']) ?><?= $payment['verified_at']?' · collection verified':' · collection unverified' ?><?= $payment['refund_requested_at']?' · refund requested':'' ?></p>
<?php if(($admin || (int)$payment['payee_id']===$uid) && !$payment['verified_at'] && in_array($payment['status'],['pending','completed'],true)): ?>
<form method="POST"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($payment['id']) ?>"><label>Actual cash received (৳)</label><input class="form-control" name="amount" type="number" min="0.01" step="0.01" required><label>Receipt reference</label><input class="form-control" name="reference" maxlength="150" required><button class="btn-kd btn-kd-primary" name="action" value="collect" data-confirm="Confirm this cash has actually been received?">Confirm cash received</button></form><?php endif; ?>
<?php $stmt=$pdo->prepare('SELECT ps.*,u.name FROM PAYMENT_SHARE ps JOIN USER u ON ps.payee_id=u.id WHERE payment_id=?');$stmt->execute([$payment['id']]);foreach($stmt->fetchAll() as $share): ?>
<p>Seller net proceeds: <?= e($share['name']) ?> · <?= taka($share['net_amount']) ?> · <?= $share['paid_at']?'paid/retained':'not paid' ?><?= $share['recovered_at']?' · returned for refund':'' ?></p>
<?php $payout=($admin || (int)$payment['verified_by']===$uid) && $payment['verified_at'] && $payment['status']==='completed' && !$payment['refund_requested_at'] && !$share['paid_at'];$recover=$admin && $payment['refund_requested_at'] && $share['paid_at'] && !$share['recovered_at'];if($payout || $recover): ?>
<form method="POST"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($payment['id']) ?>"><input type="hidden" name="share_id" value="<?= e($share['id']) ?>"><label>Cash transfer receipt</label><input class="form-control" name="reference" required maxlength="150"><button class="btn-kd btn-kd-outline" name="action" value="<?= $recover?'recover':'payout' ?>"><?= $recover?'Confirm seller returned money':'Confirm seller payout' ?></button></form><?php endif; endforeach; ?>
<?php if($admin && $payment['verified_at'] && $payment['status']==='completed' && !$payment['refund_requested_at']): ?><form method="POST"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($payment['id']) ?>"><label>Refund approval reason / support ticket reference</label><input class="form-control" name="reference" maxlength="150" required><button class="btn-kd btn-kd-danger" name="action" value="refund_request">Approve refund review</button></form><?php endif; ?>
<?php if($admin && $payment['verified_at'] && $payment['status']==='completed'): ?>
<form method="POST"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($payment['id']) ?>"><label><?= $payment['refund_requested_at']?'Buyer refund receipt':'Platform commission receipt' ?></label><input class="form-control" name="reference" maxlength="150" required><button class="btn-kd btn-kd-outline" name="action" value="<?= $payment['refund_requested_at']?'refund':'commission' ?>"><?= $payment['refund_requested_at']?'Confirm buyer refund':'Confirm commission received' ?></button></form><?php endif; ?>
<a href="support.php?payment=<?= e($payment['id']) ?>">Report a problem</a></div></article><?php endforeach; if(!$payments) echo emptyState('No payments yet'); ?>
<nav><?php if($page>1): ?><a href="?page=<?= $page-1 ?>">Previous</a><?php endif; ?> <a href="?page=<?= $page+1 ?>">Next</a></nav></div></main></div><?php include __DIR__.'/../includes/footer.php'; ?>
