<?php
require_once 'config/db.php';
// lang_config.php included in header.php
include 'includes/header.php';
include 'includes/sidebar.php';

// --- 1. Handle Delete Product ---
if (isset($_GET['delete']) && ($_SESSION['role'] == 'Super Admin' || $_SESSION['role'] == 'Manager')) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$id])) {
        // Multi-language: product_deleted_msg
        echo "<script>alert('".__('product_deleted_msg')."'); window.location='products.php';</script>";
    }
}

// --- 2. Handle Add / Update Product ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $id = $_POST['product_id'];
    $name = $_POST['product_name'];
    $cat_id = $_POST['category_id'];
    $sku = $_POST['sku'];
    $p_price = $_POST['purchase_price'];
    $s_price = $_POST['selling_price'];
    $stock = $_POST['stock_qty'];
    $expiry = $_POST['expiry_date'];

    if (!empty($id)) {
        $sql = "UPDATE products SET product_name=?, category_id=?, sku=?, purchase_price=?, selling_price=?, stock_qty=?, expiry_date=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $cat_id, $sku, $p_price, $s_price, $stock, $expiry, $id]);
    } else {
        $sql = "INSERT INTO products (product_name, category_id, sku, purchase_price, selling_price, stock_qty, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $cat_id, $sku, $p_price, $s_price, $stock, $expiry]);
    }
    header("Location: products.php?msg=success");
}

// --- 3. Fetch Data ---
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$products = $pdo->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.product_name ASC")->fetchAll();

// --- 4. DATA FOR STOCK SUMMARY ---
$total_items_volume = $pdo->query("SELECT SUM(stock_qty) FROM products")->fetchColumn() ?: 0;
$total_stock_value = $pdo->query("SELECT SUM(stock_qty * selling_price) FROM products")->fetchColumn() ?: 0;
?>

