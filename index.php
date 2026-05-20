<?php 
require_once 'config/db.php';
// lang_config.php is included in header.php
include 'includes/header.php'; 
include 'includes/sidebar.php'; 

/** 1. FETCH LIVE DATA (Timezone aligned to Cairo via config) **/
$sales_today = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
$revenue_month = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn() ?: 0;
$total_receivable = $pdo->query("SELECT SUM(total_debt) FROM customers")->fetchColumn() ?: 0;
$low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty <= 5")->fetchColumn();

/** 2. PREPARE CHART DATA (Minimized window for speed) **/
$days = []; $sales_values = [];
for ($i = 4; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days[] = date('D', strtotime($date));
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $sales_values[] = (float)($stmt->fetchColumn() ?: 0);
}
$cat_data = $pdo->query("SELECT c.category_name, COUNT(p.id) as total FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id LIMIT 4")->fetchAll();

$isAdmin = ($_SESSION['role'] === 'Super Admin');
?>

<div class="container-fluid py-2 dashboard-wrapper">
    <!-- TOP HEADER: TITLE & EXPORT -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 no-print gap-3">
        <div>
            <h3 class="fw-800 theme-text-main mb-0"><?= __("dashboard_title") ?></h3>
            <div class="d-flex align-items-center mt-2">
                <span class="badge bg-primary-soft text-primary px-3 rounded-pill fw-bold" style="font-size: 0.75rem;">
                    <i class="fas fa-clock me-1"></i> <?= __("live_status_as_of") ?>: <?= date('H:i') ?>
                </span>
            </div>
        </div>
        <?php if($isAdmin): ?>
        <button class="btn btn-primary rounded-pill shadow-sm px-4 fw-800 border-0 py-2" onclick="window.print()" style="background: var(--accent-red); font-size: 0.85rem;">
            <i class="fas fa-file-export me-2"></i> <?= __("export_report") ?>
        </button>
        <?php endif; ?>
    </div>

    <!-- HIGH-VISIBILITY ADAPTIVE HERO STATS -->
    <div class="row g-3 mb-4">
        <!-- Today's Sales -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-lg adaptive-stat-card border-start border-primary border-5">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <small class="text-uppercase fw-700 opacity-75 text-muted" style="font-size: 0.65rem; letter-spacing: 1px;"><?= __("today_sales") ?></small>
                        <div class="icon-sq bg-blue-soft text-blue"><i class="fas fa-coins"></i></div>
                    </div>
                    <!-- HIGH VISIBILITY AMOUNT -->
                    <h2 class="display-amount mt-2 mb-0"><?= number_format($sales_today) ?></h2>
                    <span class="currency-label fw-800"><?= __("currency") ?></span>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-lg adaptive-stat-card border-start border-success border-5">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <small class="text-uppercase fw-700 opacity-75 text-muted" style="font-size: 0.65rem; letter-spacing: 1px;"><?= __("monthly_revenue") ?></small>
                        <div class="icon-sq bg-green-soft text-green"><i class="fas fa-chart-line"></i></div>
                    </div>
                    <h2 class="display-amount mt-2 mb-0"><?= number_format($revenue_month) ?></h2>
                    <span class="currency-label fw-800"><?= __("currency") ?></span>
                </div>
            </div>
        </div>

        <!-- Money Owed (Debt) -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-lg adaptive-stat-card border-start border-danger border-5">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <small class="text-uppercase fw-700 opacity-75 text-muted" style="font-size: 0.65rem; letter-spacing: 1px;"><?= __("money_owed") ?></small>
                        <div class="icon-sq bg-red-soft text-red"><i class="fas fa-hand-holding-usd"></i></div>
                    </div>
                    <h2 class="display-amount mt-2 mb-0 text-danger-urgent"><?= number_format($total_receivable) ?></h2>
                    <span class="currency-label fw-800"><?= __("currency") ?></span>
                </div>
            </div>
        </div>

        <!-- Stock Alert -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-lg adaptive-stat-card border-start border-warning border-5">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <small class="text-uppercase fw-700 opacity-75 text-muted" style="font-size: 0.65rem; letter-spacing: 1px;"><?= __("stock_low") ?></small>
                        <div class="icon-sq bg-amber-soft text-amber"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                    <h2 class="display-amount mt-2 mb-0 theme-text-main"><?= $low_stock ?></h2>
                    <span class="currency-label fw-800"><?= __("items") ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ANALYTICS SECTION -->
    <div class="row g-4">
        <!-- Sales Velocity -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 rounded-4 adaptive-card h-100">
                <h6 class="fw-800 theme-text-main small text-uppercase mb-4">
                    <i class="fas fa-tachometer-alt me-2 text-danger"></i><?= __("sales_velocity") ?>
                </h6>
                <div style="height: 280px; position: relative;">
                    <canvas id="velocityChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Category Mix -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 adaptive-card h-100">
                <h6 class="fw-800 theme-text-main small text-uppercase mb-4">
                    <i class="fas fa-chart-pie me-2 text-primary"></i><?= __("inventory_mix") ?>
                </h6>
                <div style="height: 280px; position: relative;">
                    <canvas id="mixChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* PROFESSIONAL UI THEMING */
