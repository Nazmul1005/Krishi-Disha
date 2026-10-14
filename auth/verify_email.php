<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$token  = $_GET['token'] ?? '';
$ok=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token=$_POST['token'] ?? '';
    $ok=transaction($pdo,function()use($pdo,$token) {
        $id=consumeAuthToken($pdo,$token,'email_verify');
        if($id===null) return false;
        $pdo->prepare('UPDATE USER SET email_verified=1 WHERE id=?')->execute([$id]);
        return true;
    });
}
$page_title = 'Email Verification';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div id="main-content" tabindex="-1" class="auth-page">
    <div class="auth-card" style="text-align:center;">
        <div class="auth-logo" style="justify-content:center;">
            <div class="logo-icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
            <span class="logo-name">KrishiDisha</span>
        </div>
        <?php if ($_SERVER['REQUEST_METHOD']==='GET'): ?>
            <h2>Verify your email</h2>
            <form method="POST"><?= csrfField() ?><input type="hidden" name="token" value="<?= e($token) ?>"><button class="btn-kd btn-kd-primary">Confirm email</button></form>
        <?php elseif ($ok): ?>
            <div style="font-size:56px;margin:10px 0;"><i aria-hidden="true" class="fa-solid fa-circle-check"></i></div>
            <h2>Email verified!</h2>
            <p class="sub">Your email has been confirmed. You can now log in to your account.</p>
            <a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                <i class="fa-solid fa-right-to-bracket"></i> Continue to Login
            </a>
        <?php else: ?>
            <div style="font-size:56px;margin:10px 0;"><i aria-hidden="true" class="fa-solid fa-triangle-exclamation"></i></div>
            <h2>Verification failed</h2>
            <p class="sub">This verification link is invalid or has already been used.</p>
            <a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-outline w-100 justify-content-center mt-2">
                Back to Login
            </a>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
