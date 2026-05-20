<?php
require_once 'config/db.php';
// lang_config.php is usually included in header.php

// --- 1. TIMEZONE SETTING (Cairo UTC+3) ---
date_default_timezone_set('Africa/Cairo');
$pdo->exec("SET time_zone = '+03:00'");

include 'includes/header.php';
include 'includes/sidebar.php';

// Restricted Access
if ($_SESSION['role'] == 'Cashier' || $_SESSION['role'] == 'Waiter') {
    echo "<div class='alert alert-danger m-5 shadow-sm border-0 text-center'><h4>".__("access_denied")."</h4><p>".__("restricted_intel_msg")."</p></div>";
    include 'includes/footer.php';
    exit();
}

// 2. FETCH ADMIN VAT SETTINGS
$settingsStmt = $pdo->query("SELECT vat_rate FROM settings WHERE id = 1");
$admin_settings = $settingsStmt->fetch();
$current_vat_percent = $admin_settings ? (float)$admin_settings['vat_rate'] : 0;
$divisor = 1 + ($current_vat_percent / 100);

// 3. DETERMINE SELECTED PERIOD
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';
$report_title_key = "";
$date_query = "";
$expense_query = "";

switch ($period) {
    case 'weekly':
        $report_title_key = "weekly_audit";
        $date_query = "YEARWEEK(s.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        $expense_query = "YEARWEEK(expense_date, 1) = YEARWEEK(CURDATE(), 1)";
        $plain_date_query = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'monthly':
        $report_title_key = "monthly_audit";
        $date_query = "MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())";
        $expense_query = "MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
        $plain_date_query = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        break;
    case 'annual':
        $report_title_key = "annual_audit";
        $date_query = "YEAR(s.created_at) = YEAR(CURDATE())";
        $expense_query = "YEAR(expense_date) = YEAR(CURDATE())";
        $plain_date_query = "YEAR(created_at) = YEAR(CURDATE())";
        break;
    case 'daily':
    default:
        $report_title_key = "daily_audit";
        $date_query = "DATE(s.created_at) = CURDATE()";
        $expense_query = "DATE(expense_date) = CURDATE()";
        $plain_date_query = "DATE(created_at) = CURDATE()";
        break;
}

// 4. DATA CALCULATION (THE 5 KEY VARIABLES)
// A. REVENUE (Gross amount collected)
$revenue = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE $plain_date_query")->fetchColumn() ?: 0;

// B. VAT (Extracted from revenue based on Admin settings)
$net_for_tax = $revenue / $divisor;
$tax_portion = $revenue - $net_for_tax;

// C. EXPENSES (Total operational costs)
$expenses = $pdo->query("SELECT SUM(amount) FROM expenses WHERE $expense_query")->fetchColumn() ?: 0;

// D. PERIOD DEBT (Amount recorded as Unpaid/Debt in this period)
$period_debt = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE $plain_date_query AND payment_method = 'Debt'")->fetchColumn() ?: 0;

/** 
 * E. FINAL NET PROFIT FORMULA
 * Logic: ( (Selling Price - Supplier Purchase Price) * Qty ) - Store Expenses 
 */
