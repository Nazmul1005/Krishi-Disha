<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) { redirectToDashboard(); }

require_once __DIR__ . '/../includes/mail.php';
$error = $success = '';
$roles = ['farmer','dealer','tourist','cook','expert','guide','general'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    throttle($pdo,'register-ip',$_SERVER['REMOTE_ADDR'] ?? '',5,3600);
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? '';
    $application=trim($_POST['application_details'] ?? '');

    if (!emailConfigured()) {
        $error = 'Registration will open when email delivery is configured. Please contact support.';
    } elseif (mb_strlen($name)>100 || strlen($email)>150 || strlen($phone)>20) {
        $error = 'Name, email or phone is too long.';
    } elseif (!$name || !$email || !$password || !$role) {
        $error = 'All required fields must be filled.';
    } elseif (in_array($role,['farmer','dealer','expert','guide','cook'],true) && (mb_strlen($application)<20 || mb_strlen($application)>5000 || !$phone)) {
        $error='Provide a contact phone and at least 20 characters describing your farm, business or qualifications for review.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 12 || strlen($password)>72) {
        $error = 'Password must be between 12 and 72 characters.';
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
                $pdo->prepare("INSERT INTO USER (name,email,password_hash,phone,role,status,application_details) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$name, $email, $hash, $phone, $role, $status,$application]);
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

                // Create verification token and account atomically.
                $verify_token = createAuthToken($pdo, (int)$userId, 'email_verify', 60 * 24);
                $pdo->commit();
                try { sendAccountEmail($email,$verify_token,'email_verify'); } catch(Throwable $delivery) { error_log('Verification delivery failed'); }

                $success = $status === 'pending'
                    ? 'Registration successful! Verify your email and wait for admin approval.'
                    : 'Registration successful! Please check your email, or use Resend verification if delivery is delayed.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
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
        <?php if ($success): ?>
        <a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-primary w-100 justify-content-center mb-3"><i class="fa-solid fa-right-to-bracket"></i> Go to Login</a>
        <?php endif; ?>

        <form id="registrationForm" method="POST" class="form-kd" data-validate><?= csrfField() ?><?= requestKeyField() ?>
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
                        <?= e(ucfirst($r)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label>Password <span style="color:red">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="12–72 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span style="color:red">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>
<label>Farm, business or qualifications (required for provider roles)</label><textarea class="form-control" name="application_details" form="registrationForm" maxlength="5000"></textarea><p class="small">Describe your experience, qualifications or business and how the administrator can verify it. Do not submit sensitive identity numbers in this field.</p><div class="alert-kd alert-kd-info" style="font-size:12px; padding:10px 14px;">
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
