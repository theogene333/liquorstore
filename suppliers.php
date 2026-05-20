<?php
require_once 'config/db.php';
// lang_config.php is included in header.php (which defines the __() function)

// --- 1. TIMEZONE SETTING (Cairo UTC+3) ---
date_default_timezone_set('Africa/Cairo');
$pdo->exec("SET time_zone = '+03:00'");

include 'includes/header.php';
include 'includes/sidebar.php';

// Security: Only Super Admin and Manager can modify suppliers
$can_edit = ($_SESSION['role'] == 'Super Admin' || $_SESSION['role'] == 'Manager');

$message = "";

// 1. Handle Delete Supplier
if (isset($_GET['delete']) && $_SESSION['role'] == 'Super Admin') {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
    if($stmt->execute([$id])) {
        $message = "<div class='alert alert-warning alert-dismissible fade show'>".__('supplier_deleted_msg')."</div>";
    }
}

// 2. Handle Add/Update Supplier
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_supplier'])) {
    $name = $_POST['supplier_name'];
    $contact = $_POST['contact_person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $debt = $_POST['total_debt'] ?: 0;

    if (isset($_POST['supplier_id']) && !empty($_POST['supplier_id'])) {
        $sql = "UPDATE suppliers SET supplier_name=?, contact_person=?, phone=?, email=?, address=?, total_debt=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $contact, $phone, $email, $address, $debt, $_POST['supplier_id']]);
        $message = "<div class='alert alert-info alert-dismissible fade show'>".__('supplier_updated_msg')."</div>";
    } else {
        $sql = "INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, total_debt) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $contact, $phone, $email, $address, $debt]);
        $message = "<div class='alert alert-success alert-dismissible fade show'>".__('supplier_added_msg')."</div>";
    }
}

// 3. Handle Debt Payment Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $s_id = $_POST['pay_supplier_id'];
    $amount = $_POST['pay_amount'];

    if ($amount > 0) {
        $stmt = $pdo->prepare("UPDATE suppliers SET total_debt = total_debt - ? WHERE id = ?");
        $stmt->execute([$amount, $s_id]);
        $message = "<div class='alert alert-success alert-dismissible fade show text-center fw-bold'>".__('payment_recorded_success')." " . number_format($amount) . " " . __('currency') . ".</div>";
    }
}

// 4. Fetch Statistics and Data
$total_debt = $pdo->query("SELECT SUM(total_debt) FROM suppliers")->fetchColumn() ?: 0;
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY total_debt DESC, supplier_name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 fw-bold text-dark"><i class="fas fa-truck-moving text-primary me-2"></i> <?= __('suppliers') ?></h2>
        <p class="text-muted small mb-0"><?= __('distributor_registry') ?></p>
    </div>
    <?php if($can_edit): ?>
    <button class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="resetSupplierForm()">
        <i class="fas fa-plus me-2"></i> <?= __('register_new_supplier') ?>
    </button>
    <?php endif; ?>
</div>

<?= $message ?>

<!-- Debt Summary Card -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-dark text-white p-3">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-danger p-3 me-3">
                    <i class="fas fa-file-invoice-dollar fa-2x"></i>
                </div>
                <div>
                    <small class="text-white-50 fw-bold text-uppercase" style="font-size: 0.7rem;"><?= __('outstanding_debt') ?></small>
                    <h3 class="fw-bold mb-0 text-warning"><?= number_format($total_debt) ?> <small class="fs-6 text-white-50"><?= __('currency') ?></small></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Suppliers Table -->
