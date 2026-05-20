<?php
require_once 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$user_id = $_SESSION['user_id'];
$message = "";

// --- 1. HANDLE PROFILE INFORMATION UPDATE ---
if (isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);

    if (!empty($fullname)) {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$fullname, $email, $user_id])) {
            // Update session variable so header name changes immediately
            $_SESSION['fullname'] = $fullname;
            $message = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle me-2'></i>" . __('staff_update_success') . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>" . __('error_occurred') . "</div>";
    }
}

// --- 2. HANDLE PASSWORD CHANGE ---
if (isset($_POST['change_password'])) {
    $current_p = $_POST['current_password'];
    $new_p = $_POST['new_password'];
    $confirm_p = $_POST['confirm_password'];

    // Fetch current hashed password from DB
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (password_verify($current_p, $user['password'])) {
        if ($new_p === $confirm_p) {
            if (strlen($new_p) >= 6) {
                $new_hashed = password_hash($new_p, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$new_hashed, $user_id]);
                $message = "<div class='alert alert-success'>" . __('success') . ": " . __('recorded_successfully') . "</div>";
            } else {
                $message = "<div class='alert alert-danger'>Password too short (Min 6).</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>" . __('error_occurred') . ": Passwords mismatch</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>" . __('error_occurred') . ": Incorrect Old Password</div>";
    }
}

// Fetch current user details to populate fields
$stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark"><i class="fas fa-user-circle text-primary me-2"></i> <?= __('profile') ?></h2>
            <p class="text-muted"><?= __('staff_details') ?></p>
        </div>
    </div>

    <?= $message ?>

    <div class="row g-4">
        <!-- Left Column: Profile Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm text-center p-4">
                <div class="mb-3">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentUser['fullname']) ?>&background=e94560&color=fff&size=128" 
                         class="rounded-circle shadow" alt="Avatar">
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($currentUser['fullname']) ?></h4>
                <p class="text-muted mb-3">@<?= htmlspecialchars($currentUser['username']) ?></p>
                <div class="mb-3">
                    <span class="badge bg-primary px-3 py-2 text-uppercase"><?= __($currentUser['role_name']) ?></span>
                </div>
                <hr>
                <div class="text-start small">
                    <p class="mb-1"><strong><?= __('system_status') ?>:</strong> <span class="text-success">Active</span></p>
                    <p class="mb-0"><strong>Member Since:</strong> <?= date('d M Y', strtotime($currentUser['id'] == 1 ? '2023-01-01' : 'now')) ?></p>
                </div>
            </div>
        </div>

        <!-- Right Column: Forms -->
        <div class="col-lg-8">
            <!-- General Info Form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-id-card me-2 text-primary"></i> <?= __('staff_details') ?></h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold"><?= __('fullname') ?></label>
                                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($currentUser['fullname']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold"><?= __('email_address') ?></label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($currentUser['email']) ?>" placeholder="yourname@example.com">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold"><?= __('username_label') ?></label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($currentUser['username']) ?>" readonly>
                                <small class="text-muted italic">Username cannot be changed.</small>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" name="update_profile" class="btn btn-primary px-4"><?= __('save_information') ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Change Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-key me-2 text-danger"></i> <?= __('update_pass_btn') ?></h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold"><?= __('old_pass') ?></label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold"><?= __('new_pass') ?></label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold"><?= __('confirm_pass') ?></label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" name="change_password" class="btn btn-dark px-4"><?= __('update_pass_btn') ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>