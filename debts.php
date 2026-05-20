<?php
require_once 'config/db.php';

// --- 1. TIMEZONE SETTING (Cairo UTC+3) ---
date_default_timezone_set('Africa/Cairo');
$pdo->exec("SET time_zone = '+03:00'");

include 'includes/header.php';
include 'includes/sidebar.php';

$can_manage = ($_SESSION['role'] == 'Super Admin' || $_SESSION['role'] == 'Manager');
$message = "";

// --- 2. LOGIC: ADD CUSTOMER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $initial_debt = $_POST['initial_debt'] ?: 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO customers (fullname, phone, email, total_debt) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullname, $phone, $email, $initial_debt]);
        $message = "<div class='alert alert-success border-0 shadow-sm rounded-4 animate__animated animate__fadeIn'><b>".__("success")."!</b> ".__("customer_added_success")."</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger border-0 shadow-sm rounded-4'>".__("duplicate_phone_error")."</div>";
    }
}

// --- 3. LOGIC: RECORD REPAYMENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    $customer_id = $_POST['pay_customer_id'];
    $pay_amount = $_POST['pay_amount'];

    if ($pay_amount > 0) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE customers SET total_debt = total_debt - ? WHERE id = ?")->execute([$pay_amount, $customer_id]);
            $pdo->prepare("INSERT INTO customer_payments (customer_id, amount_paid, recorded_by) VALUES (?, ?, ?)")
                ->execute([$customer_id, $pay_amount, $_SESSION['user_id']]);
            $pdo->commit();
            $message = "<div class='alert alert-success border-0 shadow-sm rounded-4'>".__("payment_recorded_success")."</div>";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<div class='alert alert-danger border-0 shadow-sm rounded-4'>".__("payment_error")."</div>";
        }
    }
}

// --- 4. DATA FETCHING ---
$customers = $pdo->query("SELECT * FROM customers ORDER BY total_debt DESC, fullname ASC")->fetchAll();
$total_debt_sum = $pdo->query("SELECT SUM(total_debt) FROM customers")->fetchColumn() ?: 0;
$total_cust_count = count($customers);
?>

