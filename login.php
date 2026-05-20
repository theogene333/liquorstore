<?php
session_start();
require_once 'config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

// --- 1. DATABASE INITIALIZATION & STATE CHECK ---
try {
    $roleCheck = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    if ($roleCheck == 0) {
        $pdo->exec("INSERT INTO roles (id, role_name) VALUES 
            (1, 'Super Admin'), 
            (2, 'Manager'), 
            (3, 'Cashier'), 
            (4, 'Waiter')");
    }

    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $isFirstRun = ($userCount == 0);
} catch (PDOException $e) {
    $error = "System Database Error";
    $isFirstRun = false;
}

$view = isset($_GET['view']) ? $_GET['view'] : 'login';
if ($isFirstRun) { $view = 'register'; }

// --- 2. HANDLE FIRST-TIME REGISTRATION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_admin_btn'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($fullname) && !empty($username) && !empty($password) && !empty($email)) {
        try {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password, role_id, status) VALUES (?, ?, ?, ?, 1, 'active')");
            $stmt->execute([$fullname, $username, $email, $hashed_pass]);
            
            $success = "Admin account created! Please Login.";
            $isFirstRun = false;
            $view = 'login';
        } catch (PDOException $e) {
            $error = "Registration failed.";
        }
    }
}

// --- 3. HANDLE SECURE LOGIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_btn'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u 
                               JOIN roles r ON u.role_id = r.id 
                               WHERE u.username = :username AND u.status = 'active' LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role_name'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Incorrect username or password.";
        }
    } catch (PDOException $e) {
        $error = "Login service unavailable.";
    }
}

