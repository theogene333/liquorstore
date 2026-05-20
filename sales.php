<?php
require_once 'config/db.php';

// --- 1. TIMEZONE SETTING (Cairo UTC+3) ---
date_default_timezone_set('Africa/Cairo');
$pdo->exec("SET time_zone = '+03:00'"); 

include 'includes/header.php';
include 'includes/sidebar.php';

// --- 2. FETCH ADMIN VAT SETTING ---
$settingsStmt = $pdo->query("SELECT vat_rate FROM settings WHERE id = 1");
$admin_settings = $settingsStmt->fetch();
$current_vat_percent = $admin_settings ? (float)$admin_settings['vat_rate'] : 0;
$divisor = 1 + ($current_vat_percent / 100);

// --- 3. HANDLE SALE VOIDING (STOCK & DEBT REVERSAL) ---
if (isset($_GET['delete']) && $_SESSION['role'] == 'Super Admin') {
    $sale_id = $_GET['delete'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT total_amount, payment_method, customer_id FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);
        $saleData = $stmt->fetch();

        if ($saleData) {
            $items = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
            $items->execute([$sale_id]);
            while ($item = $items->fetch()) {
                $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?")->execute([$item['quantity'], $item['product_id']]);
            }
            if (strcasecmp($saleData['payment_method'], 'Debt') == 0 && $saleData['customer_id']) {
                $pdo->prepare("UPDATE customers SET total_debt = total_debt - ? WHERE id = ?")->execute([$saleData['total_amount'], $saleData['customer_id']]);
            }
            $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$sale_id]);
            $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$sale_id]);
            $pdo->commit();
            // Multi-language Alert
            echo "<script>alert('".__('transaction_void_success')."'); window.location='sales.php';</script>";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<script>alert('".__('error_occurred')."');</script>";
    }
}

// --- 4. FETCH SALES DATA ---
$query = "SELECT s.*, u.fullname as cashier_name, c.fullname as customer_name 
          FROM sales s 
          JOIN users u ON s.cashier_id = u.id 
          LEFT JOIN customers c ON s.customer_id = c.id 
          ORDER BY s.created_at DESC";
$sales = $pdo->query($query)->fetchAll();
?>