:root {
    --stat-card-bg: #ffffff;
    --amount-color: #111827;
    --main-text: #111827;
}

[data-theme="dark"] {
    --stat-card-bg: #16161a;
    --amount-color: #ffffff;
    --main-text: #ffffff;
}

.fw-800 { font-weight: 800; }
.fw-700 { font-weight: 700; }
.theme-text-main { color: var(--main-text); }

/* Amount Visibility logic: Flipped colors for themes */
.display-amount {
    font-size: 2.1rem;
    font-weight: 900;
    color: var(--amount-color);
    letter-spacing: -1.5px;
    line-height: 1;
    transition: color 0.3s ease;
}

/* Debts are always highlighted red regardless of theme */
.text-danger-urgent { color: #e94560 !important; }

.currency-label { 
    font-size: 0.65rem; 
    font-weight: 800; 
    color: #9ca3af; 
    text-transform: uppercase; 
    letter-spacing: 1px;
}

.adaptive-stat-card {
    background-color: var(--stat-card-bg) !important;
    border-radius: 20px;
    transition: transform 0.2s ease;
}
.adaptive-stat-card:hover { transform: translateY(-5px); }

.adaptive-card { background-color: var(--stat-card-bg) !important; }

.icon-sq {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px; font-size: 0.9rem;
}

/* UI Accents */
.bg-primary-soft { background: rgba(79, 70, 229, 0.1); }
.bg-blue-soft { background: rgba(59, 130, 246, 0.1); } .text-blue { color: #3b82f6; }
.bg-green-soft { background: rgba(16, 185, 129, 0.1); } .text-green { color: #10b981; }
.bg-red-soft { background: rgba(233, 69, 96, 0.1); } .text-red { color: #e94560; }
.bg-amber-soft { background: rgba(245, 158, 11, 0.1); } .text-amber { color: #f59e0b; }

@media (max-width: 576px) { .display-amount { font-size: 1.7rem; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/** ADAPTIVE CHART LOGIC **/
const currentTheme = () => document.documentElement.getAttribute('data-theme') || 'light';

const getThemeColors = () => {
    const isDark = currentTheme() === 'dark';
    return {
        text: isDark ? '#ffffff' : '#111827',
        grid: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)'
    };
};

let velocityChart, mixChart;

function renderDashboardCharts() {
    const themeCol = getThemeColors();

    // 1. Sales Velocity
    velocityChart = new Chart(document.getElementById('velocityChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($days) ?>,
            datasets: [{
                data: <?= json_encode($sales_values) ?>,
                borderColor: '#e94560',
                backgroundColor: 'rgba(233, 69, 96, 0.1)',
                fill: true, tension: 0.4, borderWidth: 4, pointRadius: 5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: themeCol.grid }, ticks: { color: themeCol.text, font: { weight: '600' } } },
                x: { grid: { display: false }, ticks: { color: themeCol.text, font: { weight: '600' } } }
            }
        }
    });

    // 2. Category Mix
    mixChart = new Chart(document.getElementById('mixChart'), {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($cat_data as $c) echo "'".$c['category_name']."',"; ?>],
            datasets: [{
                data: [<?php foreach($cat_data as $c) echo $c['total'].","; ?>],
                backgroundColor: ['#e94560', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '82%',
            plugins: {
                legend: { position: 'bottom', labels: { color: themeCol.text, font: { weight: '600' }, usePointStyle: true, padding: 15 } }
            }
        }
    });
}

renderDashboardCharts();

// Live Theme Listener
const themeObs = new MutationObserver(() => {
    velocityChart.destroy(); mixChart.destroy(); renderDashboardCharts();
});
themeObs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
</script>

<?php include 'includes/footer.php'; ?>