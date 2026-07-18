<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(); // any authenticated, approved user (admin bypasses approval)

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* ------------------------------------------------------------
   Role → profile table + editable fields configuration
   ------------------------------------------------------------ */
$roleConfig = [
    'farmer' => ['table' => 'FARMER', 'fields' => [
        'farm_name'       => ['label' => 'Farm Name',            'type' => 'text'],
        'farm_location'   => ['label' => 'Farm Location',        'type' => 'text'],
        'land_size_acres' => ['label' => 'Land Size (acres)',    'type' => 'number', 'step' => '0.01'],
        'soil_type'       => ['label' => 'Soil Type',            'type' => 'text'],
    ]],
    'dealer' => ['table' => 'DEALER', 'fields' => [
        'business_name'    => ['label' => 'Business Name',       'type' => 'text'],
        'license_no'       => ['label' => 'License Number',      'type' => 'text'],
        'business_address' => ['label' => 'Business Address',    'type' => 'text'],
    ]],
    'tourist' => ['table' => 'TOURIST', 'fields' => [
        'nationality'         => ['label' => 'Nationality',          'type' => 'text'],
        'travel_preferences'  => ['label' => 'Travel Preferences',   'type' => 'textarea'],
    ]],
    'cook' => ['table' => 'COOK', 'fields' => [
        'specialty'    => ['label' => 'Cuisine Specialty',       'type' => 'text'],
        'bio'          => ['label' => 'Short Bio',               'type' => 'textarea'],
        'availability' => ['label' => 'Availability',            'type' => 'enum', 'options' => ['available', 'busy']],
    ]],
    'expert' => ['table' => 'EXPERT', 'fields' => [
        'specialization' => ['label' => 'Specialization',        'type' => 'text'],
        'qualification'  => ['label' => 'Qualification',         'type' => 'text'],
        'hourly_rate'    => ['label' => 'Hourly Rate (৳)',       'type' => 'number', 'step' => '0.01'],
        'availability'   => ['label' => 'Availability',          'type' => 'enum', 'options' => ['available', 'busy']],
    ]],
    'guide' => ['table' => 'GUIDE', 'fields' => [
        'languages'        => ['label' => 'Languages Spoken',    'type' => 'text'],
        'experience_years' => ['label' => 'Years of Experience', 'type' => 'number', 'step' => '1'],
        'daily_rate'       => ['label' => 'Daily Rate (৳)',      'type' => 'number', 'step' => '0.01'],
        'availability'     => ['label' => 'Availability',        'type' => 'enum', 'options' => ['available', 'busy']],
    ]],
];

$config = $roleConfig[$role] ?? null;

/* ------------------------------------------------------------
   Handle profile update
   ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') {
        flash('error', 'Name cannot be empty.');
    } else {
        try {
            $pdo->beginTransaction();

            // Common USER fields
            $pdo->prepare("UPDATE USER SET name=?, phone=? WHERE id=?")
                ->execute([$name, $phone, $uid]);
            $_SESSION['name'] = $name;

            // Role-specific fields (upsert into the role table)
            if ($config) {
                $vals = [];
                foreach ($config['fields'] as $col => $meta) {
                    $raw = $_POST[$col] ?? '';
                    if ($meta['type'] === 'number') {
                        $vals[$col] = ($raw === '') ? null : (float)$raw;
                    } elseif ($meta['type'] === 'enum') {
                        $vals[$col] = in_array($raw, $meta['options'], true) ? $raw : $meta['options'][0];
                    } else {
                        $vals[$col] = trim($raw);
                    }
                }

                // Ensure a role row exists (defensive for legacy accounts)
                $exists = $pdo->prepare("SELECT id FROM {$config['table']} WHERE user_id=?");
                $exists->execute([$uid]);
                if (!$exists->fetch()) {
                    $pdo->prepare("INSERT INTO {$config['table']} (user_id) VALUES (?)")->execute([$uid]);
                }

                $sets   = implode(', ', array_map(fn($c) => "$c=?", array_keys($vals)));
                $params = array_values($vals);
                $params[] = $uid;
                $pdo->prepare("UPDATE {$config['table']} SET $sets WHERE user_id=?")->execute($params);
            }

            $pdo->commit();
            flash('success', 'Your profile has been updated.');
        } catch (Exception $ex) {
            $pdo->rollBack();
            error_log('Profile update failed: ' . $ex->getMessage());
            flash('error', 'Could not update your profile. Please try again.');
        }
    }
    header('Location: /KrishiDisha/modules/profile.php');
    exit;
}

/* ------------------------------------------------------------
   Load current data
   ------------------------------------------------------------ */
$user = $pdo->prepare("SELECT * FROM USER WHERE id=?");
$user->execute([$uid]);
$user = $user->fetch();

$profile = [];
if ($config) {
    $stmt = $pdo->prepare("SELECT * FROM {$config['table']} WHERE user_id=?");
    $stmt->execute([$uid]);
    $profile = $stmt->fetch() ?: [];
}

