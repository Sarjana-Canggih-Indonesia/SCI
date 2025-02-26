<?php
// Include necessary files
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth/admin_functions.php';
require_once __DIR__ . '/../../../config/user_actions_config.php';

startSession();

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "login");
    exit();
}

// Retrieve user information from the session and database
$userInfo = getUserInfo($_SESSION['user_id'], $config, $env);

// Handle cases where the user is not found in the database
if (!$userInfo) {
    handleError("User not found in the database. Redirecting...", $_ENV['ENVIRONMENT']);
    exit();
}

// Check if the user is logged in and has admin privileges
if (!isset($userInfo['role']) || $userInfo['role'] !== 'admin') {
    handleError("Unauthorized access attempt", $_ENV['ENVIRONMENT']);
    header("Location: " . $baseUrl . "login");
    exit();
}

// Tangkap data dari form
$user_id = $_POST['user_id'];
$new_role = $_POST['new_role'];

try {
    // Panggil fungsi changeUserRole
    changeUserRole($_SESSION['user_id'], $user_id, $new_role, $config, $env);

    // Simpan pesan sukses ke session
    $_SESSION['success_message'] = "Role berhasil diubah menjadi $new_role.";
    $_SESSION['form_success'] = true;
} catch (Exception $e) {
    // Simpan pesan error ke session
    $_SESSION['error_message'] = $e->getMessage();
    $_SESSION['form_success'] = false;
}

// Redirect kembali ke halaman manage_users
header("Location: " . $baseUrl . "manage_users");
exit();