<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/reference_photos.php';

$search   = trim($_GET['q'] ?? '');
$season   = trim($_GET['season'] ?? '');
$category = trim($_GET['category'] ?? '');

$where = ["c.review_status='reviewed'"]; $params = [];
if ($search)   { $where[] = '(c.name LIKE ? OR c.scientific_name LIKE ? OR c.local_name LIKE ? OR c.history LIKE ?)'; $p="%$search%"; $params=array_merge($params,[$p,$p,$p,$p]); }
if ($season)   { $where[] = 'c.season=?';   $params[] = $season; }
if ($category) { $where[] = 'c.category=?'; $params[] = $category; }

$sql = 'SELECT c.*, COUNT(DISTINCT cd.disease_id) as disease_count FROM CROP c LEFT JOIN CROP_DISEASE cd ON c.id=cd.crop_id WHERE '.implode(' AND ',$where).' GROUP BY c.id ORDER BY c.name';
$st = $pdo->prepare($sql); $crops=pagedRows($pdo,$st->queryString,$params,'crops','Reviewed crops',['name', 'local_name'],false);
$categories = $pdo->query('SELECT DISTINCT category FROM CROP WHERE category IS NOT NULL ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Crop Encyclopedia';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if (isLoggedIn()): ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" id="main-content" tabindex="-1">
<?php else: ?>

<div style="">
<?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if (isLoggedIn()): ?><?php endif; ?>
        <h1 class="topbar-title"><i class="fa-solid fa-book-atlas me-2" style="color:var(--primary);"></i>Crop Encyclopedia</h1>
    </div>
    <div class="topbar-actions">
        <a href="/KrishiDisha/modules/photo_library.php?type=crop" class="btn-kd btn-kd-outline" style="padding:6px 14px;font-size:12px;"><i class="fa-solid fa-images"></i> Crop photos</a>
        <span class="badge-kd badge-success"><?= count($crops) ?> entries</span>
        <?php if (isLoggedIn() && currentRole() === 'admin'): ?>
        <a href="/KrishiDisha/admin/manage_content.php?tab=crops" class="btn-kd btn-kd-primary" style="padding:6px 14px;font-size:12px;">
            <i class="fa-solid fa-pen-to-square"></i> Manage Crops
        </a>
        <?php elseif (isLoggedIn()): ?>
        <a href="/KrishiDisha/modules/suggest.php?section=crop" class="btn-kd btn-kd-outline" style="padding:6px 14px;font-size:12px;">
            <i class="fa-solid fa-lightbulb"></i> Suggest Crop
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search & Filter -->
<form class="filter-bar" method="GET" style="margin-bottom:20px;">
    <input type="text" name="q" placeholder="Search crops by name, local name, description..." value="<?= htmlspecialchars($search) ?>" style="flex:3;">
    <select name="category" class="form-control" style="flex:1;max-width:160px;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>" <?= $category===$cat?'selected':'' ?>><?= e($cat) ?></option><?php endforeach; ?>
    </select>
    <select name="season" class="form-control" style="flex:1;max-width:130px;">
        <option value="">All Seasons</option>
        <?php foreach (['summer','winter','rainy','all'] as $s): ?><option value="<?= $s ?>" <?= $season===$s?'selected':'' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn-kd btn-kd-primary"><i class="fa-solid fa-search"></i> Search</button>
    <a href="encyclopedia.php" class="btn-kd btn-kd-outline">Reset</a>
</form>

<div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?>
    <!-- Crop Grid -->
    <?php if (empty($crops)): ?>
    <div class="card-kd"><div class="card-body-kd text-center py-5"><div style="font-size:64px;"><i aria-hidden="true" class="fa-solid fa-wheat-awn"></i></div><h4 style="color:var(--text-muted);">No crops found</h4><p style="color:var(--text-muted);">Try adjusting your search or filters.</p><a class="btn-kd btn-kd-outline" href="encyclopedia.php">Clear filters</a></div></div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($crops as $crop): ?>
        <?php $photo = referencePhoto('crop', $crop['name']); $hasUploadedImage = !empty($crop['image']) && mediaAvailable($crop['image']); ?>
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="crop-card" style="position:relative;">
                <?php if (isLoggedIn() && currentRole() === 'admin'): ?>
                <div style="position:absolute;top:8px;right:8px;z-index:10;display:flex;gap:4px;">
                    <a href="/KrishiDisha/admin/manage_content.php?tab=crops&edit=<?= e($crop['id']) ?>" class="btn-kd btn-kd-outline" style="padding:3px 8px;font-size:10px;background:rgba(255,255,255,0.9);"><i class="fa-solid fa-pen"></i></a>
                    <form method="POST" action="/KrishiDisha/admin/manage_content.php?tab=crops" style="display:inline"><?= csrfField() ?><?= requestKeyField() ?><input type="hidden" name="delete_crop" value="<?= e($crop['id']) ?>"><button type="submit" class="btn-kd btn-kd-danger" style="padding:3px 8px;font-size:10px;opacity:0.85;" data-confirm="Delete <?= htmlspecialchars($crop['name']) ?>?"><i class="fa-solid fa-trash"></i></button></form>
                </div>
                <?php endif; ?>
                <a href="encyclopedia.php?detail=<?= e($crop['id']) ?>" style="display:block; text-decoration:none;color:inherit;">
                    <div style="height:180px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,var(--surface3),var(--accent3));">
                        <?php if ($hasUploadedImage || $photo): ?>
                        <img width="480" height="320" decoding="async" loading="lazy" src="/KrishiDisha/<?= e($hasUploadedImage ? $crop['image'] : $photo['image']) ?>" alt="<?= e($hasUploadedImage ? $crop['name'] : $photo['caption']) ?>" style="width:100%;height:180px;object-fit:cover;">
                        <?php else: ?>
                        <div style="font-size:72px;"><?php $icons=['Grain'=>'<i aria-hidden="true" class="fa-solid fa-wheat-awn"></i>','Vegetable'=>'<i aria-hidden="true" class="fa-solid fa-carrot"></i>','Fruit'=>'<i aria-hidden="true" class="fa-solid fa-apple-whole"></i>','Fiber'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Oilseed'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Legume'=>'<i aria-hidden="true" class="fa-solid fa-seedling"></i>','Cash Crop'=>'<i aria-hidden="true" class="fa-solid fa-coins"></i>']; echo $icons[$crop['category']]??'<i aria-hidden="true" class="fa-solid fa-seedling"></i>'; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="crop-card-body">
                        <h5><?= htmlspecialchars($crop['name']) ?></h5>
                        <div class="scientific"><?= htmlspecialchars($crop['scientific_name'] ?? '') ?> · <?= htmlspecialchars($crop['local_name'] ?? '') ?></div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px;">
                            <span class="badge-kd badge-success" style="font-size:10px;"><?= e(ucfirst($crop['season'])) ?></span>
                            <span class="badge-kd badge-info" style="font-size:10px;"><?= e($crop['category']) ?></span>
                            <span class="badge-kd badge-muted" style="font-size:10px;"><?= e(ucfirst($crop['trade_status'])) ?></span>
                        </div>
                        <p><?= e(mb_substr(strip_tags($crop['history'] ?? ''), 0, 90)) ?>...</p>
                    </div>
                </a>
                <?php if (!$hasUploadedImage && $photo): ?><div class="photo-inline-credit">Photo: <?= e($photo['credit']) ?> · <a href="/KrishiDisha/modules/photo_library.php?type=crop#<?= e($photo['slug']) ?>">source & license</a></div><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    // Detail view
    if (isset($_GET['detail'])) {
        $detail = $pdo->prepare("SELECT * FROM CROP WHERE id=? AND review_status='reviewed'");
        $detail->execute([(int)$_GET['detail']]);
        $detail = $detail->fetch();
        if ($detail) {
            $vitamins  = $pdo->prepare("SELECT v.name, v.unit, cv.amount_per_100g FROM CROP_VITAMIN cv JOIN VITAMIN v ON cv.vitamin_id=v.id WHERE cv.crop_id=? AND cv.review_status='reviewed'");
            $vitamins->execute([$detail['id']]); $vitamins = $vitamins->fetchAll();
            $diseases  = $pdo->prepare("SELECT d.* FROM CROP_DISEASE cd JOIN DISEASE d ON cd.disease_id=d.id WHERE cd.crop_id=? AND d.review_status='reviewed'");
            $diseases->execute([$detail['id']]); $diseases = $diseases->fetchAll();
            $varieties = $pdo->prepare("SELECT * FROM CROP_VARIETY WHERE crop_id=?");
            $varieties->execute([$detail['id']]); $varieties = $varieties->fetchAll();
            $regions   = $pdo->prepare("SELECT * FROM REGION_CROP WHERE crop_id=? AND review_status='reviewed' ORDER BY suitability_score DESC");
            $regions->execute([$detail['id']]); $regions = $regions->fetchAll();
    ?>
    <!-- Modal-style detail panel -->
    <dialog open id="cropDetailModal" class="kd-dialog crop-detail-dialog" aria-labelledby="cropDetailHeading">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:800px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.4);">
            <div style="background:linear-gradient(135deg,var(--primary-dark),var(--primary));padding:32px 36px;color:#fff;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h2 id="cropDetailHeading" style="color:#fff;margin-bottom:6px;"><?= htmlspecialchars($detail['name']) ?></h2>
                        <div style="font-style:italic;color:rgba(255,255,255,0.75);font-size:14px;"><?= htmlspecialchars($detail['scientific_name'] ?? '') ?></div>
                        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                            <span class="badge-kd" style="background:rgba(255,255,255,0.2);color:#fff;"><?= e($detail['category']) ?></span>
                            <span class="badge-kd" style="background:rgba(255,255,255,0.2);color:#fff;"><?= e(ucfirst($detail['season'])) ?> season</span>
                            <span class="badge-kd" style="background:rgba(255,255,255,0.2);color:#fff;"><?= e(ucfirst($detail['trade_status'])) ?> trade</span>
                        </div>
                    </div>
                    <button type="button" data-close-detail class="modal-close" aria-label="Close crop detail">&times;</button>
                </div>
            </div>
            <div style="padding:30px 36px;">
                <h6 style="color:var(--primary-dark);font-weight:700;margin-bottom:10px;">Origin & History</h6>
                <p style="font-size:14px;line-height:1.7;color:var(--text-muted);margin-bottom:24px;">
                    <p>Source: <a href="<?= e($detail['source_url']) ?>" target="_blank" rel="noopener">Reference</a> · Reviewed <?= e($detail['reviewed_at']) ?></p><strong>Origin:</strong> <?= htmlspecialchars($detail['origin'] ?? 'N/A') ?><br><br>
                    <?= nl2br(htmlspecialchars($detail['history'] ?? '')) ?>
                </p>

                <?php if ($vitamins): ?>
                <h6 style="color:var(--primary-dark);font-weight:700;margin-bottom:12px;">Nutritional Content (per 100g)</h6>
                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px;">
                    <?php foreach ($vitamins as $v): ?>
                    <div style="background:var(--surface3);border-radius:10px;padding:10px 16px;text-align:center;">
                        <div style="font-weight:700;color:var(--primary);"><?= e($v['amount_per_100g']) ?> <?= e($v['unit']) ?></div>
                        <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($v['name']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($varieties): ?>
                <h6 style="color:var(--primary-dark);font-weight:700;margin-bottom:12px;">Notable Varieties</h6>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;">
                    <?php foreach ($varieties as $var): ?>
                    <span class="badge-kd badge-info"><?= htmlspecialchars($var['variety_name']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($regions): ?>
                <h6 style="color:var(--primary-dark);font-weight:700;margin-bottom:12px;">Growing Regions in Bangladesh</h6>
                <table class="table-kd" style="margin-bottom:24px;">
                    <thead><tr><th>Region</th><th>Soil</th><th>Season</th><th>Suitability</th></tr></thead>
                    <tbody>
                    <?php foreach ($regions as $r): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['region']) ?></td>
                        <td><?= htmlspecialchars($r['soil_type']) ?></td>
                        <td><?= e(ucfirst($r['season'])) ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                                    <div style="width:<?= ($r['suitability_score']/10*100) ?>%;height:100%;background:linear-gradient(90deg,var(--primary),var(--primary-light));border-radius:4px;"></div>
                                </div>
                                <span style="font-size:12px;font-weight:700;color:var(--primary);"><?= e($r['suitability_score']) ?>/10</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <?php if ($diseases): ?>
                <h6 style="color:var(--primary-dark);font-weight:700;margin-bottom:12px;">Common Diseases</h6>
                <?php foreach ($diseases as $dis): ?>
                <div style="background:var(--surface3);border-radius:10px;padding:14px 16px;margin-bottom:10px;border-left:4px solid var(--danger);">
                    <strong style="color:var(--danger);"><?= htmlspecialchars($dis['name']) ?></strong>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">
                        <strong>Symptoms:</strong> <?= htmlspecialchars($dis['symptoms']) ?>
                    </div>
                    <div style="font-size:13px;color:var(--text);margin-top:4px;">
                        <strong>Reviewed reference:</strong> <?= htmlspecialchars($dis['solution']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </dialog>
    <?php }} ?>
</div>

<?php if (isLoggedIn()): ?>
</div></div>
<?php else: ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
