<?php
require_once 'config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message_key' => 'err_session']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['items'])) {
    echo json_encode(['success' => false, 'message_key' => 'err_empty_cart']);
    exit;
}

try {
    $pdo->beginTransaction();

    $vat_stmt = $pdo->query("SELECT vat_rate FROM settings WHERE id = 1");
    $vat_rate = $vat_stmt->fetchColumn() ?: 0;

    $invoice_no = "INV-" . date('ymd') . "-" . strtoupper(substr(uniqid(), -4));
    $payment_method = trim($data['payment_method']);
    $customer_id = (!empty($data['customer_id'])) ? (int)$data['customer_id'] : null;

    $subtotal_sum = 0;
    $items_to_save = [];

    foreach ($data['items'] as $item) {
        $stmt = $pdo->prepare("SELECT selling_price, stock_qty, product_name FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$item['id']]);
        $prod = $stmt->fetch();

        if (!$prod) throw new Exception("err_prod_gone");
        if ($prod['stock_qty'] < $item['qty']) throw new Exception("err_no_stock");

        $item_price = (float)$prod['selling_price'];
        $subtotal_sum += ($item_price * (int)$item['qty']);
        $items_to_save[] = ['id' => $item['id'], 'qty' => $item['qty'], 'unit_price' => $item_price];
    }

    $tax_amount = $subtotal_sum * ($vat_rate / 100);
    $final_total = $subtotal_sum + $tax_amount;

    if (strcasecmp($payment_method, 'Debt') == 0) {
        if (is_null($customer_id)) throw new Exception("err_debtor_required");
        $upd_cust = $pdo->prepare("UPDATE customers SET total_debt = total_debt + ? WHERE id = ?");
        $upd_cust->execute([$final_total, $customer_id]);
        if ($upd_cust->rowCount() === 0) throw new Exception("err_cust_not_found");
    }

    $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, total_amount, payment_method, cashier_id, customer_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$invoice_no, $final_total, $payment_method, $_SESSION['user_id'], $customer_id]);
    $sale_id = $pdo->lastInsertId();

    $insItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    $updStock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");

    foreach ($items_to_save as $si) {
        $insItem->execute([$sale_id, $si['id'], $si['qty'], $si['unit_price']]);
        $updStock->execute([$si['qty'], $si['id']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message_key' => 'success_msg']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message_key' => $e->getMessage()]);
}