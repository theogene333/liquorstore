<?php
require_once 'config/db.php';

$error = "";
$success = "";

// --- 1. HANDLE REGISTRATION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']); 
    $password = $_POST['password'];
    $role_id  = $_POST['role_id']; 

    if (!empty($fullname) && !empty($username) && !empty($email) && !empty($phone) && !empty($password)) {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            
            if ($check->rowCount() > 0) {
                $error = "User already exists!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, phone, password, role_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                
                if ($stmt->execute([$fullname, $username, $email, $phone, $hashed_password, $role_id])) {
                    $success = "Your Account Has been successfull Created";
                    header("Refresh: 2; url=login.php?msg=success"); 
                } else {
                    $error = "Registration failed.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database Error.";
        }
    } else {
        $error = "All fields required!";
    }
}

$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Register Staff | Liquor RW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-navy: #111827;
            --accent-red: #e94560;
            --bg-soft: #f9fafb;
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
            padding: 15px; /* Minimal padding for small screens */
        }

        .registration-card {
            background: #ffffff;
            padding: 30px 25px;
            border-radius: 20px;
            width: 100%;
            max-width: 420px; /* Kept narrow for a "small" look */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        /* Top Slim Accent */
        .registration-card::after {
            content: "";
            position: absolute;
            top: 0; left: 30%; right: 30%;
            height: 3px;
            background: var(--accent-red);
            border-radius: 0 0 10px 10px;
        }

        .header-section h5 { 
            font-weight: 700; 
            color: var(--primary-navy);
            font-size: 1.1rem;
        }

        .form-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }

        /* Compact Inputs */
        .input-group {
            background: var(--bg-soft);
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 12px;
            transition: 0.2s;
        }

        .input-group:focus-within {
            border-color: var(--accent-red);
            background: #fff;
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #9ca3af;
            padding-left: 12px;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            background: transparent;
            border: none;
            padding: 10px;
            font-size: 0.85rem;
            color: var(--primary-navy);
            height: 42px;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: none;
            background: transparent;
        }

        .btn-primary {
            background: var(--accent-red);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: 0.3s;
            margin-top: 5px;
        }

        .btn-primary:hover {
            background: #d63d58;
            transform: translateY(-1px);
        }

        .alert { 
            border-radius: 10px; 
            font-size: 0.75rem;
            padding: 8px;
        }

        .moon-footer { 
            margin-top: 20px; 
            color: rgba(255,255,255,0.4); 
            font-size: 0.65rem; 
            text-align: center;
        }
    </style>
</head>
<body>

<div class="registration-card shadow-lg">
    <div class="header-section text-center mb-3">
        <div class="d-inline-block p-2 rounded-circle mb-2" style="background: rgba(233, 69, 96, 0.1);">
            <i class="fas fa-user-plus text-danger"></i>
        </div>
        <h5>System Portal</h5>
        <p class="text-muted small" style="font-size: 0.7rem;">Create New Staff Account</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger text-center mb-2"><?= $error ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success text-center mb-2"><?= $success ?></div>
    <?php endif; ?>

    <form action="registration_form.php" method="POST">
        
        <label class="form-label">Full Name</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
            <input type="text" name="fullname" class="form-control shadow-none" placeholder="Jean Pierre" required>
        </div>

        <label class="form-label">Username</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-at"></i></span>
            <input type="text" name="username" class="form-control shadow-none" placeholder="jp_250" required>
        </div>

        <label class="form-label">Email & Phone</label>
        <div class="row g-2">
            <div class="col-7">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control shadow-none" placeholder="Email" required>
                </div>
            </div>
            <div class="col-5">
                <div class="input-group">
                    <input type="text" name="phone" class="form-control shadow-none" placeholder="Phone" required>
                </div>
            </div>
        </div>

        <label class="form-label">Security & Role</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" name="password" class="form-control shadow-none" placeholder="Password" required>
        </div>

        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
            <select name="role_id" class="form-select shadow-none" required>
                <option value="">-- Role --</option>
                <?php foreach($roles as $role): ?>
                    <option value="<?= $role['id'] ?>"><?= $role['role_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-grid mt-3">
            <button type="submit" class="btn btn-primary shadow-sm">REGISTER ACCOUNT</button>
        </div>
        
        <div class="text-center mt-3">
            <a href="login.php" class="text-muted small text-decoration-none fw-bold" style="font-size: 0.7rem;">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </form>
</div>

<div class="moon-footer">
    &copy; <?= date('Y') ?> <strong>Moon Design Corp</strong><br>
    (+250) 788277436
</div>

</body>
</html>