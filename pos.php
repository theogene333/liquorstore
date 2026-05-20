<?php 
require_once 'config/db.php';
// lang_config.php is included in header.php
include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch categories for filtering
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
?>

<div class="container-fluid pos-screen py-2">
    <div class="row g-3">
        <!-- LEFT SIDE: PRODUCT CATALOG -->
        <div class="col-lg-7 col-xl-8">
            <div class="card border-0 shadow-sm mb-3 rounded-4 adaptive-card">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="input-group input-group-lg search-box border rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="productSearch" class="form-control border-0 shadow-none fs-6" placeholder="<?= __('pos_search_placeholder') ?>">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <select id="categoryFilter" class="form-select form-select-lg border-0 shadow-none fs-6 bg-light rounded-3">
                                <option value=""><?= __('all_categories') ?></option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Product Grid -->
            <div class="product-grid-container" style="height: calc(100vh - 230px); overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
                <div class="row g-2" id="productDisplay">
                    <div class="text-center p-5">
                        <div class="spinner-grow text-danger" role="status"></div>
                        <p class="mt-3 text-muted fw-bold"><?= __('syncing_inventory') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: BILLING CONSOLE -->
        <div class="col-lg-5 col-xl-4">
            <div class="card border-0 shadow-lg h-100 rounded-4 overflow-hidden d-flex flex-column adaptive-card">
                <!-- Order Header -->
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-800 theme-text-main"><i class="fas fa-shopping-cart me-2 text-danger"></i><?= __('active_order') ?></h5>
                    <button class="btn btn-sm btn-light text-danger fw-bold rounded-pill px-3 shadow-none border-0" onclick="clearCart()">
                        <i class="fas fa-undo-alt me-1"></i> <?= __('reset') ?>
                    </button>
                </div>
                
                <!-- Cart Items Table -->
                <div class="card-body p-0 flex-grow-1" style="overflow-y: auto; max-height: 380px;">
                    <table class="table table-hover align-middle mb-0" id="cartTable">
                        <thead class="table-light sticky-top">
                            <tr class="small text-uppercase text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">
                                <th class="ps-3 py-3"><?= __('item') ?></th>
                                <th class="text-center"><?= __('qty') ?></th>
                                <th class="text-end pe-3"><?= __('subtotal') ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <!-- JS will inject cart rows here -->
                        </tbody>
                    </table>
                </div>

                <!-- BILLING SUMMARY (Midnight Theme) -->
                <div class="card-footer p-4 border-0" style="background: #111827;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white-50 small fw-bold"><?= __('subtotal') ?></span>
                        <span class="text-white fw-bold" id="subtotalDisplay">0 <?= __('currency') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white-50 small fw-bold"><?= __('tax_vat') ?> (<span id="vatRateLabel">0</span>%)</span>
                        <span class="text-white fw-bold" id="vatDisplay">0 <?= __('currency') ?></span>
                    </div>
                    
                    <!-- GRAND TOTAL -->
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-secondary">
                        <h4 class="text-white fw-800 mb-0"><?= __('final_total') ?></h4>
                        <div class="text-end">
                            <h3 class="fw-900 mb-0" style="color: #e94560;" id="grandTotalDisplay">0 <?= __('currency') ?></h3>
                            <!-- VISIBLE UNPAID STATUS -->
                            <span id="unpaidStatus" class="badge bg-danger mt-1 animate__animated animate__pulse animate__infinite" style="display:none; font-size: 0.7rem; letter-spacing: 1px;">
                                <i class="fas fa-exclamation-triangle me-1"></i> <?= __('unpaid_badge') ?>
                            </span>
                        </div>
                    </div>

                    <!-- CUSTOMER DEBTOR SELECTION -->
                    <div class="mt-4 mb-3" id="customerSelectContainer" style="display:none;">
                        <label class="form-label text-danger small fw-800 text-uppercase"><?= __('authorized_debtor') ?></label>
                        <select class="form-select border-danger bg-dark text-white shadow-none rounded-3" id="customer_id">
                            <!-- Populated via fetchProducts data -->
                        </select>
                    </div>

                    <!-- PAYMENT METHOD -->
                    <div class="mb-4">
                        <label class="form-label text-white-50 small fw-bold text-uppercase"><?= __('select_payment_method') ?></label>
                        <select class="form-select form-select-lg fw-bold border-0 shadow-none rounded-3" id="payMethod" onchange="toggleCustomerSelect()" style="background-color: #1f2937; color: #fff;">
                            <option value="Cash">💵 <?= __('cash_payment') ?></option>
                            <option value="MoMo">📲 <?= __('momo_payment') ?></option>
                            <option value="Card">💳 <?= __('card_payment') ?></option>
                            <option value="Debt" style="background: #dc3545; color: #fff;">📝 <?= __('debt_payment') ?></option>
                        </select>
                    </div>

                    <button class="btn btn-danger w-100 btn-lg shadow-lg fw-bold py-3 rounded-3" id="btnCheckout" onclick="processSale()">
                        <?= __('process_transaction') ?> <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * PASS TRANSLATIONS TO JAVASCRIPT
 */
