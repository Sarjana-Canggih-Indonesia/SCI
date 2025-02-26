<?php
// delete-user.php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth/admin_functions.php';
require_once __DIR__ . '/../../../config/user_actions_config.php';

startSession();

try {
    // Validasi session dan role admin
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Akses ditolak: Silakan login terlebih dahulu");
    }

    $userInfo = getUserInfo($_SESSION['user_id'], $config, $env);
    if (!$userInfo || $userInfo['role'] !== 'admin') {
        throw new Exception("Akses ditolak: Hanya admin yang boleh melakukan aksi ini");
    }

    // Validasi parameter
    if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
        throw new Exception("ID user tidak valid");
    }

    $user_id = (int) $_GET['user_id'];

    // Eksekusi penghapusan
    deleteUser($_SESSION['user_id'], $user_id, $config, $env);

    // Set flash message sukses
    $_SESSION['success_message'] = "User berhasil dihapus!";
    $_SESSION['form_success'] = true;

} catch (Exception $e) {
    // Handle error dan set flash message
    $_SESSION['error_message'] = $e->getMessage();
    $_SESSION['form_success'] = false;

    // Log error untuk environment development
    if (!isLive()) {
        error_log("Delete User Error: " . $e->getMessage());
    }
}

// Redirect kembali
header("Location: " . $baseUrl . "admin/manage_users.php");
exit();
