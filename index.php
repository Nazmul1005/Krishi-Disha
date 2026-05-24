<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';
$page_title = 'Home — Agricultural Intelligence Platform';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Navbar -->
<nav class="kd-navbar">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <a href="/KrishiDisha/index.php" class="navbar-brand">
                <div class="brand-icon" style="width:36px;height:36px;font-size:16px;border-radius:8px;background:linear-gradient(135deg,#52b788,#2d6a4f);display:flex;align-items:center;justify-content:center;color:#fff;">
                    <i class="fa-solid fa-leaf"></i>
                </div>
                KrishiDisha
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="/KrishiDisha/modules/encyclopedia.php" class="nav-link d-none d-md-inline">Encyclopedia</a>
                <a href="/KrishiDisha/modules/disease.php" class="nav-link d-none d-md-inline">Disease</a>
                <a href="/KrishiDisha/modules/calculator.php" class="nav-link d-none d-md-inline">Calculator</a>
                <?php if (isLoggedIn()): ?>
                    <a href="/KrishiDisha/<?= currentRole() ?>/dashboard.php" class="btn-kd btn-kd-primary" style="padding:8px 18px; font-size:13px;">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="/KrishiDisha/auth/login.php" class="nav-link">Login</a>
                    <a href="/KrishiDisha/auth/register.php" class="btn-kd btn-kd-primary" style="padding:8px 18px; font-size:13px;">
                        <i class="fa-solid fa-user-plus"></i> Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <div class="hero-eyebrow">
                    <i class="fa-solid fa-seedling"></i>
                    Bangladesh's #1 Agri Intelligence Platform
                </div>
                <h1>
                    Empowering Farmers<br>
                    with <span class="highlight">Smart Agriculture</span>
                </h1>
                <p>
                    KrishiDisha connects farmers, traders, tourists, and experts in a unified digital ecosystem — from crop knowledge to marketplace, from farm tours to expert consultations.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="/KrishiDisha/auth/register.php" class="btn-kd btn-kd-primary" style="padding:14px 28px; font-size:16px;">
                        <i class="fa-solid fa-rocket"></i> Start Free
                    </a>
                    <a href="/KrishiDisha/modules/encyclopedia.php" class="btn-kd btn-kd-outline" style="padding:14px 28px; font-size:16px; border-color:rgba(255,255,255,0.4); color:#fff;">
                        <i class="fa-solid fa-book-open"></i> Explore Crops
                    </a>
                </div>
                <div class="hero-stats">
                    <div>
                        <div class="hero-stat-value">10+</div>
                        <div class="hero-stat-label">Crop Species</div>
                    </div>
                    <div>
                        <div class="hero-stat-value">8</div>
                        <div class="hero-stat-label">User Roles</div>
                    </div>
                    <div>
                        <div class="hero-stat-value">26</div>
                        <div class="hero-stat-label">Database Tables</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 position-relative d-none d-lg-block" style="height:560px;">
                <!-- Floating info cards -->
                <div class="floating-card card-a">
                    <div style="font-size:12px; color:rgba(255,255,255,0.6); margin-bottom:4px;">Market Price</div>
                    <div style="font-size:20px; font-weight:800; color:#74c69d;">৳ 45/kg</div>
                    <div style="font-size:12px; color:rgba(255,255,255,0.7);">🌾 Rice — Sylhet Region</div>
                </div>
                <div class="floating-card card-b">
                    <div style="font-size:12px; color:rgba(255,255,255,0.6); margin-bottom:6px;">Expert Available</div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:36px;height:36px;background:linear-gradient(135deg,#52b788,#2d6a4f);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-user-doctor" style="color:#fff; font-size:14px;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:13px;">Dr. Anwar</div>
                            <div style="font-size:11px; color:rgba(255,255,255,0.6);">Soil & Pest Expert</div>
                        </div>
                    </div>
                </div>
                <div class="floating-card card-c">
                    <div style="font-size:12px; color:rgba(255,255,255,0.6); margin-bottom:4px;">Vitamin C / 100g</div>
                    <div style="font-size:24px; font-weight:800; color:#f4a261;">84 mg</div>
                    <div style="font-size:12px; color:rgba(255,255,255,0.7);">🥝 Bitter Gourd</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Counter Stats -->
<section class="counter-section section">
    <div class="container">
        <div class="row g-0 text-center">
            <div class="col-6 col-md-3">
                <div class="counter-item">
                    <div class="counter-num" data-count="10">0</div>
                    <div class="counter-label">Crop Species</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="counter-item">
                    <div class="counter-num" data-count="7">0</div>
                    <div class="counter-label">Crop Diseases Tracked</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="counter-item">
                    <div class="counter-num" data-count="10">0</div>
                    <div class="counter-label">Vitamins & Nutrients</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="counter-item">
                    <div class="counter-num" data-count="26">0</div>
                    <div class="counter-label">Database Tables</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modules -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5 fade-up">
            <span class="section-label">Core Modules</span>
            <h2 class="section-title">Everything Agriculture in One Place</h2>
            <p class="section-sub mx-auto">Eight powerful modules designed for every stakeholder in the agricultural ecosystem.</p>
        </div>
        <div class="row g-4">
            <?php
            $modules = [
                ['icon'=>'fa-book-atlas',    'color'=>'#2d6a4f','bg'=>'#d8f3dc','title'=>'Crop Encyclopedia',    'desc'=>'Scientific names, origins, history, trade status, and varieties of all major crops.', 'link'=>'/KrishiDisha/modules/encyclopedia.php'],
                ['icon'=>'fa-store',         'color'=>'#1d4ed8','bg'=>'#dbeafe','title'=>'Marketplace',          'desc'=>'Buy produce directly from farmers through verified dealers at transparent prices.', 'link'=>'/KrishiDisha/user/marketplace.php'],
                ['icon'=>'fa-bug',           'color'=>'#dc2626','bg'=>'#fee2e2','title'=>'Disease Detection',     'desc'=>'Identify crop diseases from a curated database of symptoms and proven solutions.', 'link'=>'/KrishiDisha/modules/disease.php'],
                ['icon'=>'fa-seedling',      'color'=>'#059669','bg'=>'#d1fae5','title'=>'Crop Recommendation',  'desc'=>'Get the best crop suggestions based on your region, soil type, and season.', 'link'=>'/KrishiDisha/modules/recommend.php'],
                ['icon'=>'fa-apple-whole',   'color'=>'#ea580c','bg'=>'#ffedd5','title'=>'Nutrition Guide',      'desc'=>'Track nutrient retention percentages across different cooking methods.', 'link'=>'/KrishiDisha/modules/nutrition.php'],
                ['icon'=>'fa-umbrella-beach','color'=>'#0891b2','bg'=>'#cffafe','title'=>'Agri-Tourism',         'desc'=>'Book farm visits, hire local guides, and order authentic farm-fresh food.', 'link'=>'/KrishiDisha/modules/tourism.php'],
                ['icon'=>'fa-user-doctor',   'color'=>'#7c3aed','bg'=>'#ede9fe','title'=>'Expert Consultation',  'desc'=>'Schedule paid advisory sessions with certified agricultural experts.', 'link'=>'/KrishiDisha/modules/consultation.php'],
                ['icon'=>'fa-calculator',    'color'=>'#b45309','bg'=>'#fef3c7','title'=>'Profit Calculator',    'desc'=>'Estimate your gross revenue and profit based on land, yield, and market price.', 'link'=>'/KrishiDisha/modules/calculator.php'],
            ];
            foreach ($modules as $i => $m):
            ?>
            <div class="col-md-6 col-lg-3 fade-up" style="transition-delay:<?= $i * 0.07 ?>s">
                <a href="<?= $m['link'] ?>" style="display:block; text-decoration:none;">
                    <div class="module-card">
                        <div class="module-icon" style="background:<?= $m['bg'] ?>; color:<?= $m['color'] ?>;">
                            <i class="fa-solid <?= $m['icon'] ?>"></i>
                        </div>
                        <h5><?= $m['title'] ?></h5>
                        <p><?= $m['desc'] ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- User Roles -->
<section class="section section-dark">
    <div class="container">
        <div class="text-center mb-5 fade-up">
            <span class="section-label" style="color:var(--accent);">Who's It For</span>
            <h2 class="section-title" style="color:#fff;">8 Roles, One Ecosystem</h2>
            <p class="section-sub mx-auto" style="color:rgba(255,255,255,0.6);">Every participant in the agricultural value chain has a dedicated, personalized dashboard.</p>
        </div>
        <div class="row g-3">
            <?php
            $roles = [
                ['icon'=>'👨‍💼','title'=>'Admin',           'desc'=>'Platform oversight, approvals & commissions'],
                ['icon'=>'🧑‍🌾','title'=>'Farmer',          'desc'=>'List produce, manage farmlands & consultations'],
                ['icon'=>'🏪','title'=>'Dealer',           'desc'=>'Buy from farmers, sell to consumers'],
                ['icon'=>'🧳','title'=>'Tourist',          'desc'=>'Book farm tours & order authentic food'],
                ['icon'=>'👩‍🍳','title'=>'Cook',            'desc'=>'Create recipes & fulfill food orders'],
                ['icon'=>'🔬','title'=>'Agri Expert',     'desc'=>'Provide paid advisory to farmers'],
                ['icon'=>'🗺️','title'=>'Tour Guide',       'desc'=>'Lead farm visits for a daily rate'],
                ['icon'=>'👤','title'=>'General User',    'desc'=>'Browse marketplace & nutrition tools'],
            ];
            foreach ($roles as $i => $r):
            ?>
            <div class="col-6 col-md-3 fade-up" style="transition-delay:<?= $i * 0.06 ?>s">
                <div class="role-card">
                    <div class="role-icon"><?= $r['icon'] ?></div>
                    <h6><?= $r['title'] ?></h6>
                    <p><?= $r['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section" style="background: linear-gradient(135deg, var(--surface3), var(--surface));">
    <div class="container text-center fade-up">
        <h2 class="section-title" style="color:var(--primary-dark);">Ready to Transform Your Farm?</h2>
        <p class="section-sub mx-auto mb-4">Join KrishiDisha today and get access to Bangladesh's most comprehensive agricultural intelligence platform.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="/KrishiDisha/auth/register.php" class="btn-kd btn-kd-primary" style="padding:14px 32px; font-size:16px;">
                <i class="fa-solid fa-user-plus"></i> Create Free Account
            </a>
            <a href="/KrishiDisha/modules/encyclopedia.php" class="btn-kd btn-kd-outline" style="padding:14px 32px; font-size:16px;">
                <i class="fa-solid fa-book-open"></i> Browse Crops
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer style="background:var(--dark2); color:rgba(255,255,255,0.7); padding:40px 0 20px;">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div style="width:32px;height:32px;background:linear-gradient(135deg,#52b788,#2d6a4f);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-leaf" style="color:#fff; font-size:14px;"></i>
                    </div>
                    <span style="font-family:'Nunito',sans-serif; font-weight:800; color:#fff; font-size:18px;">KrishiDisha</span>
                </div>
                <p style="font-size:13px; line-height:1.6;">Bangladesh's premier agricultural intelligence platform connecting the entire agri value chain.</p>
            </div>
            <div class="col-md-4">
                <h6 style="color:#fff; font-family:'Nunito',sans-serif; margin-bottom:14px;">Quick Links</h6>
                <div style="display:flex; flex-direction:column; gap:6px; font-size:13px;">
                    <a href="/KrishiDisha/modules/encyclopedia.php" style="color:rgba(255,255,255,0.6);">Crop Encyclopedia</a>
                    <a href="/KrishiDisha/modules/disease.php" style="color:rgba(255,255,255,0.6);">Disease Detection</a>
                    <a href="/KrishiDisha/modules/calculator.php" style="color:rgba(255,255,255,0.6);">Profit Calculator</a>
                    <a href="/KrishiDisha/modules/tourism.php" style="color:rgba(255,255,255,0.6);">Agri-Tourism</a>
                </div>
            </div>
            <div class="col-md-4">
                <h6 style="color:#fff; font-family:'Nunito',sans-serif; margin-bottom:14px;">Get Started</h6>
                <div style="display:flex; flex-direction:column; gap:6px; font-size:13px;">
                    <a href="/KrishiDisha/auth/register.php" style="color:rgba(255,255,255,0.6);">Register</a>
                    <a href="/KrishiDisha/auth/login.php" style="color:rgba(255,255,255,0.6);">Login</a>
                </div>
            </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.07); padding-top:16px; text-align:center; font-size:12px;">
            © <?= date('Y') ?> KrishiDisha. Agricultural Intelligence Platform. Built with PHP & MySQL.
        </div>
    </div>
</footer>

<?php include __DIR__ . '/includes/footer.php'; ?>
