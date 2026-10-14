<?php
require_once __DIR__.'/../includes/auth_check.php';
require_once __DIR__.'/../includes/mail.php';
$done=false;
if($_SERVER['REQUEST_METHOD']==='POST') {
    $done=true;
    try {
        $email=textInput($_POST['email'] ?? '',150); throttle($pdo,'verify-ip',$_SERVER['REMOTE_ADDR'] ?? '',10); throttle($pdo,'verify-account',$email,3);
        $stmt=$pdo->prepare('SELECT id FROM USER WHERE email=? AND email_verified=0');$stmt->execute([$email]);$user=$stmt->fetch();
        if($user && emailConfigured()) { $token=transaction($pdo,fn()=>createAuthToken($pdo,(int)$user['id'],'email_verify',1440)); sendAccountEmail($email,$token,'email_verify'); }
    } catch(Throwable $e) { error_log('Verification resend unavailable'); }
}
$page_title='Resend verification'; include __DIR__.'/../includes/header.php';
?>
<main id="main-content" tabindex="-1" class="auth-page"><div class="auth-card"><h1>Verify your email</h1>
<?php if($done): ?><p>If the account needs verification and email delivery is available, a link will be sent.</p><?php endif; ?>
<form method="POST"><?= csrfField() ?><label for="email">Email</label><input id="email" class="form-control" type="email" name="email" required><button class="btn-kd btn-kd-primary">Send verification</button></form><a href="login.php">Login</a></div></main>
<?php include __DIR__.'/../includes/footer.php'; ?>
