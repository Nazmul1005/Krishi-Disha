<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$search = trim($_GET['q'] ?? '');
$tours = $pdo->prepare("
    SELECT ft.*, u.name as farmer_name, f.farm_location,
           (SELECT COUNT(*) FROM TOUR_BOOKING tb WHERE tb.tour_id=ft.id AND tb.status='confirmed') as booked_count
    FROM FARM_TOUR ft
    JOIN FARMER f ON ft.farmer_id=f.id
    JOIN USER u ON f.user_id=u.id
    WHERE u.status='approved' AND u.email_verified=1 AND (ft.status='active' OR (ft.status='pending' AND " . (isLoggedIn() && currentRole()==='admin' ? '1=1' : '0=1') . "))
    " . ($search ? "AND (ft.title LIKE ? OR ft.location LIKE ?)" : "") . "
    ORDER BY ft.id DESC
");
$tour_params = [];
if ($search) { $tour_params[] = "%$search%"; $tour_params[] = "%$search%"; }
$tours=pagedRows($pdo,$tours->queryString,$tour_params,'tours','Farm visits',['title', 'location', 'farmer_name'],true);

$guides = $pdo->query("SELECT g.*, u.name FROM GUIDE g JOIN USER u ON g.user_id=u.id WHERE g.availability='available' AND u.status='approved' AND u.email_verified=1")->fetchAll();

$page_title = 'Agri-Tourism';
$isAuth = isLoggedIn();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if ($isAuth): ?><div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content" id="main-content" tabindex="-1">
<?php else: ?><div style=""><?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><?php endif; ?>
        <h1 class="topbar-title"><i class="fa-solid fa-umbrella-beach me-2" style="color:#126078;"></i>Agri-Tourism</h1>
    </div>
    <div class="topbar-actions">
        <span class="badge-kd badge-info"><?= count($tours) ?> farms available</span>
        <?php if ($isAuth && currentRole() === 'admin'): ?>
        <a href="/KrishiDisha/admin/manage_content.php?tab=tours" class="btn-kd btn-kd-primary" style="padding:6px 14px;font-size:12px;">
            <i class="fa-solid fa-pen-to-square"></i> Manage Tours
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?>
    <!-- Search Bar -->
    <form class="filter-bar" method="GET" style="margin-bottom:20px;">
        <input type="text" name="q" placeholder="Search tours by title or location..." value="<?= htmlspecialchars($search) ?>" style="flex:2;">
        <button type="submit" class="btn-kd btn-kd-primary"><i class="fa-solid fa-search"></i> Search</button>
        <a href="tourism.php" class="btn-kd btn-kd-outline">Reset</a>
    </form>
    <!-- Hero banner -->
    <div style="background:linear-gradient(135deg,#0c4a6e,#126078);border-radius:var(--radius);padding:36px;color:#fff;margin-bottom:28px;position:relative;overflow:hidden;">
        <div style="position:absolute;right:-20px;bottom:-20px;font-size:120px;opacity:0.1;"><i aria-hidden="true" class="fa-solid fa-tractor"></i></div>
        <h3 style="color:#fff;margin-bottom:8px;">Discover Bangladesh's Rural Beauty</h3>
        <p style="color:rgba(255,255,255,0.8);margin-bottom:20px;font-size:15px;">Book an immersive farm tour, hire a local guide, and taste authentic farm-to-table food.</p>
        <?php if (!$isAuth): ?>
        <a href="/KrishiDisha/auth/register.php?role=tourist" class="btn-kd btn-kd-primary"><i class="fa-solid fa-user-plus"></i> Register as Tourist</a>
        <?php endif; ?>
    </div>

    <!-- Farm Tours -->
    <h5 style="font-family:'Nunito',sans-serif;font-weight:800;color:var(--primary-dark);margin-bottom:16px;"><i aria-hidden="true" class="fa-solid fa-wheat-awn"></i> Available Farm Tours</h5>
    <div class="row g-4 mb-5">
        <?php foreach ($tours as $t): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-kd">
                <div style="height:180px;overflow:hidden;position:relative;">
                    <?php if (!empty($t['image']) && mediaAvailable($t['image'])): ?>
                    <img width="480" height="320" decoding="async" alt="<?= e($t['title']) ?>" loading="lazy" src="/KrishiDisha/<?= htmlspecialchars($t['image']) ?>" style="width:100%;height:180px;object-fit:cover;">
                    <?php else: ?>
                    <div style="height:180px;background:linear-gradient(135deg,var(--primary-dark),var(--primary));display:flex;align-items:center;justify-content:center;font-size:72px;"><i aria-hidden="true" class="fa-solid fa-leaf"></i></div>
                    <?php endif; ?>
                    <?php if ($isAuth && currentRole() === 'admin'): ?>
                    <div style="position:absolute;top:8px;right:8px;display:flex;gap:4px;">
                        <a href="/KrishiDisha/admin/manage_content.php?tab=tours&edit=<?= e($t['id']) ?>" class="btn-kd btn-kd-outline" style="padding:4px 8px;font-size:10px;background:rgba(255,255,255,0.9);"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" action="/KrishiDisha/admin/manage_content.php?tab=tours" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="delete_tour" value="<?= e($t['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger" style="padding:4px 8px;font-size:10px;" data-confirm="Delete this tour?"><i class="fa-solid fa-trash"></i></button></form>
                    </div>
                    <?php endif; ?>
                    <?php if ($t['status'] === 'pending'): ?>
                    <span class="badge-kd badge-warning" style="position:absolute;top:8px;left:8px;font-size:10px;">Pending Approval</span>
                    <?php endif; ?>
                </div>
                <div class="card-body-kd">
                    <h5 style="margin-bottom:6px;"><?= htmlspecialchars($t['title']) ?></h5>
                    <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">
                        <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($t['location']) ?>
                        · By <?= htmlspecialchars($t['farmer_name']) ?>
                    </div>
                    <p style="font-size:13px;color:var(--text-muted);line-height:1.5;margin-bottom:14px;"><?= mb_substr(htmlspecialchars($t['description']),0,120) ?>...</p>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <div>
                            <div style="font-size:20px;font-weight:800;color:var(--primary);">৳<?= number_format($t['price_per_day']) ?><span style="font-size:12px;font-weight:400;color:var(--text-muted);">/group/day</span></div>
                            <div style="font-size:12px;color:var(--text-muted);"><i class="fa-solid fa-users me-1"></i>Max <?= e($t['capacity']) ?> visitors</div>
                        </div>
                        <span class="badge-kd <?= $t['status']==='active' ? 'badge-success' : 'badge-muted' ?>"><?= e(ucfirst($t['status'])) ?></span>
                    </div>
                    <?php if ($isAuth && currentRole() === 'tourist'): ?>
                    <a href="/KrishiDisha/tourist/tours.php?book=<?= e($t['id']) ?>" class="btn-kd btn-kd-primary w-100 justify-content-center">
                        <i class="fa-solid fa-calendar-plus"></i> Book Tour
                    </a>
                    <?php elseif (!$isAuth): ?>
                    <a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-outline w-100 justify-content-center">Login to Book</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($tours)): ?><div class="col-12"><div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;"><i aria-hidden="true" class="fa-solid fa-tractor"></i></div><p style="color:var(--text-muted);">No farm tours available yet.</p></div></div></div><?php endif; ?>
    </div>

    <!-- Available Guides -->
    <?php if (!empty($guides)): ?>
    <h5 style="font-family:'Nunito',sans-serif;font-weight:800;color:var(--primary-dark);margin-bottom:16px;"><i aria-hidden="true" class="fa-solid fa-map"></i> Available Tour Guides</h5>
    <div class="row g-3">
        <?php foreach ($guides as $g): ?>
        <div class="col-md-6 col-lg-3">
            <div class="card-kd">
                <div class="card-body-kd text-center">
                    <div style="font-size:48px;margin-bottom:10px;"><i aria-hidden="true" class="fa-solid fa-person-walking"></i></div>
                    <div style="font-weight:700;font-size:15px;margin-bottom:4px;"><?= htmlspecialchars($g['name']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;"><?= htmlspecialchars($g['languages'] ?? '') ?></div>
                    <div style="display:flex;justify-content:center;gap:10px;margin-bottom:12px;flex-wrap:wrap;">
                        <span class="badge-kd badge-success" style="font-size:10px;"><?= $g['experience_years'] ?? 0 ?> yrs exp</span>
                        <span class="badge-kd badge-success" style="font-size:10px;">Available</span>
                    </div>
                    <div style="font-size:18px;font-weight:800;color:var(--primary);">৳<?= number_format($g['daily_rate'] ?? 0) ?>/group/day</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($isAuth): ?></div></div><?php else: ?></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
