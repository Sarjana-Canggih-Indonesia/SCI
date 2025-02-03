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

// Set default value for $profileImage
$profileImage = null;

// Only process if user is logged in and has an ID
if ($isLoggedIn && $userId) {
    $userInfo = getUserInfo($userId);

    if ($userInfo) {
        // Set profile image filename if available in the database
        $profileImage = $userInfo['image_filename'] ?? null;
    } else {
        // Handle case if user is not found
        $error = 'User not found.';
    }
} else {
    // Handle case if user is not logged in
    $error = 'User is not logged in.';
}

// Set the profile image URL using the function
$profileImageUrl = default_profile_image($profileImage);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>products/">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>promo/">Promo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>blogs/">Blogs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>about_us/">About
                            Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>contact/">Contact
                            Us</a>
                    </li>
                    <?php if (!empty($username)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo htmlspecialchars($profileImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="Profile" width="40" height="40" class="rounded-circle" />
                                <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin_dashboard.php">Dashboard</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>settings.php">Akun
                                        Saya</a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>cart.php">Pesanan
                                        Saya</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>auth/logout.php">Logout</a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Menampilkan tombol login jika belum login -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>auth/login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header">
            <h5 id="offcanvasNavbarLabel">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav flex-grow-1">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="<?php echo $baseUrl; ?>#">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>products/">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>promo/">Promo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>blogs/">Blogs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>about_us/">About
                        Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>contact/">Contact
                        Us</a>
                </li>
                <!-- Menampilkan profile dan logout -->
                <?php if (!empty($username)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <img src="<?php echo htmlspecialchars($profileImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile"
                                width="40" height="40" class="rounded-circle" />
                            <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin_dashboard.php">Dashboard</a>
                    </li>
                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>settings.php">Profil
                            Saya</a></li>
                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>cart.php">Pesanan Saya</a>
                    </li>
                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <!-- Menampilkan login jika belum login -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>auth/login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <!-- AKHIR OFFCANVAS MENU -->
    <!-- ==========AKHIR AREA NAVIGASI========== -->
</body>

</html>