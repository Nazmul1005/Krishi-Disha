<?php
$role = $_SESSION['role'] ?? 'general';
$userName = $_SESSION['name'] ?? 'User';

$navMap = [
    'admin' => [
        ['icon'=>'fa-gauge-high',       'label'=>'Dashboard',       'href'=>'/KrishiDisha/admin/dashboard.php'],
        ['icon'=>'fa-users',            'label'=>'Users',           'href'=>'/KrishiDisha/admin/users.php'],
        ['icon'=>'fa-user-check',       'label'=>'Approvals',       'href'=>'/KrishiDisha/admin/approvals.php'],
        ['icon'=>'fa-pen-to-square',    'label'=>'Manage Content',  'href'=>'/KrishiDisha/admin/manage_content.php'],
        ['icon'=>'fa-inbox',            'label'=>'Proposals',       'href'=>'/KrishiDisha/admin/proposals.php'],
        ['icon'=>'fa-hand-holding-dollar','label'=>'Commissions',   'href'=>'/KrishiDisha/admin/commissions.php'],
    ],
    'farmer' => [
        ['icon'=>'fa-gauge-high',  'label'=>'Dashboard',     'href'=>'/KrishiDisha/farmer/dashboard.php'],
        ['icon'=>'fa-basket-shopping','label'=>'My Produce',  'href'=>'/KrishiDisha/farmer/produce.php'],
        ['icon'=>'fa-tractor',     'label'=>'Farm Lands',    'href'=>'/KrishiDisha/farmer/farmland.php'],
        ['icon'=>'fa-user-doctor', 'label'=>'Consultations', 'href'=>'/KrishiDisha/modules/book_consultation.php'],
        ['icon'=>'fa-lightbulb',   'label'=>'Suggest Content','href'=>'/KrishiDisha/modules/suggest.php'],
    ],
    'dealer' => [
        ['icon'=>'fa-gauge-high',  'label'=>'Dashboard',  'href'=>'/KrishiDisha/dealer/dashboard.php'],
        ['icon'=>'fa-boxes-stacked','label'=>'Inventory', 'href'=>'/KrishiDisha/dealer/inventory.php'],
        ['icon'=>'fa-chart-line',  'label'=>'Sales',      'href'=>'/KrishiDisha/dealer/sales.php'],
    ],
    'tourist' => [
        ['icon'=>'fa-gauge-high','label'=>'Dashboard',   'href'=>'/KrishiDisha/tourist/dashboard.php'],
        ['icon'=>'fa-map',       'label'=>'Farm Tours',  'href'=>'/KrishiDisha/tourist/tours.php'],
        ['icon'=>'fa-utensils',  'label'=>'Food Orders', 'href'=>'/KrishiDisha/tourist/food_orders.php'],
        ['icon'=>'fa-user-doctor','label'=>'Consultations','href'=>'/KrishiDisha/modules/book_consultation.php'],
        ['icon'=>'fa-lightbulb', 'label'=>'Suggest Content','href'=>'/KrishiDisha/modules/suggest.php'],
    ],
    'cook' => [
        ['icon'=>'fa-gauge-high','label'=>'Dashboard', 'href'=>'/KrishiDisha/cook/dashboard.php'],
        ['icon'=>'fa-book-open', 'label'=>'Recipes',   'href'=>'/KrishiDisha/cook/recipes.php'],
        ['icon'=>'fa-receipt',   'label'=>'Orders',    'href'=>'/KrishiDisha/cook/orders.php'],
    ],
    'expert' => [
        ['icon'=>'fa-gauge-high','label'=>'Dashboard', 'href'=>'/KrishiDisha/expert/dashboard.php'],
        ['icon'=>'fa-calendar-check','label'=>'Sessions','href'=>'/KrishiDisha/expert/sessions.php'],
    ],
    'guide' => [
        ['icon'=>'fa-gauge-high','label'=>'Dashboard', 'href'=>'/KrishiDisha/guide/dashboard.php'],
        ['icon'=>'fa-route',     'label'=>'Bookings',  'href'=>'/KrishiDisha/guide/bookings.php'],
        ['icon'=>'fa-user-doctor','label'=>'Sessions', 'href'=>'/KrishiDisha/guide/sessions.php'],
    ],
    'general' => [
        ['icon'=>'fa-gauge-high','label'=>'Dashboard',    'href'=>'/KrishiDisha/user/dashboard.php'],
        ['icon'=>'fa-apple-whole','label'=>'Nutrition',   'href'=>'/KrishiDisha/user/nutrition.php'],
        ['icon'=>'fa-user-doctor','label'=>'Consultations','href'=>'/KrishiDisha/modules/book_consultation.php'],
    ],
];