$profit_stmt = $pdo->prepare("SELECT SUM((si.unit_price - p.purchase_price) * si.quantity) 
                             FROM sale_items si 
                             JOIN products p ON si.product_id = p.id 
                             JOIN sales s ON si.sale_id = s.id 
                             WHERE $date_query");
$profit_stmt->execute();
$total_margin = $profit_stmt->fetchColumn() ?: 0;

// Final Take-home: Total Margin minus what was spent on expenses
$final_net_profit = $total_margin - $expenses;
?>

<div class="container-fluid">
    <!-- NAVIGATION & PRINT -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div class="btn-group shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <a href="?period=daily" class="btn btn-<?= $period == 'daily' ? 'primary' : 'light' ?> fw-bold px-4"><?= __("daily") ?></a>
            <a href="?period=weekly" class="btn btn-<?= $period == 'weekly' ? 'primary' : 'light' ?> fw-bold px-4"><?= __("weekly") ?></a>
            <a href="?period=monthly" class="btn btn-<?= $period == 'monthly' ? 'primary' : 'light' ?> fw-bold px-4"><?= __("monthly") ?></a>
            <a href="?period=annual" class="btn btn-<?= $period == 'annual' ? 'primary' : 'light' ?> fw-bold px-4"><?= __("annual") ?></a>
        </div>
        <button class="btn btn-dark shadow px-4 fw-bold rounded-pill" onclick="window.print()">
            <i class="fas fa-print me-2"></i> <?= __("export_report") ?>
        </button>
    </div>

    <!-- REPORT HEADER -->
    <div class="text-center mb-4">
        <h2 class="fw-800 text-dark mb-0"><?= strtoupper(__($report_title_key)) ?></h2>
        <p class="text-muted small fw-bold"><?= __("tax_configuration") ?>: <?= $current_vat_percent ?>% <?= __("tax_vat") ?></p>
        <hr class="w-25 mx-auto border-2">
    </div>

    <!-- HIGH VISIBILITY 5-VARIABLE GRID -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100 border-start border-primary border-5 rounded-4">
                <small class="text-muted text-uppercase fw-bold"><?= __("revenue") ?></small>
                <h2 class="fw-800 text-primary mb-0 mt-2"><?= number_format($revenue) ?> <small class="fs-6"><?= __("currency") ?></small></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100 border-start border-info border-5 rounded-4">
                <small class="text-muted text-uppercase fw-bold"><?= __("vat_component") ?></small>
                <h2 class="fw-800 text-info mb-0 mt-2"><?= number_format($tax_portion) ?> <small class="fs-6"><?= __("currency") ?></small></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100 border-start border-danger border-5 rounded-4">
                <small class="text-muted text-uppercase fw-bold"><?= __("expenses") ?></small>
                <h2 class="fw-800 text-danger mb-0 mt-2"><?= number_format($expenses) ?> <small class="fs-6"><?= __("currency") ?></small></h2>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100 border-start border-warning border-5 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold"><?= __("period_debt") ?></small>
                        <h2 class="fw-800 text-warning mb-0 mt-2"><?= number_format($period_debt) ?> <small class="fs-6"><?= __("currency") ?></small></h2>
                    </div>
                    <i class="fas fa-hand-holding-usd fa-3x text-warning opacity-25"></i>
                </div>
            </div>
        </div>
        
        <!-- NET PROFIT HERO CARD -->
        <div class="col-md-6">
            <div class="card border-0 shadow-lg p-4 h-100 text-white rounded-4" style="background: #111827;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-uppercase mb-2" style="color: #e94560; letter-spacing: 2px;"><?= __("profit") ?></h6>
                        <h1 class="display-5 fw-800 text-success mb-0"><?= number_format($final_net_profit) ?> <small class="fs-6 opacity-50"><?= __("currency") ?></small></h1>
                        <p class="small fw-bold mt-2 mb-0" style="color: #ffc107;">
                            <i class="fas fa-info-circle me-1"></i> <?= __("profit_subtitle") ?>
                        </p>
                    </div>
                    <div class="text-end d-none d-sm-block">
                        <i class="fas fa-vault fa-4x text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ITEM PERFORMANCE TABLE -->
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-list-ul me-2 text-muted"></i><?= __("item_breakdown") ?></h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                        <th class="ps-4"><?= __("product") ?></th>
                        <th><?= __("qty_sold") ?></th>
                        <th><?= __("revenue") ?></th>
                        <th class="text-end pe-4 text-success"><?= __("profit_contribution") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $items_stmt = $pdo->prepare("SELECT p.product_name, SUM(si.quantity) as total_qty, 
                                                SUM(si.unit_price * si.quantity) as row_rev,
                                                SUM((si.unit_price - p.purchase_price) * si.quantity) as row_profit
                                                FROM sale_items si 
                                                JOIN products p ON si.product_id = p.id 
                                                JOIN sales s ON si.sale_id = s.id
                                                WHERE $date_query GROUP BY p.id ORDER BY row_profit DESC");
                    $items_stmt->execute();
                    $rows = $items_stmt->fetchAll();
                    if (empty($rows)) echo "<tr><td colspan='4' class='text-center py-5 text-muted'>".__("no_sales_found")."</td></tr>";
                    foreach ($rows as $item):
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><span class="badge bg-light text-dark border fw-normal"><?= $item['total_qty'] ?> <?= __("items") ?></span></td>
                        <td><?= number_format($item['row_rev']) ?></td>
                        <td class="text-end pe-4 fw-bold text-success">+ <?= number_format($item['row_profit']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.border-5 { border-left: 8px solid !important; }
.fw-800 { font-weight: 800; }
.card { transition: 0.3s; }
.card:hover { transform: translateY(-3px); }

@media print {
    .no-print, #sidebar, .btn-toggle-sidebar, .navbar-custom, .footer { display: none !important; }
    #content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .card { border: 1px solid #eee !important; box-shadow: none !important; }
    body { background: white !important; font-size: 14px; }
}
</style>

<?php include 'includes/footer.php'; ?>