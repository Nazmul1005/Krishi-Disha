<?php
require_once __DIR__.'/navigation.php';
$role=$_SESSION['role'] ?? 'general'; $userName=$_SESSION['name'] ?? 'User';
[$sidebarItems,$sharedNav]=navigationItems($role);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="/KrishiDisha/index.php" class="brand-link">
            <span class="brand-icon"><i aria-hidden="true" class="fa-solid fa-leaf"></i></span>
            <span class="brand-text">KrishiDisha</span>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><i aria-hidden="true" class="fa-solid fa-circle-user"></i></div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            <span class="user-role badge"><?= e(ucfirst($role)) ?></span>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Role navigation">
        <div class="nav-section-label">My Panel</div>
        <?php foreach ($sidebarItems as $item): ?>
        <a href="<?= e($item['href']) ?>" <?= navigationActive($item['href'])?'aria-current="page"':'' ?> class="nav-item <?= navigationActive($item['href']) ? 'active' : '' ?>">
            <i aria-hidden="true" class="fa-solid <?= e($item['icon']) ?>"></i>
            <span><?= t($item['label']) ?></span>
        </a>
        <?php endforeach; ?>

        <div class="nav-section-label mt-3">Explore</div>
        <?php foreach ($sharedNav as $item): ?>
        <a href="<?= e($item['href']) ?>" <?= navigationActive($item['href'])?'aria-current="page"':'' ?> class="nav-item <?= navigationActive($item['href']) ? 'active' : '' ?>">
            <i aria-hidden="true" class="fa-solid <?= e($item['icon']) ?>"></i>
            <span><?= t($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/KrishiDisha/modules/profile.php" class="nav-item <?= navigationActive('/KrishiDisha/modules/profile.php') ? 'active' : '' ?>">
            <i aria-hidden="true" class="fa-solid fa-id-card"></i>
            <span><?= t('My Profile') ?></span>
        </a>
        <form method="POST" action="/KrishiDisha/auth/logout.php"><?= csrfField() ?><?= requestKeyField() ?><button class="nav-item text-danger" type="submit"><i aria-hidden="true" class="fa-solid fa-right-from-bracket"></i> <?= t('Logout') ?></button></form>
    </div>
</aside>
