<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE d.name LIKE ? OR d.symptoms LIKE ? OR d.affected_part LIKE ?" : "";
$params = $search ? ["%$search%","%$search%","%$search%"] : [];

$stmt = $pdo->prepare("
    SELECT d.*, GROUP_CONCAT(c.name SEPARATOR ', ') as affected_crops
    FROM DISEASE d
    LEFT JOIN CROP_DISEASE cd ON d.id=cd.disease_id
    LEFT JOIN CROP c ON cd.crop_id=c.id
    $where
    GROUP BY d.id
    ORDER BY d.name ASC
");
$stmt->execute($params);
$diseases = $stmt->fetchAll();

$page_title = 'Disease Detection';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php $isAuth = isLoggedIn(); ?>
<?php if ($isAuth): ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
<?php else: ?>
<nav class="kd-navbar"><div class="container"><div class="d-flex align-items-center justify-content-between"><a href="/KrishiDisha/index.php" class="navbar-brand" style="display:flex;align-items:center;gap:10px;font-family:'Nunito',sans-serif;font-size:20px;font-weight:800;color:#fff;"><div style="width:34px;height:34px;background:linear-gradient(135deg,#52b788,#2d6a4f);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-leaf" style="color:#fff;font-size:15px;"></i></div>KrishiDisha</a><a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-primary" style="padding:8px 18px;font-size:13px;">Login</a></div></div></nav>
<div style="padding-top:70px;">
<?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button><?php endif; ?>
        <div class="topbar-title"><i class="fa-solid fa-bug me-2" style="color:var(--danger);"></i>Crop Disease Detection</div>
    </div>
    <div class="topbar-actions"><span class="badge-kd badge-danger"><?= count($diseases) ?> diseases tracked</span></div>
</div>

<div class="page-body">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" placeholder="Search disease name, symptoms, affected part..." value="<?= htmlspecialchars($search) ?>" style="flex:2;">
        <button type="submit" class="btn-kd btn-kd-primary"><i class="fa-solid fa-search"></i> Search</button>
        <a href="disease.php" class="btn-kd btn-kd-outline">Reset</a>
    </form>

    <div class="row g-4">
        <?php foreach ($diseases as $d): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-kd" style="border-left:4px solid var(--danger);">
                <div class="card-body-kd">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:#fee2e2;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🦠</div>
                        <div>
                            <h5 style="font-size:15px;margin-bottom:4px;"><?= htmlspecialchars($d['name']) ?></h5>
                            <span class="badge-kd badge-danger" style="font-size:10px;"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($d['affected_part'] ?? 'N/A') ?></span>
                        </div>
                    </div>

                    <?php if ($d['affected_crops']): ?>
                    <div style="margin-bottom:12px;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px;">Affects</div>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                            <?php foreach (explode(', ', $d['affected_crops']) as $crop): ?>
                            <span class="badge-kd badge-info" style="font-size:10px;"><?= htmlspecialchars(trim($crop)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="margin-bottom:12px;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px;">Symptoms</div>
                        <p style="font-size:13px;color:var(--text-muted);line-height:1.5;margin:0;"><?= htmlspecialchars($d['symptoms'] ?? 'N/A') ?></p>
                    </div>

                    <div style="background:#f0fdf4;border-radius:8px;padding:12px;border-left:3px solid var(--primary-light);">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:4px;"><i class="fa-solid fa-shield-halved me-1"></i>Solution</div>
                        <p style="font-size:13px;color:var(--text);line-height:1.5;margin:0;"><?= htmlspecialchars($d['solution'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($diseases)): ?>
        <div class="col-12"><div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;">🔍</div><h4 style="color:var(--text-muted);">No diseases found</h4></div></div></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAuth): ?></div></div><?php else: ?></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