<div class="container-fluid py-2">
    <!-- PAGE HEADER SECTION -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h3 mb-0 fw-800 text-dark">
                <i class="fas fa-boxes text-primary me-2"></i> <?= __("inventory_items") ?>
            </h2>
            <p class="text-muted small mb-0"><?= __("inventory_subtitle") ?></p>
        </div>
        
        <button class="btn btn-add-premium shadow-sm px-4 fw-bold border-0 py-2 rounded-pill" 
                data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i> <?= __("add_item") ?>
        </button>
    </div>

    <!-- STOCK HIGHLIGHTS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-dark text-white border-start border-primary border-4 rounded-4">
                <small class="fw-bold text-uppercase opacity-75" style="font-size: 0.65rem;"><?= __("total_stock_volume") ?></small>
                <h4 class="fw-800 mb-0 mt-1" style="color: #ffc107;">
                    <?= number_format($total_items_volume) ?> <small class="fs-6 fw-bold"><?= __("items") ?></small>
                </h4>
                <div class="mt-1 small fw-bold" style="color: #0dcaf0; font-size: 0.7rem;"><?= __("across_all_categories") ?></div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white border-start border-success border-4 rounded-4">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;"><?= __("total_stock_value") ?></small>
                <h4 class="fw-800 mb-0 mt-1 text-success"><?= number_format($total_stock_value) ?> <small class="fs-6 text-muted"><?= __("currency") ?></small></h4>
                <div class="mt-1 small text-muted"><?= __("estimated_value") ?></div>
            </div>
        </div>

        <?php
        $low_stock_count = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty <= 10")->fetchColumn();
        $expiring_count = $pdo->query("SELECT COUNT(*) FROM products WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date > '0000-00-00'")->fetchColumn();
        ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white border-start border-danger border-4 rounded-4">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;"><?= __("stock_low") ?></small>
                <h4 class="fw-800 mb-0 mt-1 text-danger"><?= $low_stock_count ?> <small class="fs-6 text-muted"><?= __("items") ?></small></h4>
                <div class="mt-1 small text-muted"><?= __("needs_restock") ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white border-start border-warning border-4 rounded-4">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;"><?= __("expiring_soon") ?></small>
                <h4 class="fw-800 mb-0 mt-1 text-warning"><?= $expiring_count ?> <small class="fs-6 text-muted"><?= __("items") ?></small></h4>
                <div class="mt-1 small text-muted"><?= __("next_30_days") ?></div>
            </div>
        </div>
    </div>

    <!-- Inventory Table with Enhanced Search UI -->
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-800 text-dark"><?= __("current_stock_list") ?></h6>
            
            <div class="input-group input-group-sm w-50 w-md-25 border rounded-3 overflow-hidden shadow-none">
                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="productSearch" class="form-control border-0 bg-white shadow-none" placeholder="<?= __("search") ?>...">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="productTable">
                <thead class="table-light">
                    <tr style="font-size: 0.7rem;" class="text-uppercase text-muted fw-bold">
                        <th class="ps-4"><?= __("sku") ?></th>
                        <th><?= __("product_name") ?></th>
                        <th><?= __("category") ?></th>
                        <th><?= __("price") ?></th>
                        <th><?= __("stock") ?></th>
                        <th><?= __("expiry_date") ?></th>
                        <th class="text-center no-print"><?= __("actions") ?></th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if(empty($products)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted"><?= __("no_matching_items") ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p): ?>
                    <tr class="product-row">
                        <td class="ps-4 small fw-bold text-muted">#<?= $p['sku'] ?></td>
                        <td><div class="fw-bold text-dark product-search-field"><?= htmlspecialchars($p['product_name']) ?></div></td>
                        <td><span class="badge bg-light text-dark border fw-normal"><?= $p['category_name'] ?></span></td>
                        <td>
                            <div class="small text-muted"><?= __("buy") ?>: <?= number_format($p['purchase_price']) ?></div>
                            <div class="fw-bold text-primary"><?= __("sell") ?>: <?= number_format($p['selling_price']) ?></div>
                        </td>
                        <td>
                            <?php if($p['stock_qty'] <= 5): ?>
                                <span class="badge bg-danger px-2"><?= __("critical") ?></span>
                            <?php elseif($p['stock_qty'] <= 15): ?>
                                <span class="badge bg-warning text-dark px-2"><?= __("low") ?></span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success px-3"><?= $p['stock_qty'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= ($p['expiry_date'] == "0000-00-00") ? 'N/A' : date('d M Y', strtotime($p['expiry_date'])) ?></td>
                        <td class="text-center no-print">
                            <button class="btn btn-sm btn-light text-primary border-0 rounded-3 shadow-none" onclick='editProduct(<?= json_encode($p) ?>)'><i class="fas fa-edit"></i></button>
                            <?php if ($_SESSION['role'] == 'Super Admin'): ?>
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-light text-danger border-0 rounded-3" onclick="return confirm('<?= __("confirm_delete_product") ?>')"><i class="fas fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                                <p class="fw-bold"><?= __("no_matching_items") ?></p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold ms-2" id="mTitle"><?= __("add_item") ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="product_id" id="product_id">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-bold text-muted"><?= __("sku") ?></label><input type="text" name="sku" id="sku" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-bold text-muted"><?= __("product_name") ?></label><input type="text" name="product_name" id="product_name" class="form-control" required></div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted"><?= __("category") ?></label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value=""><?= __("select_category") ?></option>
                            <?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= $c['category_name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label small fw-bold text-muted"><?= __("expiry_date") ?></label><input type="date" name="expiry_date" id="expiry_date" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold text-muted"><?= __("buy_price") ?></label><input type="number" name="purchase_price" id="purchase_price" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-bold text-primary"><?= __("sell_price") ?></label><input type="number" name="selling_price" id="selling_price" class="form-control border-primary" required></div>
                    <div class="col-md-4"><label class="form-label small fw-bold text-danger"><?= __("opening_stock") ?></label><input type="number" name="stock_qty" id="stock_qty" class="form-control border-danger" required></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light text-end">
                <button type="submit" name="save_product" class="btn btn-primary px-5 fw-bold rounded-pill"><?= __("save_product") ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('productSearch').addEventListener('keyup', function() {
    const val = this.value.toLowerCase();
    const rows = document.querySelectorAll('.product-row');
    const noResults = document.getElementById('noResultsRow');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        if (text.includes(val)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    noResults.style.display = (visibleCount === 0) ? '' : 'none';
});

function resetForm() {
    document.getElementById('product_id').value = '';
    document.getElementById('mTitle').innerText = '<?= __("add_item") ?>';
}

function editProduct(data) {
    document.getElementById('product_id').value = data.id;
    document.getElementById('sku').value = data.sku;
    document.getElementById('product_name').value = data.product_name;
    document.getElementById('category_id').value = data.category_id;
    document.getElementById('purchase_price').value = data.purchase_price;
    document.getElementById('selling_price').value = data.selling_price;
    document.getElementById('stock_qty').value = data.stock_qty;
    document.getElementById('expiry_date').value = data.expiry_date;
    document.getElementById('mTitle').innerText = '<?= __("edit_product") ?>';
    new bootstrap.Modal(document.getElementById('productModal')).show();
}
</script>

<style>
    .fw-800 { font-weight: 800; }
    .btn-add-premium { background-color: #e94560; color: white; transition: 0.3s; }
    .btn-add-premium:hover { background-color: #d63d58; transform: translateY(-2px); color: white; }
    .bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; }
</style>

<?php include 'includes/footer.php'; ?>