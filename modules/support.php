<?php
require_once __DIR__.'/../includes/auth_check.php';requireAuth();
$uid=(int)$_SESSION['user_id'];$admin=currentRole()==='admin';
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        if($admin && isset($_POST['resolve'])) {
            $id=(int)$_POST['resolve'];$reply=textInput($_POST['reply'] ?? '',5000);
            $pdo->prepare("UPDATE SUPPORT_REQUEST SET status='resolved',admin_reply=? WHERE id=? AND status='open'")->execute([$reply,$id]);audit($pdo,'support.resolved','SUPPORT_REQUEST',$id);
        } else {
            throttle($pdo,'support-user',(string)$uid,10,3600);$payment=(int)($_POST['payment_id'] ?? 0) ?: null;
            if($payment) { $stmt=$pdo->prepare('SELECT id FROM PAYMENT WHERE id=? AND (payer_id=? OR payee_id=? OR EXISTS(SELECT 1 FROM PAYMENT_SHARE ps WHERE ps.payment_id=PAYMENT.id AND ps.payee_id=?))');$stmt->execute([$payment,$uid,$uid,$uid]);if(!$stmt->fetchColumn()) throw new InvalidArgumentException('Payment not found.'); }
            $pdo->prepare('INSERT INTO SUPPORT_REQUEST(user_id,payment_id,subject,message) VALUES (?,?,?,?)')->execute([$uid,$payment,textInput($_POST['subject'] ?? '',200),textInput($_POST['message'] ?? '',5000)]);
        }flash('success','Support request saved.');
    }catch(Throwable $e) {flash('error',$e instanceof InvalidArgumentException?$e->getMessage():'Support request failed.');}
    header('Location: support.php');exit;
}
$stmt=$pdo->prepare('SELECT s.*,u.name FROM SUPPORT_REQUEST s JOIN USER u ON s.user_id=u.id '.($admin?'':'WHERE s.user_id=?').' ORDER BY s.id DESC');$requests=pagedRows($pdo,$stmt->queryString,$admin?[]:[$uid],'support','Support requests',['subject', 'name', 'message'],true);$page_title='Support';include __DIR__.'/../includes/header.php';
?>
<div class="layout-wrapper"><?php include __DIR__.'/../includes/sidebar.php'; ?><main class="main-content" id="main-content" tabindex="-1"><div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?><h1><?= t('Support') ?></h1><?= renderFlash() ?><p><?= e(env('SUPPORT_CONTACT','Contact the platform administrator')) ?></p>
<form method="POST" class="form-kd"><?= csrfField() ?><input type="hidden" name="payment_id" value="<?= (int)($_GET['payment'] ?? 0) ?>"><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Subject / বিষয়</label><input id="<?= e($fieldId) ?>" class="form-control" name="subject" maxlength="200" required><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Describe the problem / সমস্যার বিবরণ</label><textarea id="<?= e($fieldId) ?>" class="form-control" name="message" maxlength="5000" required></textarea><button class="btn-kd btn-kd-primary">Send / পাঠান</button></form>
<?php foreach($requests as $request): ?><article class="card-kd mt-3"><div class="card-body-kd"><h2 class="h5">#<?= e($request['id']) ?> <?= e($request['subject']) ?></h2><p><?= e($request['name']) ?> · <?= e($request['status']) ?></p><p><?= nl2br(e($request['message'])) ?></p><p><?= nl2br(e($request['admin_reply'])) ?></p><?php if($admin && $request['status']==='open'): ?><form method="POST"><?= csrfField() ?><?php $fieldId=uiFieldId(); ?><label for="<?= e($fieldId) ?>">Resolution reply</label><textarea id="<?= e($fieldId) ?>" class="form-control" name="reply" required maxlength="5000"></textarea><button class="btn-kd btn-kd-primary" name="resolve" value="<?= e($request['id']) ?>">Resolve</button></form><?php endif; ?></div></article><?php endforeach; ?></div></main></div><?php include __DIR__.'/../includes/footer.php'; ?>
