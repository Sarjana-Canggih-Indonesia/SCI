<?php
// Halaman Promo promo.php

// Load application configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

startSession();

// Load environment configuration
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];
$pdo = getPDOConnection();
// Deteksi environment
$isLiveEnvironment = ($config['BASE_URL'] === $_ENV['LIVE_URL']);
setCacheHeaders($isLive); // Set header no cache saat local environment
// Set security headers.
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Step 1: Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
    header("Location: " . $baseUrl . "auth/login.php");
    exit();
}

// Step 2: Retrieve user information from the session and database.
$userInfo = getUserInfo($_SESSION['user_id']);
$profileImage = null;

// Step 3: Handle cases where the user is not found in the database.
if (!$userInfo) {
    handleError("User not found in the database. Redirecting...", $_ENV['ENVIRONMENT']);
    exit();
}

// Step 4: Set the user profile image. If not available, use a default image.
$profileImage = $userInfo['image_filename'] ?? 'default_profile_image.jpg';
$profileImageUrl = $baseUrl . "uploads/profile_images/" . $profileImage;

// Restrict access to non-admin users.
if ($userInfo['role'] !== 'admin') {
    handleError("Access denied! Role: " . $userInfo['role'], $_ENV['ENVIRONMENT']);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>favicon.ico" />
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css" />
</head>

<body style="background-color: #f7f9fb;">
    <!--========== INSERT HEADER.PHP ==========-->
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <!--========== AKHIR INSERT HEADER.PHP ==========-->

    <!--========== AREA SCROLL TO TOP ==========-->
    <div class="scroll">
        <!-- Scroll to Top Button -->
        <a href="#" class="scroll-to-top" id="scrollToTopBtn">
            <i class="fa-solid fa-angles-up"></i>
        </a>
    </div>
    <!--========== AKHIR AREA SCROLL TO TOP ==========-->

    <!--========== AREA PROMO ==========-->
    <div class="jarak-kustom container">
        <section class="judul-halaman-admin-dashboard">
            <h2 class="mb-4 text-center">Admin Dashboard</h2>
        </section>
        <section class="user-info-dan-navigasi-halaman-admin">
            <div class="row mb-4">
                <!-- User Info -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">User Information</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li>
                                    <img src="<?php echo htmlspecialchars($profileImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="Profile Image" class="img-thumbnail" style="width: 100px; height: 100px;">
                                </li>
                                <li><strong>Username:</strong> <?php echo htmlspecialchars($userInfo['username']); ?>
                                </li>
                                <li><strong>Email:</strong> <?php echo htmlspecialchars($userInfo['email']); ?></li>
                                <li><strong>Role:</strong> <?php echo htmlspecialchars($userInfo['role']); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Navigasi Halaman Admin -->
                <div class="col-md-6">
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary" role="button" href="<?php echo $baseUrl; ?>manage_products">
                            Manage Products
                        </a>
                        <a class="btn btn-primary" role="button" href="<?php echo $baseUrl; ?>manage_promos">
                            Manage Promos
                        </a>
                        <a class="btn btn-primary" role="button" href="#">
                            Manage Blogs
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!--========== AKHIR AREA PROMO ==========-->

    <!--================ AREA FOOTER =================-->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <!--================ AKHIR AREA FOOTER =================-->

    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
</body>

</html>