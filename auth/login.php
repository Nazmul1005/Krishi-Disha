<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) { redirectToDashboard(); }

require_once __DIR__ . '/../includes/mfa.php';
$error = '';
$selected_role = $_POST['role'] ?? $_GET['role'] ?? '';
$allowed_roles=['admin','farmer','dealer','tourist','cook','expert','guide','general'];
if(!is_string($selected_role) || ($selected_role!=='' && !in_array($selected_role,$allowed_roles,true))) { http_response_code(400); die('Invalid role.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email=textInput($_POST['email'] ?? '',150);
        throttle($pdo,'login-ip',$_SERVER['REMOTE_ADDR'] ?? '',30);
        throttle($pdo,'login-account',$email,10);
        $password=$_POST['password'] ?? '';
        $stmt=$pdo->prepare('SELECT * FROM USER WHERE email=?'); $stmt->execute([$email]); $user=$stmt->fetch();
        $valid=is_string($password) && password_verify($password,$user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
        if(!$user || !$valid || $user['role']!==$selected_role) $error='Invalid email or password.';
        elseif($user['status']!=='approved') $error='Your account is pending approval or suspended. Contact support.';
        elseif(!(int)$user['email_verified']) $error='Verify your email first. Use the verification resend page.';
        elseif($user['role']==='admin' || $user['mfa_secret']) {
            session_regenerate_id(true); $_SESSION['mfa_pending']=['id'=>(int)$user['id'],'version'=>(int)$user['auth_version'],'expires'=>time()+300];
            header('Location: /KrishiDisha/auth/mfa.php'); exit;
        } else { establishLogin($user); redirectToDashboard(); }
    } catch(Throwable $e) { error_log('Login attempt failed: '.get_class($e)); $error=$e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to sign in. Please try again later.'; }
}

$pending_msg = '';
if (isset($_GET['error']) && $_GET['error'] === 'pending') {
    $pending_msg = 'Your account is pending admin approval. Please wait.';
}
$page_title = $selected_role ? ucfirst($selected_role) . ' Login' : 'Select Role';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-page">
    <?php if (!$selected_role): ?>
    <!-- STEP 1: ROLE SELECTION -->
    <div style="max-width: 900px; width: 100%; position: relative; z-index: 1;">
        <div class="text-center mb-5">
            <div class="auth-logo" style="margin-bottom: 12px;">
                <div class="logo-icon"><i class="fa-solid fa-leaf"></i></div>
                <span class="logo-name" style="color:#fff;">KrishiDisha</span>
            </div>
            <h2 style="color:#fff; font-size:32px;">Welcome to KrishiDisha</h2>
            <p style="color:rgba(255,255,255,0.7);">Please select your role to log in</p>
        </div>

        <div class="row g-3">
            <?php
            $roles = [
                ['id'=>'admin',   'name'=>'Admin',        'icon'=>'fa-shield-halved', 'color'=>'#dc2626'],
                ['id'=>'farmer',  'name'=>'Farmer',       'icon'=>'fa-tractor',       'color'=>'#16a34a'],
                ['id'=>'dealer',  'name'=>'Dealer',       'icon'=>'fa-boxes-stacked', 'color'=>'#2563eb'],
                ['id'=>'tourist', 'name'=>'Tourist',      'icon'=>'fa-umbrella-beach','color'=>'#0891b2'],
                ['id'=>'cook',    'name'=>'Cook',         'icon'=>'fa-utensils',      'color'=>'#ea580c'],
                ['id'=>'expert',  'name'=>'Expert',       'icon'=>'fa-user-doctor',   'color'=>'#7c3aed'],
                ['id'=>'guide',   'name'=>'Guide',        'icon'=>'fa-route',         'color'=>'#d97706'],
                ['id'=>'general', 'name'=>'General User', 'icon'=>'fa-user',          'color'=>'#475569'],
            ];
            foreach ($roles as $r):
            ?>
            <div class="col-6 col-md-3">
                <a href="?role=<?= e($r['id']) ?>" style="display:block; text-decoration:none;">
                    <div style="background:rgba(255,255,255,0.95); border-radius:16px; padding:24px 16px; text-align:center; transition:all 0.25s ease; border-bottom: 4px solid <?= e($r['color']) ?>; box-shadow:0 8px 24px rgba(0,0,0,0.15);" onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 32px rgba(0,0,0,0.25)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.15)';">
                        <div style="width:56px; height:56px; margin:0 auto 16px; background:<?= e($r['color']) ?>15; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px; color:<?= e($r['color']) ?>;">
                            <i class="fa-solid <?= e($r['icon']) ?>"></i>
                        </div>
                        <h6 style="color:#1e293b; font-weight:800; font-family:'Nunito',sans-serif; margin:0;"><?= e($r['name']) ?></h6>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center mt-5" style="font-size:14px; color:rgba(255,255,255,0.6)">
            <a href="/KrishiDisha/index.php" style="color:inherit;"><i class="fa-solid fa-arrow-left me-1"></i>Back to Home</a>
        </p>
    </div>

    <?php else: ?>
    <!-- STEP 2: SPECIFIC ROLE LOGIN -->
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon"><i class="fa-solid fa-user-lock"></i></div>
            <span class="logo-name"><?= e(ucfirst($selected_role)) ?> Login</span>
        </div>
        <h2>Welcome Back</h2>
        <p class="sub">Sign in to your <?= e(ucfirst($selected_role)) ?> dashboard</p>

        <?php if ($error): ?>
        <div class="alert-kd alert-kd-error">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($pending_msg): ?>
        <div class="alert-kd alert-kd-warning">
            <i class="fa-solid fa-clock"></i> <?= $pending_msg ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php?role=<?= htmlspecialchars($selected_role) ?>" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
            <input type="hidden" name="role" value="<?= htmlspecialchars($selected_role) ?>">

            <div class="form-group">
                <label for="email"><i class="fa-solid fa-envelope me-1"></i>Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="your@email.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password"><i class="fa-solid fa-lock me-1"></i>Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Enter password" required>
            </div>
            <div style="text-align:right;margin-top:-8px;margin-bottom:8px;">
                <a href="/KrishiDisha/auth/forgot_password.php" style="font-size:12px;color:var(--primary);font-weight:600;">Forgot password?</a>
            </div>
            <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <p><a href="resend_verification.php">Resend verification email</a></p><div class="divider"></div>
        <p class="text-center" style="font-size:14px; color:var(--text-muted)">
            Don't have an account?
            <a href="/KrishiDisha/auth/register.php" style="color:var(--primary); font-weight:600">Register here</a>
        </p>
        <p class="text-center mt-2" style="font-size:12px; color:var(--text-muted)">
            <a href="login.php" style="color:var(--text-muted)"><i class="fa-solid fa-arrow-left me-1"></i>Back to Role Selection</a>
        </p>

    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
