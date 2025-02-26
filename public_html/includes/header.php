<?php
// header.php

// Load application configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

// Load environment configuration
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);

// Start session only if not already active
startSession();

// Get user ID from session
$userId = $_SESSION['user_id'] ?? null;

// Check user login status
$isLoggedIn = isset($_SESSION['username']);
$username = $_SESSION['username'] ?? '';

// Set default values
$profileImage = null;
$userRole = null; // Menyimpan role user

// Only process if user is logged in and has an ID
if ($isLoggedIn && $userId) {
    $userInfo = getUserInfo($userId, $config, $baseUrl);

    if ($userInfo) {
        // Set profile image filename if available in the database
        $profileImage = $userInfo['profile_image_filename'] ?? null;

        // Ambil role user
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
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
</head>

<body>
    <!-- ==========AREA NAVIGASI========== -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand mx-auto" href="<?php echo $baseUrl; ?>">
                <img src="<?php echo $baseUrl; ?>assets/images/logoscblue.png" alt="Sarjana Canggih Indonesia"
                    width="64px" height="auto" />
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
                aria-controls="offcanvasNavbar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto custom-navbar">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="<?php echo $baseUrl; ?>">Home</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>products">Products</a>
                    </li> -->
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>promo">Promo</a>
                    </li> -->
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>blogs/">Blogs</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>contact">Contact
                            Us</a>
                    </li>
                    <?php if (!empty($username)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo htmlspecialchars($profileImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt=" Profile Image" width="40" height="40" class="rounded-circle" />
                                <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <?php if ($userRole === 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $baseUrl; ?>admin-dashboard">Dashboard</a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $baseUrl; ?>user-profile">User Profile</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $baseUrl; ?>settings.php">Akun Saya</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $baseUrl; ?>cart.php">Pesanan Saya</a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $baseUrl; ?>logout">Logout</a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Menampilkan tombol login jika belum login -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>login">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>

            </div>
        </div>
    </nav>
    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header bg-light">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column h-100">
            <ul class="navbar-nav flex-grow-1">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="<?php echo $baseUrl; ?>">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>products">
                        <i class="fa-solid fa-box"></i> Products
                    </a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>promo/">
                        <i class="fa-solid fa-tags"></i> Promo
                    </a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>blogs/">
                        <i class="fa-solid fa-blog"></i> Blogs
                    </a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>about">
                        <i class="fa-solid fa-users"></i> About Us
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>contact">
                        <i class="fa-solid fa-envelope"></i> Contact Us
                    </a>
                </li>
            </ul>

            <!-- Bagian profil dan logout di bawah -->
            <ul class="navbar-nav mt-auto">
                <?php if (!empty($username)): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="#">
                            <img src="<?php echo htmlspecialchars($profileImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile"
                                width="40" height="40" class="rounded-circle me-2" />
                            <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                    <?php if ($userRole === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>admin_dashboard.php">
                                <i class="fa-solid fa-chart-line"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>user-profile.php">
                            <i class="fa-solid fa-user"></i> Profil Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>settings.php">
                            <i class="fa-solid fa-gear"></i> Pengaturan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>cart.php">
                            <i class="fa-solid fa-shopping-cart"></i> Pesanan Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?php echo $baseUrl; ?>logout">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white" href="<?php echo $baseUrl; ?>login">
                            <i class="fa-solid fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <!-- AKHIR OFFCANVAS MENU -->
    <!-- ==========AKHIR AREA NAVIGASI========== -->
</body>


</html>