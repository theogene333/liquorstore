<?php
session_start();

// Check if a database connection is needed to log the logout activity
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    try {
        // Optional: Log the logout event into the activity_logs table
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'User Logged Out')");
        $log_stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // Silently fail if logging fails to ensure logout still happens
    }
}

// 1. Unset all session variables
$_SESSION = array();

// 2. If it's desired to kill the session, also delete the session cookie.
// This will completely destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session.
session_destroy();

// 4. Redirect to login page with a success message
header("Location: login.php?status=logged_out");
exit();
?>