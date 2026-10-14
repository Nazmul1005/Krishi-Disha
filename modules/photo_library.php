<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/reference_photos.php';

$type = in_array($_GET['type'] ?? '', ['crop', 'disease'], true) ? $_GET['type'] : 'all';
$search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$search = mb_substr($search, 0, 80);
$photos = array_values(array_filter(referencePhotos(), static function ($photo) use ($type, $search) {
    if ($type !== 'all' && $photo['type'] !== $type) return false;
    return $search === '' || mb_stripos($photo['name'] . ' ' . $photo['context'] . ' ' . $photo['caption'], $search) !== false;
}));
$page_title = 'Crop & Disease Photo Library';
include __DIR__ . '/../includes/header.php';
?>
<?php if (isLoggedIn()): ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content" id="main-content" tabindex="-1">
<?php else: ?>

<main id="main-content" class="photo-public-main" tabindex="-1">
<?php endif; ?>
<div class="topbar"><div class="d-flex align-items-center gap-3">
<?php if (isLoggedIn()): ?><?php endif; ?>
<h1 class="topbar-title"><i class="fa-solid fa-images me-2" style="color:var(--primary);"></i>Crop & Disease Photo Library</h1>
</div><div class="topbar-actions"><span class="badge-kd badge-success"><?= count($photos) ?> photos</span></div></div>
<div class="page-body"><?php include __DIR__ . '/../includes/topbar.php'; ?>
<div class="photo-intro"><h1>Real photographs, with sources</h1><p>Browse crop plants, harvested produce, and example disease or pest symptoms. Images were taken in different places and conditions, so appearance may differ on your farm. A photo alone cannot diagnose a crop problem or determine a treatment. Consult a qualified local expert before acting.</p><p class="photo-intro-links"><a href="/KrishiDisha/modules/encyclopedia.php">Reviewed crop information</a> · <a href="/KrishiDisha/modules/disease.php">Reviewed disease information</a></p></div>
<form class="filter-bar photo-filter" method="get" action="photo_library.php">
<label class="visually-hidden" for="photo-search">Search photographs</label><input id="photo-search" type="search" name="q" maxlength="80" placeholder="Search crop, condition or host" value="<?= e($search) ?>">
<label class="visually-hidden" for="photo-type">Photo type</label><select id="photo-type" name="type"><option value="all" <?= $type === 'all' ? 'selected' : '' ?>>All photos</option><option value="crop" <?= $type === 'crop' ? 'selected' : '' ?>>Crops</option><option value="disease" <?= $type === 'disease' ? 'selected' : '' ?>>Diseases & pests</option></select>
<button class="btn-kd btn-kd-primary" type="submit">Search</button><a class="btn-kd btn-kd-outline" href="photo_library.php">Reset</a></form>
<?php if (!$photos): ?><div class="card-kd"><div class="card-body-kd"><p>No photos match your search.</p></div></div><?php endif; ?>
<div class="photo-grid">
<?php foreach ($photos as $photo): ?>
<article class="photo-card" id="<?= e($photo['slug']) ?>">
<img width="480" height="320" decoding="async" loading="lazy" decoding="async" src="/KrishiDisha/<?= e($photo['image']) ?>" alt="<?= e($photo['caption']) ?>" width="640" height="420">
<div class="photo-card-body"><span class="photo-type"><?= $photo['type'] === 'crop' ? 'Crop' : 'Disease / pest example' ?></span><h2><?= e($photo['name']) ?></h2><p class="photo-context"><?= e($photo['context']) ?></p><p><?= e($photo['caption']) ?></p>
<p class="photo-credit">Photo: <?= e($photo['credit']) ?> · <a href="<?= e($photo['license_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($photo['license']) ?></a> · <a href="<?= e($photo['source']) ?>" target="_blank" rel="noopener noreferrer">Original file</a></p></div>
</article>
<?php endforeach; ?>
</div>
</div>
</main>
<?php if (isLoggedIn()): ?></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