<div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4"><?= __('distributor_company') ?></th>
                    <th><?= __('contact_person') ?></th>
                    <th><?= __('contact') ?></th>
                    <th><?= __('debt_balance') ?></th>
                    <th class="text-center"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($suppliers)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted"><?= __('no_suppliers_registered') ?></td></tr>
                <?php endif; ?>

                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['supplier_name']) ?></div>
                        <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($s['address']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($s['contact_person']) ?></td>
                    <td>
                        <div class="small fw-bold"><i class="fas fa-phone-alt text-muted me-2"></i><?= htmlspecialchars($s['phone']) ?></div>
                        <div class="small text-muted"><i class="fas fa-envelope text-muted me-2"></i><?= htmlspecialchars($s['email']) ?></div>
                    </td>
                    <td>
                        <?php if($s['total_debt'] > 0): ?>
                            <div class="text-danger fw-bold fs-5"><?= number_format($s['total_debt']) ?> <?= __('currency') ?></div>
                            <button class="btn btn-sm btn-link text-success p-0 text-decoration-none fw-bold" onclick="openPaymentModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['supplier_name']) ?>', <?= $s['total_debt'] ?>)">
                                <i class="fas fa-hand-holding-usd me-1"></i> <?= __('make_payment') ?>
                            </button>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success px-3"><?= __('cleared_status') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if($can_edit): ?>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick='editSupplier(<?= json_encode($s) ?>)'><i class="fas fa-edit me-2 text-primary"></i> <?= __('edit_details') ?></a></li>
                                <?php if($_SESSION['role'] == 'Super Admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="suppliers.php?delete=<?= $s['id'] ?>" onclick="return confirm('<?= __('confirm_delete_supplier') ?>')"><i class="fas fa-trash me-2"></i> <?= __('delete') ?></a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add / Edit Supplier -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="suppliers.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="mTitle"><?= __('supplier_profile') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="supplier_id" id="supplier_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><?= __('distributor_company') ?></label>
                        <input type="text" name="supplier_name" id="supplier_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><?= __('contact_person') ?></label>
                        <input type="text" name="contact_person" id="contact_person" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><?= __('phone_number') ?></label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><?= __('email_address') ?></label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold"><?= __('address') ?></label>
                        <input type="text" name="address" id="address" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-danger"><?= __('initial_credit_debt') ?> (<?= __('currency') ?>)</label>
                        <input type="number" name="total_debt" id="total_debt" class="form-control" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?? 'Cancel' ?></button>
                <button type="submit" name="save_supplier" class="btn btn-primary px-4"><?= __('save_information') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Debt Payment -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form action="suppliers.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title"><i class="fas fa-coins me-2"></i><?= __('record_payment') ?></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" name="pay_supplier_id" id="pay_supplier_id">
                <p class="text-muted small mb-1"><?= __('paying_to') ?>:</p>
                <h5 id="pay_name" class="fw-bold mb-3"></h5>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase"><?= __('payment_amount') ?></label>
                    <input type="number" name="pay_amount" id="pay_input" class="form-control form-control-lg text-center fw-bold text-success" required placeholder="0">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" name="process_payment" class="btn btn-success w-100 py-2 fw-bold"><?= __('confirm_payment') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function resetSupplierForm() {
    document.getElementById('supplier_id').value = '';
    document.getElementById('supplier_name').value = '';
    document.getElementById('mTitle').innerText = '<?= __('register_new_supplier') ?>';
}
function editSupplier(data) {
    document.getElementById('supplier_id').value = data.id;
    document.getElementById('supplier_name').value = data.supplier_name;
    document.getElementById('contact_person').value = data.contact_person;
    document.getElementById('phone').value = data.phone;
    document.getElementById('email').value = data.email;
    document.getElementById('address').value = data.address;
    document.getElementById('total_debt').value = data.total_debt;
    document.getElementById('mTitle').innerText = '<?= __('edit_supplier_profile') ?>';
    new bootstrap.Modal(document.getElementById('supplierModal')).show();
}
function openPaymentModal(id, name, debt) {
    document.getElementById('pay_supplier_id').value = id;
    document.getElementById('pay_name').innerText = name;
    document.getElementById('pay_input').value = debt;
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}
</script>
<?php include 'includes/footer.php'; ?>