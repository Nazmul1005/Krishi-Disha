<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) { redirectToDashboard(); }

$error = $success = '';
$roles = ['farmer','dealer','tourist','cook','expert','guide','general'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (!$name || !$email || !$password || !$role) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, $roles)) {
        $error = 'Invalid role selected.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM USER WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $status = in_array($role, ['farmer','dealer','cook','expert','guide']) ? 'pending' : 'approved';

            $pdo->beginTransaction();
            try {
                $pdo->prepare("INSERT INTO USER (name,email,password_hash,phone,role,status) VALUES (?,?,?,?,?,?)")
                    ->execute([$name, $email, $hash, $phone, $role, $status]);
                $userId = $pdo->lastInsertId();

                // Create role-specific record
                match($role) {
                    'farmer'  => $pdo->prepare("INSERT INTO FARMER (user_id) VALUES (?)")->execute([$userId]),
                    'dealer'  => $pdo->prepare("INSERT INTO DEALER (user_id) VALUES (?)")->execute([$userId]),
                    'tourist' => $pdo->prepare("INSERT INTO TOURIST (user_id) VALUES (?)")->execute([$userId]),
                    'cook'    => $pdo->prepare("INSERT INTO COOK (user_id) VALUES (?)")->execute([$userId]),
                    'expert'  => $pdo->prepare("INSERT INTO EXPERT (user_id) VALUES (?)")->execute([$userId]),
                    'guide'   => $pdo->prepare("INSERT INTO GUIDE (user_id) VALUES (?)")->execute([$userId]),
                    default   => null
                };

                $pdo->commit();
                $success = $status === 'pending'
                    ? 'Registration successful! Your account is pending admin approval.'
                    : 'Registration successful! You can now log in.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
$page_title = 'Register';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="auth-page" style="align-items:flex-start; padding-top:40px;">
    <div class="auth-card" style="max-width:520px;">
        <div class="auth-logo">
            <div class="logo-icon"><i class="fa-solid fa-leaf"></i></div>
            <span class="logo-name">KrishiDisha</span>
        </div>
        <h2>Create Account</h2>
        <p class="sub">Join the agricultural intelligence platform</p>

        <?php if ($error): ?>
        <div class="alert-kd alert-kd-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert-kd alert-kd-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="form-kd" data-validate>
            <div class="form-group">
                <label>Full Name <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label>Email <span style="color:red">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>I am a... <span style="color:red">*</span></label>
                <select name="role" class="form-control" required>
                    <option value="">Select your role</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= (($_POST['role'] ?? '') === $r) ? 'selected' : '' ?>>
                        <?= ucfirst($r) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label>Password <span style="color:red">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span style="color:red">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>
            <div class="alert-kd alert-kd-info" style="font-size:12px; padding:10px 14px;">
                <i class="fa-solid fa-info-circle"></i>
                Roles like Farmer, Dealer, Cook, Expert & Guide require admin approval before login.
            </div>
            <button type="submit" class="btn-kd btn-kd-primary w-100 justify-content-center mt-2">
                <i class="fa-solid fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="divider"></div>
        <p class="text-center" style="font-size:14px; color:var(--text-muted)">
            Already have an account?
            <a href="/KrishiDisha/auth/login.php" style="color:var(--primary); font-weight:600">Sign in</a>
        </p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
