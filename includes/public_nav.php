<header class="site-header">
    <a class="site-brand" href="/KrishiDisha/"><?= icon('fa-seedling') ?><span>KrishiDisha<small><?= t('From field to community','মাঠ থেকে মানুষের কাছে') ?></small></span></a>
    <form class="header-search" method="GET" action="/KrishiDisha/modules/encyclopedia.php" role="search">
        <label class="visually-hidden" for="header-search">Search crops / ফসল খুঁজুন</label>
        <input type="search" name="q" id="header-search" placeholder="<?= t('Search crops…','ফসল খুঁজুন…') ?>" maxlength="150">
        <button type="submit" aria-label="Search crops / ফসল খুঁজুন"><?= icon('fa-magnifying-glass') ?></button>
    </form>
    <div class="header-actions">
        <form method="POST" action="/KrishiDisha/auth/language.php" class="language-switch">
            <?= csrfField() ?><input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '/KrishiDisha/') ?>">
            <label class="visually-hidden" for="language">Language / ভাষা</label>
            <select id="language" name="language"><option value="bn" <?= ($_SESSION['language'] ?? 'bn')==='bn'?'selected':'' ?>>বাংলা</option><option value="en" <?= ($_SESSION['language'] ?? '')==='en'?'selected':'' ?>>English</option></select>
            <button type="submit" aria-label="Apply language / ভাষা বদলান"><?= icon('fa-check') ?></button>
        </form>
        <?php if(isLoggedIn()): ?>
        <a class="header-dashboard" aria-label="<?= t('Dashboard') ?>" href="<?= e(dashboardUrl()) ?>"><?= icon('fa-gauge-high') ?><span><?= t('Dashboard') ?></span></a>
        <?php if($roleLayout): ?><button id="sidebarToggle" class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false"><?= icon('fa-bars') ?></button><?php endif; ?>
        <?php else: ?><a class="btn-kd btn-kd-primary" href="/KrishiDisha/auth/login.php"><?= t('Sign In') ?></a><?php endif; ?>
    </div>
</header>
