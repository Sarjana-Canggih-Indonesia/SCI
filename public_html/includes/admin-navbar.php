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
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <!-- Offcanvas Content -->
        <div class="offcanvas-body p-0">
            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-image rounded-circle">
                    <img src="<?php echo $profileImageUrl; ?>" alt="User Profile Image"
                        class="profile-img rounded-circle" />
                </div>
                <h6 class="mb-1"><?php echo htmlspecialchars($username); ?></h6>
                <small><?php echo htmlspecialchars($userInfo['email'] ?? 'admin@example.com'); ?></small>
            </div>

            <!-- Navigation Links -->
            <nav class="nav flex-column px-3">

                <a href="<?php echo $baseUrl; ?>" class="nav-link active">
                    <i class="fas fa-home"></i>
                    Home
                </a>

                <div class="separator"></div>

                <a href="<?php echo $baseUrl; ?>admin-dashboard" class="nav-link">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>

                <div class="separator"></div>

                <a href="<?php echo $baseUrl; ?>manage_users" class="nav-link">
                    <i class="fas fa-users-cog"></i>
                    Manage Users
                </a>
                <a href="<?php echo $baseUrl; ?>manage_products" class="nav-link">
                    <i class="fas fa-box-open"></i>
                    Manage Products
                </a>
                <a href="<?php echo $baseUrl; ?>manage_promos" class="nav-link">
                    <i class="fas fa-percent"></i>
                    Manage Promos
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-tasks"></i>
                    Manage Projects
                </a>

                <div class="separator"></div>

                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Setting
                </a>

                <button class="btn btn-outline-danger logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </nav>
        </div>
    </div>

    <!-- Menu Toggle Button -->
    <button class="btn menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
</body>

</html>