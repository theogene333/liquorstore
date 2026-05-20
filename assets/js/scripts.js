/**
 * Liquor Store Management System - JS Core
 * Handles POS Logic, UI Toggles, and Real-time Search
 */

document.addEventListener('DOMContentLoaded', function () {
    
    // 1. Sidebar Toggle Logic
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');

    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            content.classList.toggle('active');
        });
    }

    // 2. Real-time Table Search (Filter)
    // Add 'id="searchInput"' to any input and 'id="dataTable"' to your table
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const filter = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#dataTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // 3. POS System Logic
    let cart = [];
    const cartTableBody = document.querySelector('#cartTable tbody');
    const grandTotalDisplay = document.getElementById('grandTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    // Function to add product to POS cart
    window.addToCart = function (id, name, price) {
        // Check if item exists in cart
        const existingItem = cart.find(item => item.id === id);

        if (existingItem) {
            existingItem.qty += 1;
        } else {
            cart.push({
                id: id,
                name: name,
                price: parseFloat(price),
                qty: 1
            });
        }
        renderCart();
    };

    // Function to remove item from cart
    window.removeFromCart = function (index) {
        cart.splice(index, 1);
        renderCart();
    };

    // Function to update quantity
    window.updateQty = function (index, newQty) {
        if (newQty < 1) return;
        cart[index].qty = parseInt(newQty);
        renderCart();
    };

    // Function to render the cart table
    function renderCart() {
        if (!cartTableBody) return;

        cartTableBody.innerHTML = '';
        let total = 0;

        cart.forEach((item, index) => {
            const subtotal = item.price * item.qty;
            total += subtotal;

            cartTableBody.innerHTML += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.price.toLocaleString()} RWF</td>
                    <td style="width: 100px;">
                        <input type="number" class="form-control form-control-sm" 
                            value="${item.qty}" onchange="updateQty(${index}, this.value)">
                    </td>
                    <td class="fw-bold">${subtotal.toLocaleString()} RWF</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        grandTotalDisplay.innerText = total.toLocaleString();
        
        // Disable checkout if cart empty
        if (checkoutBtn) {
            checkoutBtn.disabled = cart.length === 0;
        }
    }

    // 4. POS Checkout (AJAX to PHP)
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function () {
            const paymentMethod = document.getElementById('paymentMethod').value;

            if (cart.length === 0) {
                alert("Cart is empty!");
                return;
            }

            const saleData = {
                payment_method: paymentMethod,
                items: cart
            };

            // Send data to PHP via Fetch API
            fetch('process_sale.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(saleData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Sale Completed! Invoice: #" + data.invoice_no);
                    cart = []; // Clear cart
                    renderCart();
                    window.location.reload(); // Reload to update stock
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Critical system error occurred.");
            });
        });
    }

    // 5. Automatic Disappearing Alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 4000);

});
