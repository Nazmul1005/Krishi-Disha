<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$page_title = 'Profit Calculator';
$isAuth = isLoggedIn();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if ($isAuth): ?><div class="layout-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content">
<?php else: ?><nav class="kd-navbar"><div class="container"><div class="d-flex align-items-center justify-content-between"><a href="/KrishiDisha/index.php" class="navbar-brand" style="display:flex;align-items:center;gap:10px;font-family:'Nunito',sans-serif;font-size:20px;font-weight:800;color:#fff;"><div style="width:34px;height:34px;background:linear-gradient(135deg,#52b788,#2d6a4f);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-leaf" style="color:#fff;font-size:15px;"></i></div>KrishiDisha</a><a href="/KrishiDisha/auth/login.php" class="btn-kd btn-kd-primary" style="padding:8px 18px;font-size:13px;">Login</a></div></div></nav><div style="padding-top:70px;"><?php endif; ?>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($isAuth): ?><button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none; font-size:20px;"><i class="fa-solid fa-bars"></i></button><?php endif; ?>
        <div class="topbar-title"><i class="fa-solid fa-calculator me-2" style="color:var(--gold);"></i>Farm Profit Calculator</div>
    </div>
</div>

<div class="page-body">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-6">
            <div class="calc-card">
                <h4 style="color:#fff;margin-bottom:6px;"><i class="fa-solid fa-calculator me-2" style="color:var(--accent);"></i>Calculate Your Farm Profit</h4>
                <p style="color:rgba(255,255,255,0.7);margin-bottom:28px;font-size:14px;">Estimate gross revenue, net profit, ROI, and profit per acre based on your inputs.</p>

                <form id="profitCalcForm" class="form-kd">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="color:rgba(255,255,255,0.85);">Land Area (acres)</label>
                                <input type="number" id="land_area" step="0.01" min="0" class="form-control" placeholder="e.g. 5.5" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="color:rgba(255,255,255,0.85);">Total Investment (৳)</label>
                                <input type="number" id="investment" step="1" min="0" class="form-control" placeholder="e.g. 50000" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="color:rgba(255,255,255,0.85);">Expected Yield (kg)</label>
                                <input type="number" id="yield_kg" step="1" min="0" class="form-control" placeholder="e.g. 3000" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="color:rgba(255,255,255,0.85);">Market Price per kg (৳)</label>
                                <input type="number" id="market_price" step="0.01" min="0" class="form-control" placeholder="e.g. 45" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-kd btn-kd-gold w-100 justify-content-center mt-3" style="padding:14px; font-size:16px; color:#fff;">
                        <i class="fa-solid fa-chart-line"></i> Calculate Profit
                    </button>
                </form>
            </div>

            <!-- Results -->
            <div id="calcResults" style="display:none; margin-top:20px;">
                <div class="row g-3">
                    <div class="col-6">
                        <div style="background:#fff;border-radius:var(--radius);padding:20px;text-align:center;border:1px solid var(--border);box-shadow:var(--shadow);">
                            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Gross Revenue</div>
                            <div id="result_gross" style="font-size:22px;font-weight:800;font-family:'Nunito',sans-serif;color:var(--primary-dark);">৳ —</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#fff;border-radius:var(--radius);padding:20px;text-align:center;border:1px solid var(--border);box-shadow:var(--shadow);">
                            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Net Profit</div>
                            <div id="result_profit" style="font-size:22px;font-weight:800;font-family:'Nunito',sans-serif;">৳ —</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#fff;border-radius:var(--radius);padding:20px;text-align:center;border:1px solid var(--border);box-shadow:var(--shadow);">
                            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">ROI</div>
                            <div id="result_roi" style="font-size:22px;font-weight:800;font-family:'Nunito',sans-serif;color:#7c3aed;">—%</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#fff;border-radius:var(--radius);padding:20px;text-align:center;border:1px solid var(--border);box-shadow:var(--shadow);">
                            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Profit / Acre</div>
                            <div id="result_acre" style="font-size:22px;font-weight:800;font-family:'Nunito',sans-serif;color:var(--gold);">৳ —</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-kd">
                <div class="card-header-kd"><h5><i class="fa-solid fa-lightbulb me-2" style="color:var(--gold);"></i>Market Reference Prices</h5></div>
                <div class="card-body-kd p-0">
                    <table class="table-kd">
                        <thead><tr><th>Crop</th><th>Avg Price/kg</th><th>Season</th></tr></thead>
                        <tbody>
                        <?php $prices = $pdo->query("SELECT c.name, c.season, AVG(p.price_per_kg) as avg_price FROM PRODUCT p JOIN CROP c ON p.crop_id=c.id GROUP BY c.id ORDER BY c.name")->fetchAll(); ?>
                        <?php foreach ($prices as $pr): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($pr['name']) ?></td>
                            <td>
                                <span style="color:var(--primary);font-weight:700;">৳<?= number_format($pr['avg_price'],2) ?></span>
                                <button onclick="document.getElementById('market_price').value='<?= round($pr['avg_price'],2) ?>'" style="border:none;background:none;color:var(--primary-light);font-size:11px;cursor:pointer;margin-left:4px;" title="Use this price">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </button>
                            </td>
                            <td><span class="badge-kd badge-muted" style="font-size:10px;"><?= ucfirst($pr['season']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-kd mt-4">
                <div class="card-header-kd"><h5><i class="fa-solid fa-circle-info me-2" style="color:var(--info);"></i>Formula Used</h5></div>
                <div class="card-body-kd">
                    <div style="font-size:13px;line-height:1.8;color:var(--text-muted);">
                        <strong style="color:var(--text);">Gross Revenue</strong> = Yield (kg) × Market Price (৳/kg)<br>
                        <strong style="color:var(--text);">Net Profit</strong> = Gross Revenue − Total Investment<br>
                        <strong style="color:var(--text);">ROI</strong> = (Net Profit ÷ Investment) × 100%<br>
                        <strong style="color:var(--text);">Profit/Acre</strong> = Net Profit ÷ Land Area
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isAuth): ?></div></div><?php else: ?></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
