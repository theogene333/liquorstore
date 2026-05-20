<?php require_once 'config/db.php'; ?>
<?php
$id = $_GET['id'];
$items = $pdo->prepare("SELECT si.*, p.product_name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$items->execute([$id]);

echo '<table class="table table-sm"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>';
foreach($items->fetchAll() as $item) {
    echo "<tr><td>{$item['product_name']}</td><td>{$item['quantity']}</td><td>".number_format($item['unit_price'])."</td><td>".number_format($item['quantity']*$item['unit_price'])."</td></tr>";
}
echo '</tbody></table>'; ?>