const uiLang = {
    currency: "<?= __('currency') ?>",
    owes: "<?= __('debt_balance') ?>",
    stock_limit: "<?= __('stock_limit_reached') ?>",
    clear_confirm: "<?= __('clear_order_confirm') ?>",
    debtor_required: "<?= __('select_debtor_msg') ?>",
    success: "<?= __('sale_success_msg') ?>",
    processing: "<?= __('processing') ?>",
    complete_btn: "<?= __('complete_btn') ?>",
    choose_debtor: "<?= __('choose_debtor') ?>"
};

let cart = [];
let allProducts = [];
let systemVatRate = 0; 

function toggleCustomerSelect() {
    const method = document.getElementById('payMethod').value;
    const container = document.getElementById('customerSelectContainer');
    const unpaidStatus = document.getElementById('unpaidStatus');
    const btn = document.getElementById('btnCheckout');

    if (method === 'Debt') {
        container.style.display = 'block';
        unpaidStatus.style.display = 'inline-block';
        btn.classList.replace('btn-danger', 'btn-warning');
        btn.style.color = '#000';
    } else {
        container.style.display = 'none';
        unpaidStatus.style.display = 'none';
        btn.classList.replace('btn-warning', 'btn-danger');
        btn.style.color = '#fff';
    }
}

async function fetchProducts() {
    try {
        const search = document.getElementById('productSearch').value;
        const catId = document.getElementById('categoryFilter').value;
        const res = await fetch(`ajax_pos_data.php?q=${search}&cat_id=${catId}`);
        const data = await res.json(); 
        
        if(data.status === 'success') {
            systemVatRate = parseFloat(data.vat_rate);
            document.getElementById('vatRateLabel').innerText = systemVatRate;
            allProducts = data.products;
            renderProducts(allProducts);
            
            // Populate Debtor dropdown
            const custSelect = document.getElementById('customer_id');
            if (data.customers && custSelect.options.length <= 1) {
                custSelect.innerHTML = `<option value="">-- ${uiLang.choose_debtor} --</option>`;
                data.customers.forEach(c => {
                    custSelect.innerHTML += `<option value="${c.id}">${c.fullname} (${uiLang.owes}: ${parseInt(c.total_debt).toLocaleString()} ${uiLang.currency})</option>`;
                });
            }
        }
    } catch (e) { console.error("Sync Error", e); }
}

