<?php
// 1. INCLUDE LANGUAGE CONFIGURATION (Defines __() function and $lang array)
require_once 'config/lang_config.php';

// Start session and verify login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: If session is not set, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Current page title logic
$current_file = basename($_SERVER['PHP_SELF'], ".php");
$page_title = ucwords(str_replace("_", " ", $current_file));
if($page_title == "Index") $page_title = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __($page_title) ?> | Liquor Store RW</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 (Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom System CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --primary-navy: #111827;
            --accent-red: #e94560;
            --sidebar-width: 280px;
            --top-nav-height: 70px;
            --bg-body: #f3f4f6;
            --bg-navbar: rgba(255, 255, 255, 0.85);
            --text-main: #111827;
            --border-color: rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] {
            --bg-body: #0f0e17;
            --bg-navbar: rgba(22, 22, 26, 0.9);
            --text-main: #fffffe;
            --border-color: rgba(255, 255, 255, 0.05);
            --primary-navy: #ffffff;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; transition: background 0.3s ease; }
        .wrapper { display: flex; align-items: stretch; min-height: 100vh; }
        #content { width: calc(100% - var(--sidebar-width)); margin-left: var(--sidebar-width); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); min-height: 100vh; display: flex; flex-direction: column; }
        .navbar-custom { height: var(--top-nav-height); background: var(--bg-navbar); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-bottom: 1px solid var(--border-color); padding: 0 30px; z-index: 998; }
        .btn-toggle-sidebar { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: var(--bg-navbar); border: 1px solid var(--border-color); color: var(--text-main); transition: 0.2s; }
        .page-header-title { font-weight: 700; color: var(--primary-navy); margin-left: 20px; font-size: 1.1rem; }
        .nav-icon-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 12px; color: var(--text-main); background: transparent; border: none; transition: 0.2s; }
        .nav-icon-btn:hover { background: rgba(128, 128, 128, 0.1); color: var(--accent-red); }
        .location-badge { background: var(--bg-navbar); border: 1px solid var(--border-color); padding: 6px 16px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .location-dot { height: 8px; width: 8px; background-color: #10b981; border-radius: 50%; margin-right: 10px; animation: pulse-green 2s infinite; }
        @keyframes pulse-green { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
        .user-profile-img { width: 42px; height: 42px; border-radius: 12px; border: 2px solid var(--accent-red); padding: 2px; background: #fff; }
        .dropdown-menu-modern { border: none; border-radius: 18px; box-shadow: 0 15px 50px rgba(0,0,0,0.15); padding: 12px; min-width: 220px; background: var(--bg-navbar); backdrop-filter: blur(20px); }
        .dropdown-item { border-radius: 10px; padding: 10px 15px; font-size: 0.85rem; font-weight: 500; color: var(--text-main); }
        @media (max-width: 992px) { #content { width: 100%; margin-left: 0; } }
    </style>
</head>
<body>

<script>
    const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
</script>

<div class="wrapper">
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
            <div class="container-fluid d-flex justify-content-between align-items-center p-0">
                <div class="d-flex align-items-center">
                    <button type="button" id="sidebarCollapse" class="btn-toggle-sidebar shadow-none">
                        <i class="fas fa-bars-staggered"></i>
                    </button>
                    <h5 class="page-header-title mb-0 d-none d-md-block"><?= __($page_title) ?></h5>
                </div>

                <div class="d-flex align-items-center">
                    <button class="nav-icon-btn me-2" id="themeToggle" onclick="toggleTheme()" title="<?= __('toggle_theme') ?>">
                        <i class="fas fa-moon fs-5"></i>
                    </button>

                    <div class="dropdown me-2">
                        <button class="nav-icon-btn" type="button" data-bs-toggle="dropdown" title="<?= __('select_language') ?>">
                            <i class="fas fa-globe fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-modern border-0">
                            <li><h6 class="dropdown-header small fw-bold"><?= __('select_language') ?></h6></li>
                            <li><a class="dropdown-item" href="?lang=en">🇺🇸 English</a></li>
                            <li><a class="dropdown-item" href="?lang=rw">🇷🇼 Kinyarwanda</a></li>
                            <li><a class="dropdown-item" href="?lang=fr">🇫🇷 Français</a></li>
                            <li><a class="dropdown-item" href="?lang=sw">🇹🇿 Kiswahili</a></li>
                        </ul>
                    </div>
                    
                    <div class="me-4 d-none d-lg-block">
                        <div class="location-badge"><span class="location-dot"></span> Kigali, RW</div>
                    </div>

                    <div class="dropdown">
                        <button class="user-dropdown-btn d-flex align-items-center border-0 bg-transparent" type="button" data-bs-toggle="dropdown">
                            <div class="text-end me-3 d-none d-md-block">
                                <span class="d-block fw-bold small mb-0" style="color: var(--primary-navy);"><?= $_SESSION['fullname'] ?></span>
                                <span class="badge rounded-pill bg-danger-subtle text-danger" style="font-size: 0.6rem;"><?= $_SESSION['role'] ?></span>
                            </div>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['fullname']) ?>&background=111827&color=fff&bold=true" alt="User" class="user-profile-img">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-modern animate slideIn">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2 opacity-50"></i> <?= __('profile') ?></a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2 opacity-50"></i> <?= __('settings') ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="fas fa-power-off me-2"></i> <?= __('logout') ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <script>
            function toggleTheme() {
                const html = document.documentElement;
                const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            }
            function updateThemeIcon(t) {
                const icon = document.querySelector('#themeToggle i');
                icon.className = (t === 'dark' ? 'fas fa-sun fs-5' : 'fas fa-moon fs-5');
                icon.style.color = (t === 'dark' ? '#fbbf24' : 'inherit');
            }
            updateThemeIcon(localStorage.getItem('theme'));
        </script>

        <div class="container-fluid py-4 px-4">