$navMap['admin'][]=['icon'=>'fa-book','label'=>'Review References','href'=>'/KrishiDisha/admin/knowledge.php'];
$navMap['farmer'][]=['icon'=>'fa-receipt','label'=>'Orders','href'=>'/KrishiDisha/farmer/orders.php'];
$navMap['farmer'][]=['icon'=>'fa-calendar','label'=>'Tour Bookings','href'=>'/KrishiDisha/farmer/bookings.php'];
$sharedNav = [
    ['icon'=>'fa-list','label'=>'Order History','href'=>'/KrishiDisha/modules/orders.php'],
    ['icon'=>'fa-money-bill','label'=>'Payments','href'=>'/KrishiDisha/modules/payments.php'],
    ['icon'=>'fa-bell','label'=>'Notifications','href'=>'/KrishiDisha/modules/notifications.php'],
    ['icon'=>'fa-life-ring','label'=>'Support','href'=>'/KrishiDisha/modules/support.php'],
    ['icon'=>'fa-store',        'label'=>'Marketplace',       'href'=>'/KrishiDisha/user/marketplace.php'],
    ['icon'=>'fa-book-atlas',   'label'=>'Crop Encyclopedia', 'href'=>'/KrishiDisha/modules/encyclopedia.php'],
    ['icon'=>'fa-bug',          'label'=>'Disease Reference', 'href'=>'/KrishiDisha/modules/disease.php'],
    ['icon'=>'fa-seedling',     'label'=>'Crop Reference Matches',  'href'=>'/KrishiDisha/modules/recommend.php'],
    ['icon'=>'fa-leaf',         'label'=>'Nutrition Guide',   'href'=>'/KrishiDisha/modules/nutrition.php'],
    ['icon'=>'fa-umbrella-beach','label'=>'Agri-Tourism',     'href'=>'/KrishiDisha/modules/tourism.php'],
    ['icon'=>'fa-calculator',   'label'=>'Profit Calculator', 'href'=>'/KrishiDisha/modules/calculator.php'],
];

$sidebarItems = $navMap[$role] ?? $navMap['general'];
$currentPath = $_SERVER['REQUEST_URI'];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="/KrishiDisha/index.php" class="brand-link">
            <span class="brand-icon"><i class="fa-solid fa-leaf"></i></span>
            <span class="brand-text">KrishiDisha</span>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><i class="fa-solid fa-circle-user"></i></div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            <span class="user-role badge"><?= e(ucfirst($role)) ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">My Panel</div>
        <?php foreach ($sidebarItems as $item): ?>
        <a href="<?= e($item['href']) ?>" class="nav-item <?= (strpos($currentPath, basename($item['href'], '.php')) !== false) ? 'active' : '' ?>">
            <i class="fa-solid <?= e($item['icon']) ?>"></i>
            <span><?= t($item['label']) ?></span>
        </a>
        <?php endforeach; ?>

        <div class="nav-section-label mt-3">Explore</div>
        <?php foreach ($sharedNav as $item): ?>
        <a href="<?= e($item['href']) ?>" class="nav-item <?= (strpos($currentPath, basename($item['href'], '.php')) !== false) ? 'active' : '' ?>">
            <i class="fa-solid <?= e($item['icon']) ?>"></i>
            <span><?= t($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/KrishiDisha/modules/profile.php" class="nav-item <?= strpos($currentPath, 'profile.php') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-id-card"></i>
            <span><?= t('My Profile') ?></span>
        </a>
        <form method="POST" action="/KrishiDisha/auth/logout.php"><?= csrfField() ?><?= requestKeyField() ?><button class="nav-item text-danger" type="submit"><i class="fa-solid fa-right-from-bracket"></i> <?= t('Logout') ?></button></form>
    </div>
</aside>
