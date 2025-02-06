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
header('Cache-Control: ' . ($isLive
    ? 'public, max-age=3600, must-revalidate'
    : 'no-cache, must-revalidate'));
header('Expires: ' . ($isLive
    ? Carbon::now()->addHour()->toRfc7231String()
    : Carbon::now()->subYear()->toRfc7231String()));
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
    <style>
        body {
            background-color: #f8f9fa;
        }

        .profile-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .profile-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            margin: -1rem -1rem 1rem -1rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
            color: white;
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .card-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background: #667eea;
            border-radius: 2px;
        }

        .list-group-item {
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            border-radius: 6px;
        }

        .btn-custom {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
        }

        .order-list {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .order-list::-webkit-scrollbar {
            width: 6px;
        }

        .order-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .order-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .order-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .sidebar {
            width: 280px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
            padding: 1.5rem;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2rem;
            padding: 0 1rem;
            text-decoration: none;
            display: block;
        }

        .sidebar-nav .nav-item {
            margin-bottom: 0.5rem;
            position: relative;
        }

        .sidebar-nav .nav-link {
            color: #4a5568;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s ease;
            position: relative;
            font-weight: 500;
        }

        .sidebar-nav .nav-link:hover {
            background-color: #e2e8f0;
            color: #2d3748;
            transform: translateX(5px);
        }

        .sidebar-nav .nav-link.active {
            background-color: #ebf4ff;
            color: #3b82f6;
            box-shadow: 2px 2px 8px rgba(59, 130, 246, 0.1);
        }

        .sidebar-nav .nav-link.active:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background-color: #3b82f6;
            border-radius: 0 4px 4px 0;
        }

        .sidebar-nav .nav-link i {
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
        }

        .container {
            margin-left: 280px;
            width: calc(100% - 280px);
            transition: all 0.3s ease;
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

        <div class="container mt-5 ms-3" style="flex: 1;">
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

                <!-- Active Orders -->
                <div class="card profile-card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Pesanan Aktif</h5>
                        <ul class="list-group order-list">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Desain Presentasi
                                <span class="badge bg-warning">Sedang Diproses</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Analisis Data
                                <span class="badge bg-success">Selesai</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Order History -->
                <div class="card profile-card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Riwayat Pesanan</h5>
                        <ul class="list-group order-list">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Pembuatan Website
                                <span class="badge bg-success">Selesai</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Optimasi SEO
                                <span class="badge bg-success">Selesai</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="card profile-card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Keamanan Akun</h5>
                        <button class="btn btn-warning btn-custom w-100 mb-3" onclick="changeEmail()">
                            <i class="fas fa-envelope me-2"></i>Ubah Email
                        </button>
                        <button class="btn btn-danger btn-custom w-100" onclick="resetPassword()">
                            <i class="fas fa-key me-2"></i>Reset Password
                        </button>
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

            <!-- Modal Change Email -->
            <div class="modal fade" id="profile-changeEmailModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Ubah Email</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id=" profile-changeEmailForm">
                                <div class="mb-3">
                                    <label class="form-label">Email Baru</label>
                                    <input type="email" class="form-control" id="profile-edit-new-email" required>
                                </div>
                                <button type="submit" class="btn btn-warning w-100">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Reset Password -->
            <div class="modal fade" id="profile-resetPasswordModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reset Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="profile-resetPasswordForm">
                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="profile-edit-new-password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control" id="profile-edit-confirm-password"
                                        required>
                                </div>
                                <button type="submit" class="btn btn-danger w-100">Reset</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/profile.js"></script>
</body>

</html>