<?php
require_once 'config/db.php';
// lang_config.php is included in header.php

// --- 1. TIMEZONE SETTING (Cairo UTC+3) ---
date_default_timezone_set('Africa/Cairo');
$pdo->exec("SET time_zone = '+03:00'"); 

include 'includes/header.php';
include 'includes/sidebar.php';

// Restricted Access
if ($_SESSION['role'] == 'Cashier' || $_SESSION['role'] == 'Waiter') {
    echo "<div class='alert alert-danger m-4 shadow-sm'><i class='fas fa-exclamation-shield me-2'></i>".__("access_denied").". ".__("restricted_intel_msg")."</div>";
    include 'includes/footer.php';
    exit();
}

// 1. FETCH ADMIN VAT SETTINGS
$settingsStmt = $pdo->query("SELECT vat_rate FROM settings WHERE id = 1");
$admin_settings = $settingsStmt->fetch();
$current_vat_percent = $admin_settings ? (float)$admin_settings['vat_rate'] : 0;
$divisor = 1 + ($current_vat_percent / 100);

// 2. Handle Date Filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); 
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$sql_start = $start_date . " 00:00:00";
$sql_end = $end_date . " 23:59:59";

// 3. Fetch Aggregated Statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(id) as total_tx, 
    SUM(total_amount) as total_revenue 
    FROM sales WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$sql_start, $sql_end]);
$stats = $stmt->fetch();

$total_gross = $stats['total_revenue'] ?: 0;
$total_transactions = $stats['total_tx'] ?: 0;

// REVERSE TAX FORMULA: Extract Net and Tax from Gross
$total_net = $total_gross / $divisor;
$total_tax_amount = $total_gross - $total_net;

/**
 * 4. FULL PROFIT LOGIC
 * Calculation: (Sold Price - Supplier Purchase Price) * Quantity
 * This calculates the true margin generated from products supplied.
 */
$profit_stmt = $pdo->prepare("SELECT SUM((si.unit_price - p.purchase_price) * si.quantity) 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    JOIN sales s ON si.sale_id = s.id
    WHERE s.created_at BETWEEN ? AND ?");
$profit_stmt->execute([$sql_start, $sql_end]);
$total_profit = $profit_stmt->fetchColumn() ?: 0;

// 5. Fetch Detailed Sales List
$stmt = $pdo->prepare("SELECT s.*, u.fullname, c.fullname as customer_name 
    FROM sales s 
    JOIN users u ON s.cashier_id = u.id 
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.created_at BETWEEN ? AND ? 
    ORDER BY s.created_at DESC");
$stmt->execute([$sql_start, $sql_end]);
$sales_list = $stmt->fetchAll();

// 6. FETCH TOTAL OUTSTANDING CUSTOMER DEBT
$total_customer_debt = $pdo->query("SELECT SUM(total_debt) FROM customers")->fetchColumn() ?: 0;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="h3 fw-bold text-dark mb-0"><?= __("sales_audit_report") ?></h2>
            <p class="text-muted small"><?= __("live_status_as_of") ?>: <b><?= date('d M Y, H:i A') ?> (<?= __("cairo") ?>)</b> | <?= __("tax_vat") ?>: <b><?= $current_vat_percent ?>%</b></p>
        </div>
        <button class="btn btn-outline-dark px-4 shadow-sm" onclick="window.print()">
            <i class="fas fa-print me-2"></i> <?= __("print") ?>
        </button>
    </div>

    <!-- Filter Form -->
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase text-muted"><?= __("from_date") ?></label>
                    <input type="date" name="start_date" class="form-control shadow-none" value="<?= $start_date ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase text-muted"><?= __("to_date") ?></label>
                    <input type="date" name="end_date" class="form-control shadow-none" value="<?= $end_date ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> <?= __("apply_filter") ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-3 mb-4 text-center">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-bold"><?= __("gross_revenue") ?></span>
                <h3 class="fw-bold text-primary mb-0"><?= number_format($total_gross) ?></h3>
                <small class="text-muted"><?= __("total_collections") ?></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-bold"><?= __("net_sales") ?></span>
                <h3 class="fw-bold text-dark mb-0"><?= number_format($total_net) ?></h3>
                <small class="text-muted"><?= __("revenue_excl_tax") ?></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-bold"><?= __("vat") ?> (<?= $current_vat_percent ?>%)</span>
                <h3 class="fw-bold text-danger mb-0"><?= number_format($total_tax_amount) ?></h3>
                <small class="text-muted"><?= __("tax_obligation") ?></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-dark text-white">
                <span class="small text-uppercase fw-bold" style="color: #e94560;"><?= __("profit") ?></span>
                <h3 class="fw-bold text-success mb-0"><?= number_format($total_profit) ?></h3>
                
                <div class="mt-2">
                    <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.65rem;">
                        <i class="fas fa-calculator me-1"></i> <?= __("revenue_minus_stock_cost") ?>
                    </span>
                </div>
                <small class="text-white-50 d-block mt-1"><?= __("true_earnings") ?></small>
            </div>
        </div>
    </div>

    <!-- TOTAL OUTSTANDING DEBT -->
    <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center p-3 mb-4">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-hand-holding-usd me-2"></i><?= __("total_outstanding_customer_debts") ?></h6>
            <small class="text-muted"><?= __("accumulated_unpaid_msg") ?></small>
        </div>
        <div class="text-end text-dark">
            <h4 class="fw-bold text-danger mb-0"><?= number_format($total_customer_debt) ?> <?= __("currency") ?></h4>
            <a href="debts.php" class="text-decoration-none small fw-bold text-primary no-print"><?= __("manage_debts") ?> <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
    </div>

    <!-- Detailed List -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list-ul me-2"></i><?= __("detailed_transaction_log") ?> (<?= __("cairo_time") ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase">
                    <tr style="font-size: 0.7rem;">
                        <th class="ps-3"><?= __("date_time") ?></th>
                        <th><?= __("invoice") ?> #</th>
                        <th><?= __("cashier") ?></th>
                        <th><?= __("method") ?></th>
                        <th class="text-end"><?= __("net") ?></th>
                        <th class="text-end"><?= __("tax") ?></th>
                        <th class="text-end pe-3"><?= __("final_total") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales_list)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted"><?= __("no_sales_found") ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($sales_list as $row): 
                        $row_net = $row['total_amount'] / $divisor;
                        $row_tax = $row['total_amount'] - $row_net;
                    ?>
                    <tr>
                        <td class="ps-3 small"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td class="fw-bold text-dark">#<?= $row['invoice_no'] ?></td>
                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                        <td>
                            <?php if(strcasecmp($row['payment_method'], 'Debt') == 0): ?>
                                <span class="badge" style="background-color: #ffc107; color: #000; border: 1px solid #e0a800; font-size: 0.65rem;"><?= __("debt_payment") ?></span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border fw-normal"><?= $row['payment_method'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted small"><?= number_format($row_net) ?></td>
                        <td class="text-end text-muted small"><?= number_format($row_tax) ?></td>
                        <td class="text-end pe-3 fw-bold text-primary"><?= number_format($row['total_amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-success-subtle { background-color: rgba(25, 135, 84, 0.15) !important; }
    @media print {
        .no-print, #sidebar, .btn-toggle-sidebar, .navbar-custom, .footer { display: none !important; }
        #content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
        body { background: white !important; font-size: 12px; }
    }
</style>

<?php include 'includes/footer.php'; ?>