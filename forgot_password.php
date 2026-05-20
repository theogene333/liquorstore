<?php
require_once 'config/db.php';

// --- CONFIGURATION ---
$admin_username = 'admin';       // The username you want to reset
$new_password   = 'admin123';    // The NEW password you want to set
// ---------------------

try {
    // 1. Generate a secure hash
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // 2. Update the database
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashed_password, $admin_username]);

    if ($stmt->rowCount() > 0) {
        echo "<div style='color:green; font-family:sans-serif;'>
                <h2>Success!</h2>
                <p>Password for <b>$admin_username</b> has been updated to: <b>$new_password</b></p>
                <p style='color:red;'><b>IMPORTANT: Delete this file (reset_password.php) from your server immediately!</b></p>
              </div>";
    } else {
        echo "<h2 style='color:orange;'>Notice:</h2>
              <p>Username '$admin_username' not found, or the password was already set to that value.</p>";
    }
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>Error:</h2> " . $e->getMessage();
}
?>