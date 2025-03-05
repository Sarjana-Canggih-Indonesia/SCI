<?php
// includes/admin-navbar.php

// Load the config file
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/nav/nav-functions.php';

// Load dynamic URL configuration from config.php
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);

// Start session from user_actions_config.php
startSession();

// Get user ID from session
$userId = $_SESSION['user_id'] ?? null;

// Check user login status
$isLoggedIn = isset($_SESSION['username']);
$username = $_SESSION['username'] ?? '';

// Set default values
$profileImage = null;
$userRole = null; // Stores user role

// Only process if user is logged in and has an ID
if ($isLoggedIn && $userId) {
    $userInfo = getUserInfo($userId, $config, $baseUrl);

    if ($userInfo) {
        // Set profile image filename if available in the database
        $profileImage = $userInfo['profile_image_filename'] ?? null;

        // Get user role
        $userRole = $userInfo['role'] ?? 'customer';
    } else {
        // Handle case if user is not found
        $error = 'User not found.';
    }
} else {
    // Handle case if user is not logged in
    $error = 'User is not logged in.';
}

// Set the profile image URL using the function
$profileImageUrl = default_profile_image($profileImage, $baseUrl, $config);

// Get URL to determine which page is active
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$baseUrlPath = parse_url($baseUrl, PHP_URL_PATH);
$relativePath = trim(str_replace($baseUrlPath, '', $currentUri), '/');
$activePage = $relativePath ?: 'home';
$is_active = function ($pageName) use ($activePage) {
    $currentPage = $activePage ?? 'home'; // Default ke 'home' jika $activePage tidak terdefinisi
    return str_starts_with($currentPage, $pageName) || $pageName === 'home' && empty($currentPage) ? 'active' : '';
};
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <!-- CSS untuk Navbar Admin -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/admin-navbar.css" />
</head>

<body>
    <!-- Admin Sidebar Offcanvas -->
    <div class="offcanvas offcanvas-start offcanvas-halaman-admin" tabindex="-1" id="adminSidebar"
        data-bs-scroll="true">
        <!-- Offcanvas Close Button -->
        <div class="offcanvas-header position-relative">
            <button type="button" class="logo-close-btn" data-toggle-minimize aria-label="Close">
                <img src="<?php echo $baseUrl; ?>assets/images/logoscblue.png" alt="Logo" class="close-logo">
            </button>
        </div>
        <!-- Offcanvas Content -->
        <div class="offcanvas-body p-0">
            <!-- Profile Section -->
            <div class="profile-section" data-toggle-minimize>
                <div class="profile-image rounded-circle">
                    <img src="<?php echo $profileImageUrl; ?>" alt="User Profile Image"
                        class="profile-img rounded-circle" />
                </div>
                <h6 class="mb-1"><?php echo htmlspecialchars($username); ?></h6>
                <small><?php echo htmlspecialchars($userInfo['email'] ?? 'admin@example.com'); ?></small>
            </div>

            <!-- Navigation Links -->
            <nav class="nav flex-column px-3">

                <!-- Home Link -->
                <a href="<?php echo $baseUrl; ?>" class="nav-link <?= $is_active('home') ?>">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>

                <div class="separator"></div>

                <!-- Dashboard Link -->
                <a href="<?= $baseUrl ?>admin-dashboard" class="nav-link <?= $is_active('admin-dashboard') ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Manage Users Link -->
                <a href="= $baseUrl ?>manage_users" class="nav-link <?= $is_active('manage_users') ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </a>
                <!-- Manage Products Link -->
                <a href="<?php echo $baseUrl; ?>manage_products" class="nav-link <?= $is_active('manage_products') ?>">
                    <i class="fas fa-box-open"></i>
                    <span>Manage Products</span>
                </a>
                <!-- Manage Promos Link -->
                <a href="<?php echo $baseUrl; ?>manage_promos" class="nav-link <?= $is_active('manage_promos') ?>">
                    <i class="fas fa-percent"></i>
                    <span>Manage Promos</span>
                </a>
                <a href="#" class="nav-link <?= $is_active('manage_projects') ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Manage Projects</span>
                </a>

                <div class="separator"></div>

                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Setting</span>
                </a>

                <a href="<?php echo $baseUrl; ?>logout" class="btn btn-outline-danger logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Menu Toggle Button -->
    <button class="btn menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('adminSidebar');
            const toggleElements = document.querySelectorAll('[data-toggle-minimize]');
            const bsOffcanvas = new bootstrap.Offcanvas(sidebar);

            // Fungsi toggle minimize
            const toggleMinimize = () => {
                const isMinimized = !sidebar.classList.contains('minimized');
                sidebar.classList.toggle('minimized', isMinimized);
                localStorage.setItem('sidebarMinimized', isMinimized);
            }

            // Handle semua toggle element
            toggleElements.forEach(element => {
                element.addEventListener('click', (e) => {
                    if (window.innerWidth >= 992) {
                        e.preventDefault();
                        toggleMinimize();
                    }
                });
            });

            // Handle resize
            const handleResize = () => {
                if (window.innerWidth >= 992) {
                    bsOffcanvas.show();
                    const wasMinimized = localStorage.getItem('sidebarMinimized') === 'true';
                    sidebar.classList.toggle('minimized', wasMinimized);
                } else {
                    bsOffcanvas.hide();
                    sidebar.classList.remove('minimized');
                }
            }

            // Initial setup
            const initSidebar = () => {
                if (window.innerWidth >= 992) {
                    bsOffcanvas.show();
                    const wasMinimized = localStorage.getItem('sidebarMinimized') === 'true';
                    sidebar.classList.toggle('minimized', wasMinimized);
                }
            }

            // Event listeners
            window.addEventListener('resize', handleResize);
            initSidebar();

            // Handle close button click di mobile
            document.querySelector('.logo-close-btn').addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    bsOffcanvas.hide();
                }
            });
        });
    </script>
</body>

</html>