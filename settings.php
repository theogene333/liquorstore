<?php
require_once 'config/db.php';

// --- 0. DATABASE ROW INSURANCE ---
$pdo->query("INSERT IGNORE INTO settings (id, store_name, vat_rate, currency) VALUES (1, 'Liquor Store RW', 0.00, 'RWF')");

// SECURITY Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    include 'includes/header.php';
    include 'includes/sidebar.php';
    // Multi-language: Access Denied
    echo "<div class='container-fluid p-5'><div class='alert alert-danger shadow border-0 text-center'><h4 class='fw-bold'>".$lang['staff_access_denied']."</h4><hr><a href='index.php' class='btn btn-danger btn-sm px-4'>Return</a></div></div>";
    include 'includes/footer.php';
    exit();
}

$message = "";

// --- 1. SMS / MESSAGE SENDING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_sms'])) {
    $target_phone = $_POST['user_phone'];
    $sms_body = trim($_POST['sms_message']);

    if (!empty($target_phone) && !empty($sms_body)) {
        $message = "<div class='alert alert-success border-0 shadow-sm'>
            <i class='fas fa-paper-plane me-2'></i> Message successfully queued for <b>$target_phone</b>: \"$sms_body\"
        </div>";
    } else {
        $message = "<div class='alert alert-danger'>".$lang['error_occurred']."</div>";
    }
}

// --- 2. DATA CLEARING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_data'])) {
    $type = $_POST['clear_type'];
    try {
        if ($type === 'sales') {
            $pdo->exec("TRUNCATE TABLE sale_items");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE sales; SET FOREIGN_KEY_CHECKS = 1;");
            $message = "<div class='alert alert-warning border-0 shadow-sm'><i class='fas fa-trash-alt me-2'></i> ".$lang['success']."</div>";
        } elseif ($type === 'expenses') {
            $pdo->exec("TRUNCATE TABLE expenses");
            $message = "<div class='alert alert-warning border-0 shadow-sm'><i class='fas fa-trash-alt me-2'></i> ".$lang['success']."</div>";
        }
    } catch (PDOException $e) { $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>"; }
}

// --- 3. HANDLE STORE PROFILE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['store_name'] ?? ''; $tin = $_POST['tin_number'] ?? ''; $ebm = $_POST['ebm_vcode'] ?? '';
    $phone = $_POST['phone'] ?? ''; $email = $_POST['email'] ?? ''; $addr = $_POST['address'] ?? ''; $footer = $_POST['receipt_footer'] ?? ''; 
    $sql = "UPDATE settings SET store_name=?, tin_number=?, ebm_vcode=?, phone=?, email=?, address=?, receipt_footer=? WHERE id=1";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute([$name, $tin, $ebm, $phone, $email, $addr, $footer])) { $message = "<div class='alert alert-success border-0 shadow-sm'>".$lang['success']."</div>"; }
}

// --- 4. HANDLE VAT & CURRENCY ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_config'])) {
    $vat = $_POST['vat_rate']; $cur = $_POST['currency'] ?? 'RWF';
    $pdo->prepare("UPDATE settings SET vat_rate=?, currency=? WHERE id=1")->execute([$vat, $cur]);
    $message = "<div class='alert alert-info border-0 shadow-sm'>".$lang['success']."</div>";
}

include 'includes/header.php';
include 'includes/sidebar.php';

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$staff_members = $pdo->query("SELECT fullname, phone, role_id FROM users WHERE phone IS NOT NULL AND phone != ''")->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="h3 fw-bold text-dark"><i class="fas fa-cogs text-primary me-2"></i><?= $lang['settings_title'] ?></h2>
        <p class="text-muted small"><?= $lang['settings_subtitle'] ?></p>
    </div>
</div>

<?= $message ?>

