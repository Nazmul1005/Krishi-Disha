<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$experts=pagedRows($pdo,"
    SELECT e.*, u.name, u.email, u.phone,
           (SELECT COUNT(*) FROM CONSULTATION c WHERE c.provider_id=u.id AND c.status='completed') as completed_sessions
    FROM EXPERT e
    JOIN USER u ON e.user_id=u.id
    WHERE u.status='approved'
    ORDER BY e.hourly_rate ASC
",[],'experts','Expert providers',['name', 'specialization'],false);

$page_title = 'Expert Consultation';
$isAuth = isLoggedIn();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if ($isAuth): ?><div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content" id="main-content" tabindex="-1">
<?php else: ?><div style=""><?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><?php endif; ?>
        <h1 class="topbar-title"><i class="fa-solid fa-user-doctor me-2" style="color:#7c3aed;"></i>Expert Consultation</h1>
    </div>
</div>

<div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?>
    <!-- Hero -->
    <div style="background:linear-gradient(135deg,#4c1d95,#7c3aed);border-radius:var(--radius);padding:36px;color:#fff;margin-bottom:28px;">
        <h3 style="color:#fff;margin-bottom:8px;">Connect with Agricultural Experts</h3>
        <p style="color:rgba(255,255,255,0.8);font-size:15px;">Get personalized advice on soil health, pest management, crop diseases, and farm productivity from approved providers. Check qualifications with the platform before booking.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($experts as $ex): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-kd">
                <div class="card-body-kd">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:56px;height:56px;background:linear-gradient(135deg,#4c1d95,#7c3aed);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;flex-shrink:0;"><i aria-hidden="true" class="fa-solid fa-user-doctor"></i></div>
                        <div>
                            <div style="font-weight:800;font-family:'Nunito',sans-serif;font-size:16px;"><?= htmlspecialchars($ex['name']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($ex['specialization'] ?? '') ?></div>
                            <span class="badge-kd <?= ($ex['availability']??'') === 'available' ? 'badge-success' : 'badge-warning' ?>" style="font-size:10px;margin-top:4px;"><?= ucfirst($ex['availability'] ?? 'N/A') ?></span>
                        </div>
                    </div>

                    <div style="background:var(--surface3);border-radius:8px;padding:12px;margin-bottom:14px;">
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;"><i class="fa-solid fa-graduation-cap me-1"></i><?= htmlspecialchars($ex['qualification'] ?? 'N/A') ?></div>
                        <div style="font-size:12px;color:var(--text-muted);"><i class="fa-solid fa-circle-check me-1" style="color:var(--primary);"></i><?= e($ex['completed_sessions']) ?> consultations completed</div>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div style="font-size:24px;font-weight:800;color:#7c3aed;font-family:'Nunito',sans-serif;">৳<?= number_format($ex['hourly_rate'] ?? 0) ?></div>
                        <div style="font-size:12px;color:var(--text-muted);">per hour</div>
                    </div>

                    <?php if ($isAuth && in_array(currentRole(), ['farmer', 'general', 'tourist'])): ?>
                    <a href="/KrishiDisha/modules/book_consultation.php?provider=<?= e($ex['user_id']) ?>" class="btn-kd w-100 justify-content-center" style="background:linear-gradient(135deg,#4c1d95,#7c3aed);color:#fff;padding:10px;">
                        <i class="fa-solid fa-calendar-plus"></i> Book Session
                    </a>
                    <?php elseif (!$isAuth): ?>
                    <a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-outline w-100 justify-content-center">Login to Book</a>
                    <?php else: ?>
                    <div class="btn-kd w-100 justify-content-center" style="background:var(--surface3);color:var(--text-muted);cursor:default;">Available for Booking</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($experts)): ?><div class="col-12"><div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;"><i aria-hidden="true" class="fa-solid fa-user-doctor"></i></div><p style="color:var(--text-muted);">No experts available yet.</p></div></div></div><?php endif; ?>
    </div>
</div>

<?php if ($isAuth): ?></div></div><?php else: ?></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