function renderProducts(products) {
    const display = document.getElementById('productDisplay');
    display.innerHTML = '';
    products.forEach(p => {
        const isOutOfStock = p.stock_qty <= 0;
        display.innerHTML += `
            <div class="col-md-4 col-xl-3 col-6">
                <div class="card h-100 border-0 shadow-sm product-card ${isOutOfStock ? 'opacity-50' : ''}" 
                     onclick="${isOutOfStock ? '' : `addToCart(${p.id}, '${p.product_name.replace(/'/g, "\\'")}', ${p.selling_price}, ${p.stock_qty})`}">
                    <div class="card-body p-3">
                        <div class="text-muted tiny mb-1">${p.category_name}</div>
                        <h6 class="fw-bold text-dark product-title mb-2">${p.product_name}</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-800 text-danger small">${parseInt(p.selling_price).toLocaleString()}</span>
                            <span class="badge ${p.stock_qty < 10 ? 'bg-warning text-dark' : 'bg-light text-muted'} border" style="font-size: 0.6rem;">${p.stock_qty}</span>
                        </div>
                    </div>
                </div>
            </div>`;
    });
}

document.getElementById('productSearch').addEventListener('input', fetchProducts);
document.getElementById('categoryFilter').addEventListener('change', fetchProducts);

function addToCart(id, name, price, maxStock) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        if (existing.qty < maxStock) existing.qty++;
        else alert(uiLang.stock_limit);
    } else {
        cart.push({ id, name, price, qty: 1, maxStock });
    }
    renderCart();
}

function renderCart() {
    const tbody = document.querySelector('#cartTable tbody');
    tbody.innerHTML = '';
    let subtotal = 0;
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.qty;
        subtotal += itemTotal;
        tbody.innerHTML += `
            <tr class="cart-row">
                <td class="ps-3 py-3">
                    <div class="fw-bold small theme-text-main text-truncate" style="max-width:120px;">${item.name}</div>
                </td>
                <td class="text-center">
                    <input type="number" value="${item.qty}" class="form-control form-control-sm text-center mx-auto qty-field" onchange="updateQty(${index}, this.value)">
                </td>
                <td class="text-end pe-3 fw-bold theme-text-main">${parseInt(itemTotal).toLocaleString()}</td>
                <td class="pe-3"><button class="btn btn-sm text-danger border-0 p-0 shadow-none" onclick="removeFromCart(${index})"><i class="fas fa-times-circle"></i></button></td>
            </tr>`;
    });
    const vatAmount = subtotal * (systemVatRate / 100);
    document.getElementById('subtotalDisplay').innerText = subtotal.toLocaleString() + " " + uiLang.currency;
    document.getElementById('vatDisplay').innerText = vatAmount.toLocaleString() + " " + uiLang.currency;
    document.getElementById('grandTotalDisplay').innerText = (subtotal + vatAmount).toLocaleString() + " " + uiLang.currency;
    document.getElementById('btnCheckout').disabled = cart.length === 0;
}

function updateQty(i, v) { 
    const q = parseInt(v); 
    if(q > cart[i].maxStock) { alert("Max: " + cart[i].maxStock); cart[i].qty = cart[i].maxStock; }
    else if(q <= 0) removeFromCart(i);
    else cart[i].qty = q;
    renderCart(); 
}
function removeFromCart(i) { cart.splice(i, 1); renderCart(); }
function clearCart() { if(confirm(uiLang.clear_confirm)) { cart = []; renderCart(); } }

async function processSale() {
    const method = document.getElementById('payMethod').value;
    const custId = document.getElementById('customer_id').value;
    if (method === 'Debt' && !custId) return alert(uiLang.debtor_required);

    const btn = document.getElementById('btnCheckout');
    btn.disabled = true;
    btn.innerHTML = uiLang.processing;

    try {
        const res = await fetch('ajax_process_sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ payment_method: method, customer_id: custId, items: cart })
        });
        const result = await res.json();
        if (result.success) {
            alert(uiLang.success); 
            location.reload(); 
        } else {
            alert(result.message);
            btn.disabled = false;
            btn.innerHTML = "<?= __('process_transaction') ?>";
        }
    } catch (e) {
        alert("Server error.");
        btn.disabled = false;
    }
}

fetchProducts();
</script>

<style>
    .pos-screen { background-color: #f3f4f6; min-height: 100vh; }
    .product-card { cursor: pointer; transition: 0.3s; border-radius: 16px; border: 1.5px solid transparent !important; }
    .product-card:hover { transform: translateY(-5px); border-color: #e94560 !important; box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important; }
    .product-title { height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; font-size: 0.85rem; }
    .tiny { font-size: 0.6rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
    .qty-field { width: 55px; border-radius: 8px; font-weight: 800; border: none; background: #f3f4f6; }
    .fw-800 { font-weight: 800; }
    .fw-900 { font-weight: 900; }
    
    :root { --main-text: #111827; --card-bg: #ffffff; }
    [data-theme="dark"] { --main-text: #ffffff; --card-bg: #16161a; }
    .theme-text-main { color: var(--main-text); }
    .adaptive-card { background-color: var(--card-bg) !important; }
    
    /* Animation for Debt Badge */
    .animate__pulse { animation-duration: 1.5s; }
</style>

<?php include 'includes/footer.php'; ?>