<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) { redirectToDashboard(); }

$done = false;
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $done  = true; // always show the same confirmation (avoid user enumeration)

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT id FROM USER WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = createAuthToken($pdo, (int)$user['id'], 'password_reset', 60);
            // No SMTP in this environment — surface the link directly (dev mode).
            $reset_link = baseUrl() . '/KrishiDisha/auth/reset_password.php?token=' . $token;
        }
    }
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
            <p class="sub">If an account exists for that email, we've sent a link to reset the password. The link expires in 1 hour.</p>

            <?php if ($reset_link): ?>
            <div class="alert-kd alert-kd-info" style="font-size:12px;word-break:break-all;">
                <i class="fa-solid fa-flask"></i>
                <div>
                    <strong>Development mode:</strong> email delivery isn't configured, so use this link:
                    <br><a href="<?= e($reset_link) ?>" style="color:var(--primary);font-weight:600;"><?= e($reset_link) ?></a>
                </div>
            </div>
            <?php endif; ?>

            <div class="divider"></div>
            <p class="text-center" style="font-size:14px;color:var(--text-muted)">
                <a href="/KrishiDisha/auth/login.php" style="color:var(--primary);font-weight:600"><i class="fa-solid fa-arrow-left me-1"></i>Back to Login</a>
            </p>
        <?php else: ?>
            <h2>Forgot your password?</h2>
            <p class="sub">Enter your account email and we'll send you a reset link.</p>
            <form method="POST" class="form-kd" data-validate>
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
