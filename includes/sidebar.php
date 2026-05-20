<?php
// Ensure language configuration is loaded
require_once 'config/lang_config.php';

// Get the current page filename to apply the 'active' class
$current_page = basename($_SERVER['PHP_SELF']);

/**
 * Helper function to set active class
 */
function isActive($pageName, $current_page) {
    return ($pageName == $current_page) ? 'active' : '';
}

// Role-Based Access Logic
$role = $_SESSION['role'] ?? 'Guest';

$isAdmin    = ($role == 'Super Admin');
$isManager  = ($role == 'Manager' || $role == 'Super Admin');
$isCashier  = ($role == 'Cashier' || $role == 'Manager' || $role == 'Super Admin');
$isWaiter   = ($role == 'Waiter' || $role == 'Cashier' || $role == 'Manager' || $role == 'Super Admin');
?>

<!-- Sidebar -->
<nav id="sidebar" class="modern-sidebar">
    <!-- Brand Info -->
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <div class="brand-logo shadow-sm">
                <i class="fas fa-glass-cheers text-white"></i>
            </div>
            <div class="ms-3">
                <h6 class="brand-name mb-0">LIQUOR <span class="accent-text">RW</span></h6>
                <div class="brand-status">
                    <span class="status-dot pulse"></span> <?= __("online_portal") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- User Profile Section (Modern Glass Effect) -->
    <div class="user-profile-section mx-3 my-4">
        <div class="d-flex align-items-center p-3 rounded-4 glass-card">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['fullname']) ?>&background=e94560&color=fff" class="rounded-circle border border-2 border-white shadow-sm" width="45">
            <div class="ms-3 overflow-hidden">
                <p class="user-name mb-0 text-white text-truncate"><?= $_SESSION['fullname'] ?></p>
                <span class="user-role badge"><?= __($role) ?></span>
            </div>
        </div>
    </div>

    <ul class="list-unstyled components px-3">
        <!-- Section: Main -->
        <p class="sidebar-label"><?= __("main_hub") ?></p>
        
        <li class="nav-item <?= isActive('index.php', $current_page) ?>">
            <a href="index.php"><i class="fas fa-th-large"></i> <span><?= __("dashboard") ?></span></a>
        </li>

        <li class="nav-item <?= isActive('pos.php', $current_page) ?>">
            <a href="pos.php" class="pos-highlight">
                <i class="fas fa-cash-register"></i> <span><?= __("pos_system") ?></span>
            </a>
        </li>

        <!-- Section: Inventory (Managers and Admins Only) -->
        <?php if($isManager): ?>
        <p class="sidebar-label"><?= __("inventory") ?></p>
        
        <li class="nav-item <?= isActive('products.php', $current_page) ?>">
            <a href="products.php"><i class="fas fa-wine-bottle"></i> <span><?= __("products") ?></span></a>
        </li>
        
        <li class="nav-item <?= isActive('categories.php', $current_page) ?>">
            <a href="categories.php"><i class="fas fa-tags"></i> <span><?= __("categories") ?></span></a>
        </li>
        <?php endif; ?>

        <!-- Section: Business & Financials -->
        <p class="sidebar-label"><?= __("business") ?></p>

        <li class="nav-item <?= isActive('sales.php', $current_page) ?>">
            <a href="sales.php"><i class="fas fa-history"></i> <span><?= __("sales_history") ?></span></a>
        </li>

        <li class="nav-item <?= isActive('debts.php', $current_page) ?>">
            <a href="debts.php"><i class="fas fa-hand-holding-usd"></i> <span><?= __("debts") ?></span></a>
        </li>

        <?php if($isManager): ?>
        <li class="nav-item <?= isActive('suppliers.php', $current_page) ?>">
            <a href="suppliers.php"><i class="fas fa-truck-loading"></i> <span><?= __("suppliers") ?></span></a>
        </li>
        
        <li class="nav-item <?= isActive('expenses.php', $current_page) ?>">
            <a href="expenses.php"><i class="fas fa-wallet"></i> <span><?= __("expenses") ?></span></a>
        </li>
        <?php endif; ?>

        <!-- Section: Intelligence -->
        <?php if($isManager): ?>
        <p class="sidebar-label"><?= __("intelligence") ?></p>
        
        <li class="nav-item <?= isActive('sales_report.php', $current_page) ?>">
            <a href="sales_report.php"><i class="fas fa-file-invoice-dollar"></i> <span><?= __("audit_reports") ?></span></a>
        </li>
        
        <li class="nav-item <?= isActive('reports.php', $current_page) ?>">
            <a href="reports.php"><i class="fas fa-chart-pie"></i> <span><?= __("analytics") ?></span></a>
        </li>
        <?php endif; ?>

        <!-- Section: Administration -->
        <?php if($isAdmin): ?>
        <p class="sidebar-label"><?= __("administration") ?></p>
        
        <li class="nav-item <?= isActive('users.php', $current_page) ?>">
            <a href="users.php"><i class="fas fa-user-shield"></i> <span><?= __("manage_staff") ?></span></a>
        </li>
        
        <li class="nav-item <?= isActive('settings.php', $current_page) ?>">
            <a href="settings.php"><i class="fas fa-sliders-h"></i> <span><?= __("settings") ?></span></a>
        </li>
        <?php endif; ?>

        <li class="mt-5 pb-5">
            <a href="logout.php" class="logout-link rounded-3">
                <i class="fas fa-power-off"></i> <span><?= __("logout") ?></span>
            </a>
        </li>
    </ul>
