<?php
require_once 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// SECURITY: Only allow Super Admin to access this page
if ($_SESSION['role'] !== 'Super Admin') {
    echo "<div class='alert alert-danger m-5'>".$lang['staff_access_denied']."</div>";
    include 'includes/footer.php';
    exit();
}

$message = "";

// --- LOGIC: CREATE OR UPDATE USER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $role_id  = $_POST['role_id'];
    $user_id  = $_POST['user_id']; 

    try {
        if (!empty($user_id)) {
            // UPDATE EXISTING USER
            if (!empty($_POST['password'])) {
                $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=?, role_id=?, password=? WHERE id=?");
                $stmt->execute([$fullname, $username, $email, $phone, $role_id, $pass, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=?, role_id=? WHERE id=?");
                $stmt->execute([$fullname, $username, $email, $phone, $role_id, $user_id]);
            }
            $message = "<div class='alert alert-success'>".$lang['staff_update_success']."</div>";
        } else {
            // CREATE NEW USER
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, phone, password, role_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$fullname, $username, $email, $phone, $pass, $role_id]);
            $message = "<div class='alert alert-success'>".$lang['staff_create_success']."</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>".$lang['staff_error_exists']."</div>";
    }
}

// --- LOGIC: DELETE USER ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) { 
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        echo "<script>window.location='users.php?msg=deleted';</script>";
        exit();
    }
}

// Fetch all users and roles for the display
$users = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-users-cog text-primary"></i> <?= $lang['staff_mgmt_title'] ?></h2>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="prepareAddForm()">
            <i class="fas fa-user-plus me-2"></i><?= $lang['add_new_staff'] ?>
        </button>
    </div>

    <?= $message ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?= $lang['fullname'] ?></th>
                            <th><?= $lang['username_label'] ?></th>
                            <th><?= $lang['email_address'] ?></th>
                            <th><?= $lang['phone_number'] ?></th>
                            <th><?= $lang['system_role'] ?></th>
                            <th class="text-center"><?= $lang['actions'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($u['fullname']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="small"><?= htmlspecialchars($u['phone']) ?></td>
                            <td>
                                <span class="badge bg-dark px-3"><?= $u['role_name'] ?></span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-2" onclick='prepareEditForm(<?= json_encode($u) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= $lang['confirm_delete_staff'] ?>')">
                                    <i class="fas fa-trash"></i>
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
</div>

<!-- Modal for Add/Edit -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content shadow border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="mTitle"><?= $lang['staff_details'] ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="user_id" id="user_id">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold"><?= $lang['fullname'] ?></label>
                        <input type="text" name="fullname" id="fullname" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold"><?= $lang['username_label'] ?></label>
                        <input type="text" name="username" id="username" class="form-control shadow-none" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold"><?= $lang['email_address'] ?></label>
                        <input type="email" name="email" id="email" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold"><?= $lang['phone_number'] ?></label>
                        <input type="text" name="phone" id="phone" class="form-control shadow-none" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold"><?= $lang['password'] ?></label>
                        <input type="password" name="password" id="password_input" class="form-control shadow-none">
                        <small class="text-muted" id="passHint"><?= $lang['pass_hint_new'] ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold"><?= $lang['system_role'] ?></label>
                        <select name="role_id" id="role_id" class="form-select shadow-none" required>
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= $r['role_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="submit" name="save_user" class="btn btn-primary px-5 fw-bold"><?= $lang['save_staff_account'] ?></button>
            </div>
        </form>
    </div>
</div>

<script>
// Load multi-language strings for JavaScript
const jsLang = {
    addTitle: "<?= $lang['modal_title_staff_add'] ?>",
    editTitle: "<?= $lang['modal_title_staff_edit'] ?>",
    hintNew: "<?= $lang['pass_hint_new'] ?>",
    hintEdit: "<?= $lang['pass_hint_edit'] ?>"
};

function prepareAddForm() {
    document.getElementById('user_id').value = '';
    document.getElementById('fullname').value = '';
    document.getElementById('username').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('password_input').required = true;
    document.getElementById('passHint').innerText = jsLang.hintNew;
    document.getElementById('mTitle').innerText = jsLang.addTitle;
}

function prepareEditForm(data) {
    document.getElementById('user_id').value = data.id;
    document.getElementById('fullname').value = data.fullname;
    document.getElementById('username').value = data.username;
    document.getElementById('email').value = data.email;
    document.getElementById('phone').value = data.phone;
    document.getElementById('role_id').value = data.role_id;
    document.getElementById('password_input').required = false;
    document.getElementById('passHint').innerText = jsLang.hintEdit;
    document.getElementById('mTitle').innerText = jsLang.editTitle;
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>