<div class="row g-4">
    <div class="col-lg-8">
        
        <!-- STAFF COMMUNICATION -->
        <div class="card border-0 shadow-sm mb-4 border-start border-primary border-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-comment-alt me-2"></i><?= $lang['sms_portal'] ?></h6>
                <span class="badge bg-primary rounded-pill"><?= $lang['total_reg'] ?>: <?= $user_count ?></span>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold"><?= $lang['select_staff_recipient'] ?></label>
                            <select name="user_phone" class="form-select shadow-none" required>
                                <option value=""><?= $lang['choose_staff'] ?></option>
                                <?php foreach($staff_members as $staff): ?>
                                    <option value="<?= $staff['phone'] ?>"><?= $staff['fullname'] ?> (<?= $staff['phone'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold"><?= $lang['msg_content'] ?></label>
                            <div class="input-group">
                                <input type="text" name="sms_message" class="form-control shadow-none" placeholder="<?= $lang['msg_placeholder'] ?>" required>
                                <button type="submit" name="send_sms" class="btn btn-primary px-4 fw-bold">
                                    <i class="fas fa-paper-plane"></i> <?= $lang['send_sms_btn'] ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-muted" style="font-size: 0.7rem;">
                        <i class="fas fa-info-circle me-1"></i> <?= $lang['sms_info'] ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- DATA MANAGEMENT -->
        <div class="card border-0 shadow-sm mb-4 border-start border-danger border-4">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold text-danger"><i class="fas fa-database me-2"></i><?= $lang['data_cleanup'] ?></h6></div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="POST" onsubmit="return confirm('<?= $lang['conf_clear_sales'] ?>');">
                            <input type="hidden" name="clear_type" value="sales"><button type="submit" name="clear_data" class="btn btn-outline-danger w-100 py-2 fw-bold"><?= $lang['clear_sales'] ?></button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" onsubmit="return confirm('<?= $lang['conf_clear_expenses'] ?>');">
                            <input type="hidden" name="clear_type" value="expenses"><button type="submit" name="clear_data" class="btn btn-outline-dark w-100 py-2 fw-bold"><?= $lang['clear_expenses'] ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- VAT CONFIGURATION -->
        <div class="card border-0 shadow-sm mb-4 border-start border-success border-4">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold text-success"><i class="fas fa-percentage me-2"></i><?= $lang['vat_config'] ?></h6></div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row align-items-end g-3">
                        <div class="col-md-4"><label class="small fw-bold">VAT %</label><input type="number" step="0.01" name="vat_rate" class="form-control fw-bold" value="<?= $settings['vat_rate'] ?>" required></div>
                        <div class="col-md-4"><label class="small fw-bold"><?= $lang['currency'] ?></label><select name="currency" class="form-select fw-bold"><option value="RWF" <?= ($settings['currency'] == 'RWF') ? 'selected' : '' ?>>RWF</option></select></div>
                        <div class="col-md-4"><button type="submit" name="update_config" class="btn btn-success w-100 fw-bold"><?= $lang['save_tax'] ?></button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm bg-primary text-white p-4 mb-4">
            <h6 class="fw-bold mb-3 text-uppercase small"><?= $lang['system_status'] ?></h6>
            <div class="d-flex justify-content-between mb-2"><span><?= $lang['inventory_items'] ?>:</span> <b><?= $product_count ?></b></div>
            <div class="d-flex justify-content-between mb-2"><span><?= $lang['active_debtors'] ?>:</span> <b><?= $user_count ?></b></div>
            <div class="d-flex justify-content-between"><span><?= $lang['vat'] ?>:</span> <b><?= $settings['vat_rate'] ?> %</b></div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-3"><h6 class="mb-0 fw-bold"><?= $lang['password'] ?></h6></div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3"><label class="small"><?= $lang['old_pass'] ?></label><input type="password" name="old_password" class="form-control" required></div>
                    <div class="mb-3"><label class="small"><?= $lang['new_pass'] ?></label><input type="password" name="new_password" class="form-control" required></div>
                    <div class="mb-3"><label class="small"><?= $lang['confirm_pass'] ?></label><input type="password" name="confirm_password" class="form-control" required></div>
                    <button type="submit" name="change_password" class="btn btn-outline-dark w-100 fw-bold"><?= $lang['update_pass_btn'] ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>