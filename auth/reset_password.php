<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) { redirectToDashboard(); }

$error = $success = '';
// Token arrives via GET (link) or POST (form submit)
$token = $_POST['token'] ?? $_GET['token'] ?? '';

/**
 * Peek at a token without consuming it (so we can show the form).
 */
function tokenIsValid(PDO $pdo, string $raw): bool {
    if ($raw === '') return false;
    $stmt = $pdo->prepare("SELECT id FROM AUTH_TOKEN
        WHERE token_hash=? AND type='password_reset' AND used=0 AND expires_at>NOW() LIMIT 1");
    $stmt->execute([hash('sha256', $raw)]);
    return (bool)$stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $userId = consumeAuthToken($pdo, $token, 'password_reset');
        if ($userId === null) {
            $error = 'This reset link is invalid or has expired. Please request a new one.';
            $token = '';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE USER SET password_hash=? WHERE id=?")->execute([$hash, $userId]);
            $success = 'Your password has been reset. You can now log in.';
        }
    }
}

$valid_token = !$success && tokenIsValid($pdo, $token);
$page_title  = 'Set New Password';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon"><i class="fa-solid fa-lock"></i></div>
            <span class="logo-name">New Password</span>
        </div>

        <?php if ($success): ?>
            <div class="alert-kd alert-kd-success"><i class="fa-solid fa-circle-check"></i> <?= e($success) ?></div>
            <a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                <i class="fa-solid fa-right-to-bracket"></i> Go to Login
            </a>
        <?php elseif (!$valid_token): ?>
            <div class="alert-kd alert-kd-error"><i class="fa-solid fa-circle-exclamation"></i>
                <?= $error ?: 'This reset link is invalid or has expired.' ?>
            </div>
            <a href="/KrishiDisha/auth/forgot_password.php" class="btn-kd btn-kd-outline w-100 justify-content-center mt-2">
                Request a new link
            </a>
        <?php else: ?>
            <h2>Choose a new password</h2>
            <p class="sub">Make it at least 6 characters.</p>
            <?php if ($error): ?>
            <div class="alert-kd alert-kd-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="form-kd" data-validate>
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                    <i class="fa-solid fa-floppy-disk"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