<div class="container-fluid py-2">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h3 class="fw-800 text-dark mb-0"><?= __('sales_payments_title') ?></h3>
            <p class="text-muted small mb-0"><?= __('sales_history_subtitle') ?> • <span class="text-primary fw-bold"><?= __('cairo_time') ?></span></p>
        </div>
        <div>
            <button class="btn btn-outline-dark border-2 rounded-pill me-2 fw-bold px-4 shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-2"></i><?= __('print') ?>
            </button>
            <a href="pos.php" class="btn btn-primary rounded-pill fw-bold px-4 shadow border-0" style="background: var(--accent-red);">
                <i class="fas fa-plus-circle me-2"></i><?= __('add_new_sale') ?>
            </a>
        </div>
    </div>

    <!-- PREMIUM GRADIENT SUMMARY CARDS -->
    <div class="row g-3 mb-4">
        <?php
        $today_gross = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
        $today_debt_amt = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = CURDATE() AND payment_method = 'Debt'")->fetchColumn() ?: 0;
        $today_debt_count = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURDATE() AND payment_method = 'Debt'")->fetchColumn() ?: 0;
        $today_profit = $pdo->query("SELECT SUM((si.unit_price - p.purchase_price) * si.quantity) 
                                    FROM sale_items si JOIN products p ON si.product_id = p.id 
                                    JOIN sales s ON si.sale_id = s.id WHERE DATE(s.created_at) = CURDATE()")->fetchColumn() ?: 0;
        ?>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 text-white rounded-4" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                <small class="text-uppercase fw-bold opacity-75" style="font-size: 0.6rem;"><?= __('today_sales') ?></small>
                <h4 class="fw-800 mb-0"><?= number_format($today_gross) ?></h4>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 text-white rounded-4" style="background: linear-gradient(135deg, #dc2626, #f43f5e);">
                <small class="text-uppercase fw-bold opacity-75" style="font-size: 0.6rem;"><?= __('money_owed') ?></small>
                <h4 class="fw-800 mb-0"><?= number_format($today_debt_amt) ?></h4>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 text-white rounded-4" style="background: #111827;">
                <small class="text-uppercase fw-bold opacity-75" style="font-size: 0.6rem;"><?= __('unpaid_count') ?></small>
                <h4 class="fw-800 mb-0"><?= $today_debt_count ?></h4>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 text-white rounded-4" style="background: linear-gradient(135deg, #059669, #10b981);">
                <small class="text-uppercase fw-bold opacity-75" style="font-size: 0.6rem;"><?= __('true_earnings') ?></small>
                <h4 class="fw-800 mb-0"><?= number_format($today_profit) ?></h4>
            </div>
        </div>
    </div>

    <!-- MODERN DATA TABLE -->
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-800 text-dark"><i class="fas fa-file-invoice me-2 text-muted"></i><?= __('transaction_ledger') ?></h6>
            <div class="input-group input-group-sm w-25">
                <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="salesSearch" class="form-control border-0 bg-light shadow-none" placeholder="<?= __('search') ?>">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="salesTable">
                <thead class="table-light">
                    <tr style="font-size: 0.65rem; letter-spacing: 1px;" class="text-uppercase text-muted">
                        <th class="ps-4"><?= __('timestamp') ?></th>
                        <th><?= __('invoice') ?> #</th>
                        <th><?= __('Cashier') ?></th>
                        <th><?= __('payment_status') ?></th>
                        <th class="text-end"><?= __('final_total') ?></th>
                        <th class="text-center no-print"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php foreach ($sales as $sale): $method = trim($sale['payment_method']); ?>
                    <tr class="transaction-row">
                        <td class="ps-4 small text-muted"><?= date('d M, H:i', strtotime($sale['created_at'])) ?></td>
                        <td class="fw-bold text-dark">#<?= $sale['invoice_no'] ?></td>
                        <td class="small fw-600 text-muted"><?= htmlspecialchars($sale['cashier_name']) ?></td>
                        <td>
                            <?php if(strcasecmp($method, 'Debt') == 0): ?>
                                <div class="d-inline-flex align-items-center bg-danger-subtle px-3 py-1 rounded-3 border border-danger-subtle">
                                    <span class="pulse-red me-2"></span>
                                    <span class="fw-800 text-danger small text-uppercase"><?= __('unpaid_badge') ?></span>
                                    <span class="ms-2 text-dark small fw-bold">| <?= htmlspecialchars($sale['customer_name'] ?? 'N/A') ?></span>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border fw-bold px-3 py-2">
                                    <i class="fas fa-check-circle text-success me-1"></i> <?= $sale['payment_method'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-800 text-end pe-4 text-dark"><?= number_format($sale['total_amount']) ?> <small class="fw-normal text-muted"><?= __('currency') ?></small></td>
                        <td class="text-center no-print">
                            <button class="btn btn-sm btn-light text-primary border-0 rounded-3 shadow-none me-1" onclick="viewInvoice(<?= $sale['id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($_SESSION['role'] == 'Super Admin'): ?>
                                <a href="?delete=<?= $sale['id'] ?>" class="btn btn-sm btn-light text-danger border-0 rounded-3" onclick="return confirm('<?= __('void_confirm') ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold ms-2"><i class="fas fa-receipt me-2 text-danger"></i><?= __('receipt_details') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="invoiceData">
                <div class="text-center p-5"><div class="spinner-border text-danger"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
// Search logic
document.getElementById('salesSearch').addEventListener('keyup', function() {
    let v = this.value.toLowerCase();
    document.querySelectorAll('#salesTable tbody tr').forEach(r => {
        r.style.display = r.innerText.toLowerCase().includes(v) ? '' : 'none';
    });
});

function viewInvoice(saleId) {
    new bootstrap.Modal(document.getElementById('invoiceModal')).show();
    fetch('get_sale_details.php?id=' + saleId)
        .then(res => res.text()).then(data => { document.getElementById('invoiceData').innerHTML = data; });
}
</script>

<style>
    .fw-800 { font-weight: 800; }
    .fw-600 { font-weight: 600; }
    .transaction-row:hover { background-color: #f8fafc !important; }
    .bg-danger-subtle { background-color: rgba(220, 38, 38, 0.08) !important; }
    
    .pulse-red {
        width: 8px; height: 8px; background: #dc2626; border-radius: 50%;
        animation: pulse-red-anim 2s infinite;
    }
    @keyframes pulse-red-anim {
        0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
    }

    @media print {
        .no-print, #sidebar, .navbar-custom, .btn-toggle-sidebar { display: none !important; }
        #content { margin-left: 0 !important; width: 100% !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
    }
</style>

<?php include 'includes/footer.php'; ?>