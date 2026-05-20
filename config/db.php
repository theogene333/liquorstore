<?php
/**
 * LIQUOR STORE MANAGEMENT SYSTEM - CONFIGURATION
 * Location: config/db.php
 */

// 1. DATABASE CREDENTIALS
define('DB_HOST', 'sql210.infinityfree.com');
define('DB_NAME', 'if0_41890144_liquor_store_db'); // Update this with your actual database name
define('DB_USER', 'if0_41890144');
define('DB_PASS', '1sk9UDXib6tSPID'); // Update this with your actual password
define('DB_CHARSET', 'utf8mb4');

// 2. SYSTEM SETTINGS (Rwanda Context)
define('APP_NAME', 'Liquor Store RW');
define('CURRENCY', 'RWF');
define('VAT_RATE', 0.18); // 18% standard VAT in Rwanda
date_default_timezone_set('Africa/Kigali'); // Set timezone to Rwanda

// 3. SECURE SESSION START
// Prevents script errors if session is already started elsewhere
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4. PDO CONNECTION LOGIC
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // In production, you should log this to a file instead of echoing
    die("Database Connection Failed: " . $e->getMessage());
}

// 5. GLOBAL HELPER FUNCTIONS
// These functions will be available in any file that includes db.php

/**
 * Format numbers into Rwandan Francs
 * Usage: echo formatRWF(5000); -> 5,000 RWF
 */
function formatRWF($amount) {
    return number_format($amount, 0, '.', ',') . " " . CURRENCY;
}

/**
 * Sanitize User Input (XSS Protection)
 */
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect Helper
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Check User Role (Authorization)
 * Usage: checkRole('Super Admin');
 */
function checkRole($requiredRole) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        die("<div style='color:red; padding:20px; font-family:sans-serif;'>
                <h2>Access Denied</h2>
                <p>You do not have permission to view this page. Required: $requiredRole</p>
                <a href='index.php'>Back to Dashboard</a>
             </div>");
    }
}
?>