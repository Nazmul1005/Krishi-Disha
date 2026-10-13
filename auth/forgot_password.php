<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) { redirectToDashboard(); }

require_once __DIR__ . '/../includes/mail.php';
$done=false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $done=true;
    try {
        $email=textInput($_POST['email'] ?? '',150);
        throttle($pdo,'recovery-ip',$_SERVER['REMOTE_ADDR'] ?? '',10);
        throttle($pdo,'recovery-account',$email,3);
        if(emailConfigured() && filter_var($email,FILTER_VALIDATE_EMAIL)) {
            $stmt=$pdo->prepare('SELECT id FROM USER WHERE email=?'); $stmt->execute([$email]); $user=$stmt->fetch();
            if($user) {
                $token=transaction($pdo,fn()=>createAuthToken($pdo,(int)$user['id'],'password_reset',60));
                sendAccountEmail($email,$token,'password_reset');
            }
        }
    } catch(Throwable $e) { error_log('Recovery request could not be delivered'); }
}
$page_title = 'Forgot Password';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon"><i class="fa-solid fa-key"></i></div>
            <span class="logo-name">Reset Password</span>
        </div>

        <?php if ($done): ?>
            <h2>Check your inbox</h2>
            <p class="sub">If an account exists for that email, a reset link will be sent when email delivery is available. The link expires in 1 hour.</p>

            <div class="divider"></div>
            <p class="text-center" style="font-size:14px;color:var(--text-muted)">
                <a href="/KrishiDisha/auth/login.php" style="color:var(--primary);font-weight:600"><i class="fa-solid fa-arrow-left me-1"></i>Back to Login</a>
            </p>
        <?php else: ?>
            <h2>Forgot your password?</h2>
            <p class="sub">Enter your account email and we'll send you a reset link.</p>
            <form method="POST" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="email"><i class="fa-solid fa-envelope me-1"></i>Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
                <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                    <i class="fa-solid fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            <div class="divider"></div>
            <p class="text-center" style="font-size:14px;color:var(--text-muted)">
                <a href="/KrishiDisha/auth/login.php" style="color:var(--primary);font-weight:600"><i class="fa-solid fa-arrow-left me-1"></i>Back to Login</a>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
