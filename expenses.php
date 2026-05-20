<?php
require_once 'config/db.php';
// lang_config.php is included in header.php

// --- 1. HANDLE EXPENSE DELETION ---
if (isset($_GET['delete']) && $_SESSION['role'] == 'Super Admin') {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    
    // Fix for Headers Sent error: Use JS redirect with translated message
    echo "<script>window.location='expenses.php?msg=Deleted';</script>";
    exit();
}

// --- 2. HANDLE ADD EXPENSE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_expense'])) {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $payment_status = $_POST['payment_status']; 
    $expense_date = $_POST['expense_date'];
    $description = trim($_POST['description']);

    $sql = "INSERT INTO expenses (title, category, amount, payment_status, expense_date, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute([$title, $category, $amount, $payment_status, $expense_date, $description])) {
        echo "<script>window.location='expenses.php?msg=Success';</script>";
        exit();
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';

// --- 3. DATA FETCHING ---
$expenses = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC")->fetchAll();
$total_expenses = $pdo->query("SELECT SUM(amount) FROM expenses")->fetchColumn() ?: 0;
?>

<div class="container-fluid py-2">
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-4 gap-3 no-print">
        <div>
            <h2 class="h3 mb-0 fw-800 text-dark"><i class="fas fa-wallet text-danger me-2"></i> <?= __("expenses") ?></h2>
            <p class="text-muted small mb-0"><?= __("expenses_subtitle") ?></p>
        </div>
        <button class="btn btn-danger rounded-pill shadow-sm px-4 fw-bold border-0 py-2" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="fas fa-plus-circle me-2"></i> <?= __("record_expense") ?>
        </button>
    </div>

    <!-- SUMMARY CARD -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 rounded-4 border-start border-danger border-4">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-danger-subtle p-3 rounded-3 me-3 text-danger">
                        <i class="fas fa-money-bill-transfer fa-2x"></i>
                    </div>
                    <div>
                        <small class="text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.65rem;"><?= __("total_expenses") ?></small>
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($total_expenses) ?> <small class="fs-6 text-muted"><?= __("currency") ?></small></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- EXPENSES TABLE -->
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.75rem;" class="text-uppercase text-muted fw-bold">
                        <th class="ps-4 py-3"><?= __("timestamp") ?></th>
                        <th><?= __("expense_title") ?></th>
                        <th><?= __("category") ?></th>
                        <th><?= __("amount") ?></th>
                        <th><?= __("payment_status") ?></th>
                        <th><?= __("description") ?></th>
                        <th class="text-center no-print"><?= __("actions") ?></th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($expenses)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted fw-bold"><?= __("no_expenses_found") ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($expenses as $exp): ?>
                    <tr class="expense-row">
                        <td class="ps-4 small text-muted"><?= date('d M Y', strtotime($exp['expense_date'])) ?></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($exp['title']) ?></td>
                        <!-- CATEGORY TRANSLATION: Using lowercase key for consistency -->
                        <td><span class="badge bg-light text-dark border fw-normal"><?= __(strtolower($exp['category'])) ?></span></td>
                        <td class="text-danger fw-bold"><?= number_format($exp['amount']) ?> <?= __("currency") ?></td>
                        <td>
                            <?php if($exp['payment_status'] == 'Debit'): ?>
                                <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-clock me-1"></i> <?= __("unpaid_status") ?></span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> <?= __("paid_status") ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($exp['description']) ?></td>
                        <td class="text-center no-print">
                            <?php if ($_SESSION['role'] == 'Super Admin'): ?>
                                <a href="expenses.php?delete=<?= $exp['id'] ?>" class="btn btn-sm btn-light text-danger border-0 rounded-3" onclick="return confirm('<?= __("confirm_delete_expense") ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small opacity-50">--</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: ADD EXPENSE -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="expenses.php" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title fw-bold ms-2"><?= __("record_expense") ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted"><?= __("expense_title_label") ?></label>
                    <input type="text" name="title" class="form-control rounded-3 shadow-none border-light-subtle" placeholder="<?= __('e.g. Rent') ?>" required>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted"><?= __("category") ?></label>
                        <select name="category" class="form-select rounded-3 shadow-none border-light-subtle" required>
                            <option value="Rent"><?= __("rent") ?></option>
                            <option value="Electricity"><?= __("electricity") ?></option>
                            <option value="Water"><?= __("water") ?></option>
                            <option value="Salaries"><?= __("salaries") ?></option>
                            <option value="Transport"><?= __("transport") ?></option>
                            <option value="Taxes"><?= __("taxes") ?></option>
                            <option value="Maintenance"><?= __("maintenance") ?></option>
                            <option value="Other"><?= __("other") ?></option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted"><?= __("amount") ?></label>
                        <input type="number" name="amount" class="form-control rounded-3 shadow-none border-light-subtle fw-bold" required>
                    </div>
                </div>
                
                <div class="my-3">
                    <label class="form-label small fw-bold text-muted"><?= __("payment_status") ?></label>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_status" id="p1" value="Paid" checked>
                            <label class="form-check-label small fw-bold" for="p1"><?= __("paid_status") ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_status" id="p2" value="Debit">
                            <label class="form-check-label small fw-bold text-danger" for="p2"><?= __("unpaid_status") ?> (Debit)</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted"><?= __("expense_date") ?></label>
                    <input type="date" name="expense_date" class="form-control rounded-3 shadow-none border-light-subtle" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold text-muted"><?= __("description") ?></label>
                    <textarea name="description" class="form-control rounded-3 shadow-none border-light-subtle" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="submit" name="save_expense" class="btn btn-danger px-5 fw-bold rounded-pill shadow-sm"><?= __("save_expense") ?></button>
            </div>
        </form>
    </div>
</div>

<style>
    .bg-danger-subtle { background-color: rgba(220, 38, 38, 0.05) !important; }
    .bg-success-subtle { background-color: rgba(16, 185, 129, 0.1) !important; }
    .expense-row:hover { background-color: #f8fafc !important; }
    .fw-800 { font-weight: 800; }
</style>

<?php include 'includes/footer.php'; ?>