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

// Auto-detect current page from URL or filename
$scriptName = basename($_SERVER['SCRIPT_NAME'], ".php");

// Converts script names to valid page identifiers used in the navigation system
$pageMapping = [
    'admin-dashboard' => 'home',  // Maps manage_products.php to 'products' page
    'manage_products' => 'products',  // Maps manage_products.php to 'products' page
    'manage_users' => 'users',        // Maps manage_users.php to 'users' page
    'manage_promos' => 'promos'       // Maps manage_promos.php to 'promos' page
];

// Determine current page using mapping, fallback to 'home' if no match found
$currentPage = $pageMapping[$scriptName] ?? 'home';

// Render navigation bar with the determined current page
renderNavbar($currentPage);

// Get user ID from session.
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
    <p>PLACEHOLDER, Hello World</p>
</body>

</html>