// --- 4. HANDLE RECOVERY ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recovery_btn'])) {
    $v_name  = trim($_POST['verify_fullname']);
    $v_email = trim($_POST['verify_email']);
    $new_p   = $_POST['new_password'];

    try {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE fullname = ? AND email = ? LIMIT 1");
        $stmt->execute([$v_name, $v_email]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed = password_hash($new_p, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hashed, $user['id']]);
            $success = "Recovery Successful! Please Login.";
            $view = 'login';
        } else {
            $error = "Verification Failed. Try again.";
        }
    } catch (PDOException $e) {
        $error = "Recovery process error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Access System | Liquor Store RW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-navy: #111827;
            --accent-red: #e94560;
            --soft-grey: #f9fafb;
        }

        body { 
            background: var(--primary-navy);
            background: radial-gradient(circle at top right, #1f2937, #111827);
            min-height: 100vh; 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            justify-content: center; 
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
        }

        /* Mobile Responsive Card */
        .login-card { 
            background: #ffffff; 
            padding: 40px 30px; 
            border-radius: 24px; 
            width: 100%; 
            max-width: 420px; 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        /* Top Accent Line */
        .login-card::after {
            content: "";
            position: absolute;
            top: 0; left: 20%; right: 20%;
            height: 4px;
            background: var(--accent-red);
            border-radius: 0 0 10px 10px;
        }

        .welcome-header h5 { font-weight: 700; color: var(--primary-navy); font-size: 1.15rem; }

        .form-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 6px; }

        /* Modern Inputs */
        .input-group {
            background: var(--soft-grey);
            border: 1.5px solid #eee;
            border-radius: 14px;
            transition: 0.2s;
            margin-bottom: 15px;
        }
        .input-group:focus-within {
            border-color: var(--accent-red);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(233, 69, 96, 0.1);
        }
        .input-group-text { background: transparent; border: none; color: #9ca3af; padding-left: 15px; }
        .form-control { background: transparent; border: none; padding: 12px; font-size: 0.95rem; height: 50px; }
        .form-control:focus { box-shadow: none; }

        /* Action Buttons */
        .btn-primary { 
            background: var(--accent-red); 
            border: none; 
            height: 55px;
            font-weight: 600; 
            border-radius: 14px;
            transition: 0.3s;
        }
        .btn-primary:hover { background: #d63d58; transform: translateY(-1px); }

        .btn-outline-staff {
            background: #fff;
            color: var(--primary-navy);
            border: 1.5px solid #eee;
            border-radius: 14px;
            padding: 12px;
            font-weight: 600;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: center;
        }

        .recovery-link { font-size: 0.8rem; color: var(--accent-red); text-decoration: none; font-weight: 600; }

        .moon-footer { 
            margin-top: 25px; 
            color: rgba(255,255,255,0.4); 
            font-size: 0.7rem; 
            text-align: center;
            line-height: 1.5;
        }

        /* Small device optimization */
        @media (max-width: 400px) {
            .login-card { padding: 30px 20px; }
            .welcome-header h5 { font-size: 1rem; }
            body { padding: 15px; }
        }
        
        .eye-toggle { cursor: pointer; padding-right: 15px !important; }
    </style>
</head>
<body>

<div class="login-card shadow-lg">
    <div class="welcome-header text-center mb-4">
        <div class="mb-3 d-inline-block p-3 rounded-circle" style="background: rgba(233, 69, 96, 0.1);">
            <i class="fas fa-lock-open fa-lg text-danger"></i>
        </div>
        <h5 class="mb-1">Welcome in the System Portal</h5>
        <p class="text-muted small mb-0">Liquor Management RW</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 small text-center border-0 mb-3" style="border-radius:10px;"><?= $error ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success py-2 small text-center border-0 mb-3" style="border-radius:10px;"><?= $success ?></div>
    <?php endif; ?>

    <!-- VIEW: REGISTER -->
    <?php if($view == 'register'): ?>
        <form method="POST">
            <div class="mb-2"><label class="form-label">Full Name</label>
                <div class="input-group"><span class="input-group-text"><i class="fas fa-user"></i></span><input type="text" name="fullname" class="form-control" required></div>
            </div>
            <div class="mb-2"><label class="form-label">Username</label>
                <div class="input-group"><span class="input-group-text"><i class="fas fa-at"></i></span><input type="text" name="username" class="form-control" required></div>
            </div>
            <div class="mb-2"><label class="form-label">Email</label>
                <div class="input-group"><span class="input-group-text"><i class="fas fa-envelope"></i></span><input type="email" name="email" class="form-control" required></div>
            </div>
            <div class="mb-4"><label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" name="password" id="pass_reg" class="form-control" required>
                    <span class="input-group-text eye-toggle" onclick="togglePassword('pass_reg')"><i class="fas fa-eye" id="icon-pass_reg"></i></span>
                </div>
            </div>
            <button type="submit" name="register_admin_btn" class="btn btn-primary w-100 shadow">INITIALIZE ADMIN</button>
        </form>

    <!-- VIEW: LOGIN -->
    <?php elseif($view == 'login'): ?>
        <form method="POST">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                <input type="text" name="username" class="form-control shadow-none" placeholder="Enter username" required>
            </div>
            
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                <input type="password" name="password" id="pass_log" class="form-control shadow-none" placeholder="••••••••" required>
                <span class="input-group-text eye-toggle" onclick="togglePassword('pass_log')"><i class="fas fa-eye text-muted" id="icon-pass_log"></i></span>
            </div>
            
            <button type="submit" name="login_btn" class="btn btn-primary w-100 shadow mb-3">SECURE SIGN IN</button>
            
            <div class="text-center">
                <a href="login.php?view=reset" class="recovery-link">Lost access? Recover Account</a>
                <div class="my-3 small text-muted">Authorized Personnel Only</div>
                <a href="registration_form.php" class="btn btn-outline-staff">
                    <i class="fas fa-user-plus me-2"></i>Staff Registration
                </a>
            </div>
        </form>

    <!-- VIEW: RECOVERY -->
    <?php else: ?>
        <form method="POST">
            <p class="small text-muted text-center mb-4">Provide identity to reset.</p>
            <label class="form-label">Full Name</label>
            <div class="input-group"><span class="input-group-text"><i class="fas fa-id-card"></i></span><input type="text" name="verify_fullname" class="form-control" required></div>
            
            <label class="form-label">Email</label>
            <div class="input-group"><span class="input-group-text"><i class="fas fa-envelope"></i></span><input type="email" name="verify_email" class="form-control" required></div>
            
            <label class="form-label text-danger">New Password</label>
            <div class="input-group border-danger">
                <span class="input-group-text text-danger"><i class="fas fa-lock"></i></span>
                <input type="password" name="new_password" id="pass_rec" class="form-control shadow-none" required minlength="6">
                <span class="input-group-text eye-toggle" onclick="togglePassword('pass_rec')"><i class="fas fa-eye text-danger" id="icon-pass_rec"></i></span>
            </div>
            <button type="submit" name="recovery_btn" class="btn btn-dark w-100 mb-3 fw-bold">UPDATE ACCESS</button>
            <div class="text-center">
                <a href="login.php?view=login" class="text-muted small fw-bold text-decoration-none">Back to Login</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="moon-footer">
    &copy; <?= date('Y') ?> <strong>Moon Design Corp</strong><br>
    Kigali, Rwanda | Contacts: (+250) 788277436
</div>

<script>
    function togglePassword(fieldId) {
        const passwordField = document.getElementById(fieldId);
        const icon = document.getElementById('icon-' + fieldId);
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordField.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>

</body>
</html>