</nav>

<style>
/* Modern Professional Sidebar Styles */
:root {
    --sidebar-bg: #111827; 
    --sidebar-hover: #1f2937;
    --accent-red: #e94560;
    --glass-white: rgba(255, 255, 255, 0.05);
    --text-inactive: #9ca3af;
}

#sidebar {
    min-width: 280px;
    max-width: 280px;
    background: var(--sidebar-bg);
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 999;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 10px 0 30px rgba(0,0,0,0.1);
    overflow-y: auto;
    overflow-x: hidden;
}

/* Custom Scrollbar */
#sidebar::-webkit-scrollbar { width: 5px; }
#sidebar::-webkit-scrollbar-track { background: var(--sidebar-bg); }
#sidebar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
#sidebar::-webkit-scrollbar-thumb:hover { background: var(--accent-red); }

/* Header & Brand */
.sidebar-header { padding: 30px 25px; background: rgba(0,0,0,0.1); }
.brand-logo { width: 42px; height: 42px; background: linear-gradient(135deg, var(--accent-red), #ff6b6b); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.brand-name { font-weight: 800; letter-spacing: 1px; font-size: 1.1rem; }
.accent-text { color: var(--accent-red); }
.brand-status { font-size: 0.65rem; color: #10b981; font-weight: 600; text-transform: uppercase; }

/* Glass Profile Card */
.glass-card { background: var(--glass-white); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
.user-name { font-weight: 600; font-size: 0.85rem; }
.user-role { background: rgba(233, 69, 96, 0.2); color: var(--accent-red); font-size: 0.6rem; padding: 4px 8px; text-transform: uppercase; }

/* Navigation Links */
.sidebar-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: #4b5563; letter-spacing: 1.5px; margin-bottom: 10px; margin-top: 20px; padding-left: 10px; }
#sidebar ul li a { padding: 12px 18px; font-size: 0.9rem; display: flex; align-items: center; color: var(--text-inactive); text-decoration: none; transition: 0.2s; border-radius: 12px; margin-bottom: 4px; }
#sidebar ul li a i { width: 30px; font-size: 1.1rem; }
#sidebar ul li a:hover { color: #fff; background: var(--sidebar-hover); transform: translateX(5px); }
#sidebar ul li.active > a { color: #fff; background: var(--accent-red); box-shadow: 0 4px 15px rgba(233, 69, 96, 0.3); font-weight: 600; }

.pos-highlight { background: rgba(16, 185, 129, 0.1) !important; color: #10b981 !important; border: 1px dashed rgba(16, 185, 129, 0.3); }
.logout-link { color: #ef4444 !important; border: 1px solid rgba(239, 68, 68, 0.2); }
.pulse { display: inline-block; width: 6px; height: 6px; background: #10b981; border-radius: 50%; margin-right: 4px; animation: p-pulse 2s infinite; }

@keyframes p-pulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

@media (max-width: 992px) {
    #sidebar { margin-left: -280px; }
    #sidebar.active { margin-left: 0; }
}
</style>