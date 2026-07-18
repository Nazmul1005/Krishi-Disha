<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$token  = $_GET['token'] ?? '';
$userId = consumeAuthToken($pdo, $token, 'email_verify');
$ok     = false;

if ($userId !== null) {
    $pdo->prepare("UPDATE USER SET email_verified = 1 WHERE id = ?")->execute([$userId]);
    $ok = true;
}

$page_title = 'Email Verification';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="auth-page">
    <div class="auth-card" style="text-align:center;">
        <div class="auth-logo" style="justify-content:center;">
            <div class="logo-icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
            <span class="logo-name">KrishiDisha</span>
        </div>
        <?php if ($ok): ?>
            <div style="font-size:56px;margin:10px 0;">✅</div>
            <h2>Email verified!</h2>
            <p class="sub">Your email has been confirmed. You can now log in to your account.</p>
            <a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                <i class="fa-solid fa-right-to-bracket"></i> Continue to Login
            </a>
        <?php else: ?>
            <div style="font-size:56px;margin:10px 0;">⚠️</div>
            <h2>Verification failed</h2>
            <p class="sub">This verification link is invalid or has already been used.</p>
            <a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-outline w-100 justify-content-center mt-2">
                Back to Login
            </a>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
