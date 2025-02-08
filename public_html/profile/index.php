<?php
// profile/index.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

use Carbon\Carbon;

// Memulai sesi apabila tidak ada
startSession();

// Memuat konfigurasi URL Dinamis
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = (isset($_ENV['LIVE_URL']) && $_ENV['LIVE_URL'] === getBaseUrl($config, $_ENV['LIVE_URL']));

// Set header no cache saat local environment
setCacheHeaders($isLive);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Klien</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>favicon.ico" />
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css" />
    </style>
    <style>
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <section class="profile-sidebar">
            <div class="sidebar">
                <a href="#" class="sidebar-brand d-block mb-4">
                    <i class="fas fa-user-circle me-2"></i>My Profile
                </a>
                <ul class="sidebar-nav nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-home"></i>
                            Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-shopping-cart"></i>
                            Shopping Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-history"></i>
                            Order History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-tasks"></i>
                            Active Orders
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="#">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </section>

        <section class="profile-maincontent container mt-5 ms-3" style="flex: 1;">
            <div class="container mt-5">
                <!-- Profile Header -->
                <div class="card profile-card">
                    <div class="profile-header">
                        <img src="https://placehold.co/140x140" alt="Foto Profil" class="profile-img mb-3">
                        <h3 id="profile-client-name" class="mb-1">John Doe</h3>
                        <p id="profile-client-email" class="text-light mb-3">johndoe@example.com</p>
                        <button class="btn btn-light btn-custom" onclick="editProfile()">
                            <i class="fas fa-edit me-2"></i>Edit Profil
                        </button>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="card profile-card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Informasi Pribadi</h5>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Nama Depan</span>
                                <span class="text-muted" id="profile-first-name">John</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Nama Belakang</span>
                                <span class="text-muted" id="profile-last-name">Doe</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Nomor Telepon</span>
                                <span class="text-muted" id="profile-phone">+628123456789</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Email</span>
                                <span class="text-muted" id="profile-client-email-info">johndoe@example.com</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Tanggal Lahir</span>
                                <span class="text-muted" id="profile-birthday">01 Januari 1990</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Modal Edit Profil -->
            <div class="modal fade" id="profile-editProfileModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Profil</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="profile-editProfileForm">
                                <div class="mb-3">
                                    <label class="form-label">Nama Depan</label>
                                    <input type="text" class="form-control" id="profile-edit-first-name"
                                        placeholder="Masukkan Nama Depan" />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Belakang</label>
                                    <input type="text" class="form-control" id="profile-edit-last-name"
                                        placeholder="Masukkan Nama Belakang" />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nomor Telepon</label>
                                    <input type="text" class="form-control" id="profile-edit-phone"
                                        placeholder="+6200xxx" />
                                    <small class="form-text text-muted">Contoh: +6200xxx Maks 15 digit</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="profile-edit-email"
                                        placeholder="contoh@email.com" />
                                    <small class="form-text text-muted">Contoh: contoh@email.com</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="profile-edit-birthday" />
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/profile.js"></script>
</body>

</html>