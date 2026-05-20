<?php
/**
 * ajax_pos_data.php
 * Dynamic Data Engine for the POS Interface
 * Provides Products, VAT Settings, and Customer Debt Balances
 */

require_once 'config/db.php';

// Ensure the session is active to verify authorized access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SET JSON HEADER
header('Content-Type: application/json');

// 2. SECURITY: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Please login."]);
    exit;
}

/**
 * 3. GET SYSTEM VAT CONFIGURATION (ADMIN CONTROLLED)
 * This rate is set in settings.php and stored in the database.
 */
try {
    $settingsStmt = $pdo->query("SELECT vat_rate FROM settings WHERE id = 1 LIMIT 1");
    $settings = $settingsStmt->fetch();
    $current_vat = $settings ? (float)$settings['vat_rate'] : 0.00;
} catch (Exception $e) {
    $current_vat = 0.00; 
}

/**
 * 4. FETCH CUSTOMERS (FOR DEBT / ON ACCOUNT SALES)
 * We fetch 'id', 'fullname', and 'total_debt' so the POS can display current balances.
 */
try {
    $custStmt = $pdo->query("SELECT id, fullname, total_debt FROM customers ORDER BY fullname ASC");
    $customers = $custStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $customers = [];
}

/**
 * 5. HANDLE SEARCH & FILTER PARAMETERS FOR PRODUCTS
 */
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;

// Base Query: Only show products that have items in stock
$sql = "SELECT p.id, p.sku, p.product_name, p.selling_price, p.stock_qty, c.category_name, p.category_id 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.stock_qty > 0";

$params = [];

// Apply Search (Matches Name or Barcode/SKU)
if (!empty($q)) {
    $sql .= " AND (p.product_name LIKE :q OR p.sku LIKE :q)";
    $params['q'] = "%$q%";
}

// Apply Category Filter
if ($cat_id > 0) {
    $sql .= " AND p.category_id = :cat_id";
    $params['cat_id'] = $cat_id;
}

// Sort alphabetically and limit results for speed
$sql .= " ORDER BY p.product_name ASC LIMIT 50";

try {
    // 6. EXECUTE PRODUCT QUERY
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * 7. OUTPUT CONSOLIDATED JSON
     * This single response gives the POS all the dynamic data it needs.
     */
    echo json_encode([
        "status" => "success",
        "vat_rate" => $current_vat,
        "products" => $products,
        "customers" => $customers // Allows the POS to populate the Debtor dropdown
    ]);

} catch (PDOException $e) {
    // Return structured error if the database query fails
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Inventory Query Error: " . $e->getMessage()
    ]);
}