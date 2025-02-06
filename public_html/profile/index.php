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
            max-width: 600px;
            margin: auto;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
        }

        .order-list {
            max-height: 250px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="card shadow-sm profile-card">
            <div class="card-body text-center">
                <img src="https://via.placeholder.com/120" alt="Foto Profil" class="profile-img mb-3">
                <h4 id="profile-client-name">John Doe</h4>
                <p id="profile-client-email" class="text-muted">johndoe@example.com</p>
                <button class="btn btn-primary btn-sm" onclick="editProfile()">Edit Profil</button>
            </div>
        </div>

        <div class="card shadow-sm mt-4 profile-card">
            <div class="card-body">
                <h5 class="card-title">Informasi Pribadi</h5>
                <ul class="list-group">
                    <li class="list-group-item">Nama Depan: <span id="profile-first-name"></span></li>
                    <li class="list-group-item">Nama Belakang: <span id="profile-last-name"></span></li>
                    <li class="list-group-item">Nomor Telepon: <span id="profile-phone"></span></li>
                    <li class="list-group-item">Email: <span id="profile-client-email-info"></span></li>
                    <li class="list-group-item">Tanggal Lahir: <span id="profile-birthday"></span></li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm mt-4 profile-card">
            <div class="card-body">
                <h5 class="card-title">Pesanan Aktif</h5>
                <ul class="list-group order-list">
                    <li class="list-group-item">Desain Presentasi - <span class="badge bg-warning">Sedang
                            Diproses</span></li>
                    <li class="list-group-item">Analisis Data - <span class="badge bg-success">Selesai</span></li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm mt-4 profile-card">
            <div class="card-body">
                <h5 class="card-title">Riwayat Pesanan</h5>
                <ul class="list-group order-list">
                    <li class="list-group-item">Pembuatan Website - <span class="badge bg-success">Selesai</span></li>
                    <li class="list-group-item">Optimasi SEO - <span class="badge bg-success">Selesai</span></li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm mt-4 profile-card">
            <div class="card-body">
                <h5 class="card-title">Keamanan Akun</h5>
                <button class="btn btn-warning w-100 mb-2" onclick="changeEmail()">Ubah Email</button>
                <button class="btn btn-danger w-100" onclick="resetPassword()">Reset Password</button>
            </div>
        </div>
    </div>

    <!-- Modal Edit Profil -->
    <div class="modal fade" id="profile-editProfileModal" tabindex="-1">
        <div class="modal-dialog">
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
                            <input type="text" class="form-control" id="profile-edit-phone" placeholder="+6200xxx" />
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="profile-changeEmailForm">
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
        <div class="modal-dialog">
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
                            <input type="password" class="form-control" id="profile-edit-confirm-password" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Reset</button>
                    </form>
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