<div class="container-fluid py-2">
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h3 class="fw-800 text-dark mb-0"><?= __("debts") ?></h3>
            <p class="text-muted small mb-0"><?= __("debts_subtitle") ?></p>
        </div>
        <button class="btn btn-primary rounded-pill shadow px-4 fw-bold border-0 py-2" data-bs-toggle="modal" data-bs-target="#addCustomerModal" style="background: var(--accent-red);">
            <i class="fas fa-user-plus me-2"></i><?= __("register_new_debtor") ?>
        </button>
    </div>

    <?= $message ?>

    <!-- PREMIUM GRADIENT STAT CARDS -->
    <div class="row g-3 mb-4">
        <!-- Total Receivables Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-lg p-3 text-white rounded-4 stat-card-premium" style="background: linear-gradient(135deg, #dc2626, #f43f5e);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-uppercase fw-800 opacity-75" style="font-size: 0.65rem; letter-spacing: 1px;"><?= __("total_receivables") ?></small>
                        <h2 class="fw-800 mb-0 mt-2"><?= number_format($total_debt_sum) ?> <small class="fs-6"><?= __("currency") ?></small></h2>
                    </div>
                    <div class="stat-icon-bg"><i class="fas fa-hand-holding-usd"></i></div>
                </div>
            </div>
        </div>
        <!-- Active Debtors Card -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 text-white rounded-4 stat-card-premium" style="background: #111827;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-uppercase fw-800 opacity-50" style="font-size: 0.65rem; letter-spacing: 1px;"><?= __("active_debtors") ?></small>
                        <h2 class="fw-800 mb-0 mt-2"><?= $total_cust_count ?> <small class="fs-6 opacity-50"><?= __("persons") ?></small></h2>
                    </div>
                    <div class="stat-icon-bg"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- REFINED DATA TABLE -->
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="mb-0 fw-800 text-dark"><i class="fas fa-list-ul me-2 text-muted"></i><?= __("customer_ledger") ?></h6>
            <div class="input-group input-group-sm w-25">
                <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="debtSearch" class="form-control border-0 bg-light shadow-none" placeholder="<?= __("search_customer_placeholder") ?>">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="debtTable">
                <thead class="table-light">
                    <tr style="font-size: 0.65rem;" class="text-uppercase text-muted fw-bold">
                        <th class="ps-4 py-3"><?= __("customer_identity") ?></th>
                        <th><?= __("contact") ?></th>
                        <th><?= __("debt_balance") ?></th>
                        <th class="text-center"><?= __("actions") ?></th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php foreach ($customers as $c): ?>
                    <tr class="customer-row">
                        <td class="ps-4">
                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($c['fullname']) ?></div>
                            <small class="text-muted"><?= __("id") ?>: #CUST-0<?= $c['id'] ?></small>
                        </td>
                        <td>
                            <div class="fw-bold small text-dark"><i class="fas fa-phone-alt me-2 text-muted small"></i><?= $c['phone'] ?></div>
                            <div class="small text-muted"><i class="fas fa-envelope me-2 text-muted small"></i><?= $c['email'] ?: 'N/A' ?></div>
                        </td>
                        <td>
                            <?php if($c['total_debt'] > 0): ?>
                                <div class="d-inline-flex align-items-center bg-danger-subtle px-3 py-1 rounded-3 border border-danger-subtle">
                                    <span class="pulse-red me-2"></span>
                                    <span class="fw-800 text-danger fs-5"><?= number_format($c['total_debt']) ?></span>
                                    <small class="ms-1 text-danger fw-bold"><?= __("currency") ?></small>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-800">
                                    <i class="fas fa-check-circle me-1"></i> <?= __("cleared_status") ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($can_manage && $c['total_debt'] > 0): ?>
                                <button class="btn btn-sm btn-light text-success fw-800 rounded-pill px-4 shadow-none border-0" onclick="openPayModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['fullname'], ENT_QUOTES) ?>', <?= $c['total_debt'] ?>)">
                                    <i class="fas fa-coins me-1"></i> <?= __("receive_payment") ?>
                                </button>
                            <?php else: ?>
                                <span class="text-muted small italic opacity-50"><?= __("no_balance") ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: ADD CUSTOMER (Refined UI) -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold ms-2"><?= __("register_new_customer") ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted"><?= __("fullname") ?></label>
                    <input type="text" name="fullname" class="form-control rounded-3 shadow-none border-light-subtle" placeholder="Jean Paul" required>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted"><?= __("phone_number") ?></label>
                        <input type="text" name="phone" class="form-control rounded-3 shadow-none border-light-subtle" placeholder="078..." required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-danger"><?= __("opening_debt") ?></label>
                        <input type="number" name="initial_debt" class="form-control rounded-3 shadow-none border-danger-subtle" value="0">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label small fw-bold text-muted"><?= __("email_optional") ?></label>
                    <input type="email" name="email" class="form-control rounded-3 shadow-none border-light-subtle">
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="submit" name="add_customer" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow border-0" style="background: var(--accent-red);"><?= __("create_account") ?></button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: RECORD PAYMENT (Compact UI) -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden text-center">
            <div class="modal-header bg-success text-white border-0 justify-content-center">
                <h6 class="modal-title fw-bold"><i class="fas fa-coins me-2"></i><?= __("cash_repayment") ?></h6>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="pay_customer_id" id="pay_customer_id">
                <p class="text-muted small mb-1"><?= __("customer_name") ?>:</p>
                <h5 id="pay_name" class="fw-bold mb-4 text-dark"></h5>
                
                <div class="p-3 rounded-4" style="background: #f0fdf4; border: 2px dashed #10b981;">
                    <label class="form-label small fw-800 text-success text-uppercase"><?= __("payment_amount") ?></label>
                    <input type="number" name="pay_amount" id="pay_input" class="form-control form-control-lg text-center fw-800 border-0 bg-transparent text-success shadow-none" placeholder="0" required>
                    <div class="border-top mt-2 pt-2"><small id="debt_info" class="text-muted fw-bold"></small></div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="submit" name="record_payment" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow"><?= __("complete_payment") ?></button>
            </div>
        </form>
    </div>
</div>

<script>
// Logic stays same, ensuring professional speed
document.getElementById('debtSearch').addEventListener('keyup', function() {
    let val = this.value.toLowerCase();
    document.querySelectorAll('#debtTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
});

function openPayModal(id, name, debt) {
    document.getElementById('pay_customer_id').value = id;
    document.getElementById('pay_name').innerText = name;
    document.getElementById('pay_input').max = debt;
    document.getElementById('debt_info').innerText = debt.toLocaleString() + " <?= __("currency") ?>";
    new bootstrap.Modal(document.getElementById('payModal')).show();
}
</script>

<style>
    .fw-800 { font-weight: 800; }
    .customer-row:hover { background-color: #f8fafc !important; }
    .stat-card-premium { position: relative; overflow: hidden; border-radius: 24px; transition: 0.3s; }
    .stat-card-premium:hover { transform: translateY(-5px); }
    .stat-icon-bg { font-size: 3rem; opacity: 0.1; position: absolute; right: -5px; bottom: -5px; transform: rotate(-15deg); }
    .bg-danger-subtle { background-color: rgba(220, 38, 38, 0.05) !important; }
    .bg-success-subtle { background-color: rgba(16, 185, 129, 0.05) !important; }
    
    /* UNPAID Pulse Indicator */
    .pulse-red { width: 8px; height: 8px; background: #dc2626; border-radius: 50%; animation: pulse-red-anim 2s infinite; }
    @keyframes pulse-red-anim {
        0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
    }
</style>

<?php include 'includes/footer.php'; ?>