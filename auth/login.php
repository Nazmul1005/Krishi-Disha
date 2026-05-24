<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) { redirectToDashboard(); }

$error = '';
$selected_role = $_GET['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM USER WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['role'] !== $selected_role) {
                $error = "This account is registered as a " . ucfirst($user['role']) . ". Please go back and select the correct role.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['status']  = $user['status'];
                redirectToDashboard();
            }
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
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
                <a href="?role=<?= $r['id'] ?>" style="display:block; text-decoration:none;">
                    <div style="background:rgba(255,255,255,0.95); border-radius:16px; padding:24px 16px; text-align:center; transition:all 0.25s ease; border-bottom: 4px solid <?= $r['color'] ?>; box-shadow:0 8px 24px rgba(0,0,0,0.15);" onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 32px rgba(0,0,0,0.25)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.15)';">
                        <div style="width:56px; height:56px; margin:0 auto 16px; background:<?= $r['color'] ?>15; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px; color:<?= $r['color'] ?>;">
                            <i class="fa-solid <?= $r['icon'] ?>"></i>
                        </div>
                        <h6 style="color:#1e293b; font-weight:800; font-family:'Nunito',sans-serif; margin:0;"><?= $r['name'] ?></h6>
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
            <span class="logo-name"><?= ucfirst($selected_role) ?> Login</span>
        </div>
        <h2>Welcome Back</h2>
        <p class="sub">Sign in to your <?= ucfirst($selected_role) ?> dashboard</p>

        <?php if ($error): ?>
        <div class="alert-kd alert-kd-error" data-autohide="6000">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($pending_msg): ?>
        <div class="alert-kd alert-kd-warning">
            <i class="fa-solid fa-clock"></i> <?= $pending_msg ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php?role=<?= htmlspecialchars($selected_role) ?>" class="form-kd" data-validate>
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
            <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="divider"></div>
        <p class="text-center" style="font-size:14px; color:var(--text-muted)">
            Don't have an account?
            <a href="/KrishiDisha/auth/register.php" style="color:var(--primary); font-weight:600">Register here</a>
        </p>
        <p class="text-center mt-2" style="font-size:12px; color:var(--text-muted)">
            <a href="login.php" style="color:var(--text-muted)"><i class="fa-solid fa-arrow-left me-1"></i>Back to Role Selection</a>
        </p>

        <?php 
        // Auto-suggest demo credentials based on selected role
        $demo_creds = [
            'admin' => 'admin@krishidisha.com',
            'farmer' => 'karim@farmer.com',
            'dealer' => 'rahim@dealer.com',
            'tourist' => 'john@tourist.com',
            'cook' => 'nasrin@cook.com',
            'expert' => 'anwar@expert.com',
            'guide' => 'rony@guide.com',
            'general' => 'sadia@user.com'
        ];
        ?>
        <div style="background:var(--surface3); border-radius:8px; padding:12px; margin-top:20px; font-size:12px; color:var(--primary-dark)">
            <strong>Demo Credential:</strong><br>
            Email: <code><?= $demo_creds[$selected_role] ?? 'user@krishidisha.com' ?></code><br>
            Password: <code>password</code>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