$page_title = 'My Profile';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title"><i class="fa-solid fa-id-card me-2" style="color:var(--primary);"></i>My Profile</div>
        </div>
        <div class="topbar-actions">
            <span class="badge-kd badge-info"><?= e(ucfirst($role)) ?></span>
        </div>
    </div>

    <div class="page-body">
        <?= renderFlash() ?>

        <div class="row g-4">
            <!-- Identity summary card -->
            <div class="col-lg-4">
                <div class="card-kd">
                    <div class="card-body-kd" style="text-align:center;">
                        <div style="width:96px;height:96px;margin:0 auto 14px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;font-size:44px;color:#fff;">
                            <i class="fa-solid fa-circle-user"></i>
                        </div>
                        <h5 style="margin-bottom:2px;"><?= e($user['name']) ?></h5>
                        <div style="font-size:13px;color:var(--text-muted);"><?= e($user['email']) ?></div>
                        <div class="divider"></div>
                        <div style="display:flex;flex-direction:column;gap:8px;text-align:left;font-size:13px;color:var(--text-muted);">
                            <div><i class="fa-solid fa-phone me-2" style="color:var(--primary);"></i><?= $user['phone'] ? e($user['phone']) : 'No phone added' ?></div>
                            <div><i class="fa-solid fa-user-tag me-2" style="color:var(--primary);"></i><?= e(ucfirst($role)) ?></div>
                            <div><i class="fa-solid fa-circle-check me-2" style="color:var(--primary);"></i><?= e(ucfirst($user['status'])) ?></div>
                            <div><i class="fa-solid fa-calendar me-2" style="color:var(--primary);"></i>Joined <?= date('d M Y', strtotime($user['created_at'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit form -->
            <div class="col-lg-8">
                <div class="card-kd">
                    <div class="card-header-kd"><h5><i class="fa-solid fa-pen-to-square me-2" style="color:var(--primary);"></i>Edit Profile</h5></div>
                    <div class="card-body-kd">
                        <form method="POST" class="form-kd" data-validate>
                            <?= csrfField() ?>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <div class="form-group">
                                    <label>Full Name <span style="color:var(--danger)">*</span></label>
                                    <input type="text" name="name" class="form-control" required value="<?= e($user['name']) ?>">
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>" placeholder="01XXXXXXXXX">
                                </div>
                            </div>

                            <?php if ($config): ?>
                            <div class="divider"></div>
                            <div class="nav-section-label" style="color:var(--primary-dark);padding-left:0;margin-bottom:8px;"><?= e(ucfirst($role)) ?> Details</div>

                            <?php
                            $fieldKeys = array_keys($config['fields']);
                            $i = 0;
                            while ($i < count($fieldKeys)):
                                $col  = $fieldKeys[$i];
                                $meta = $config['fields'][$col];
                                $val  = $profile[$col] ?? '';
                                $isWide = $meta['type'] === 'textarea';
                            ?>
                            <?php if ($isWide): ?>
                            <div class="form-group">
                                <label><?= e($meta['label']) ?></label>
                                <textarea name="<?= e($col) ?>" class="form-control" rows="3"><?= e($val) ?></textarea>
                            </div>
                            <?php $i++; ?>
                            <?php else: ?>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <?php
                                // group up to two non-textarea fields per row
                                for ($j = 0; $j < 2 && $i < count($fieldKeys); $j++):
                                    $col  = $fieldKeys[$i];
                                    $meta = $config['fields'][$col];
                                    if ($meta['type'] === 'textarea') break;
                                    $val  = $profile[$col] ?? '';
                                ?>
                                <div class="form-group">
                                    <label><?= e($meta['label']) ?></label>
                                    <?php if ($meta['type'] === 'enum'): ?>
                                    <select name="<?= e($col) ?>" class="form-control">
                                        <?php foreach ($meta['options'] as $opt): ?>
                                        <option value="<?= e($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php elseif ($meta['type'] === 'number'): ?>
                                    <input type="number" step="<?= e($meta['step'] ?? '1') ?>" min="0" name="<?= e($col) ?>" class="form-control" value="<?= e($val) ?>">
                                    <?php else: ?>
                                    <input type="text" name="<?= e($col) ?>" class="form-control" value="<?= e($val) ?>">
                                    <?php endif; ?>
                                </div>
                                <?php $i++; endfor; ?>
                            </div>
                            <?php endif; ?>
                            <?php endwhile; ?>

                            <?php if (in_array($role, ['expert', 'guide'])): ?>
                            <div class="alert-kd alert-kd-info" style="font-size:12px;padding:10px 14px;">
                                <i class="fa-solid fa-circle-info"></i>
                                Your rate is used to calculate consultation fees. Set it so clients can book you correctly.
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="alert-kd alert-kd-info" style="font-size:13px;">
                                <i class="fa-solid fa-circle-info"></i>
                                This account type only maintains basic contact details.
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btn-kd btn-kd-primary mt-2">
                                <i class="fa-solid fa-floppy-disk"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
