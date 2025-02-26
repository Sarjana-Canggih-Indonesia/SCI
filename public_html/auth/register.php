<?php
// register.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/auth/validate.php';
use Symfony\Component\HttpClient\HttpClient;
use voku\helper\AntiXSS;

// Memuat konfigurasi lingkungan
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';
$isLive = $config['is_live'];

setCacheHeaders($isLive); // Set header no cache saat local environment

// Sanitize user input
$user_input = $_GET['input'] ?? '';
$sanitized_input = sanitize_input($user_input);

startSession();

$client = HttpClient::create();

// Validate reCAPTCHA environment variables
validateReCaptchaEnvVariables();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleRegistration($client, $baseUrl, $config);
}

// Redirect to the index page if the user is already logged in
redirect_if_logged_in();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $baseUrl; ?>assets/images/logoscblue.png" type="image/x-icon">
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <!-- Custom Styles CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css">
    <!-- Google reCAPTCHA -->
    <script type="text/javascript" src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- Membuat konten rata tengah -->
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }
    </style>
</head>

<body class="login-page">
    <section class="h-100 d-flex justify-content-center align-items-center">
        <div class="card-wrapper">
            <div class="brand">
                <a href="<?php echo $baseUrl; ?>">
                    <img src="<?php echo $baseUrl; ?>assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia"
                        srcset=""></a>
            </div>
            <div class="card fat">
                <div class="card-body">
                    <h4 class="card-title">Buat Akun</h4>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; ?></div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Bagian Form -->
                    <form action="" method="POST" class="halaman-register" novalidate="">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="register_username" name="username" class="form-control" required
                                autofocus>
                            <div class="invalid-feedback">
                                Username is required
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">E-Mail Address</label>
                            <input type="email" id="register_email" name="email" class="form-control" required>
                            <div class="invalid-feedback">
                                Email is required
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="register_password">Password</label>
                            <div style="position:relative" id="posisi_register_password_0">
                                <input id="register_password" type="password" name="password" class="form-control"
                                    required autofocus style="padding-right: 60px;" />
                                <div class="invalid-feedback">Password is required</div>
                                <div class="btn btn-sm" id="toggle_register_password_0"
                                    style="position: absolute; right: 10px; top: 7px; padding: 2px 7px; font-size: 16px; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="register_confirm_password">Konfirmasi Password</label>
                            <div style="position:relative" id="posisi_register_password_1">
                                <input id="register_confirm_password" type="password" name="confirm_password"
                                    class="form-control" required style="padding-right: 60px;" />
                                <div class="invalid-feedback">Passwords do not match</div>
                                <div class="btn btn-sm" id="toggle_register_password_1"
                                    style="position: absolute; right: 10px; top: 7px; padding: 2px 7px; font-size: 16px; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>

                        <br>
                        <!-- Honeypot Field -->
                        <input type="text" name="honeypot" id="register_honeypot" class="honeypot"
                            style="display: none;">
                        <input type="hidden" name="csrf_token" id="register_csrf_token"
                            value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>"
                            required></div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">Register</button>
                        <a href="<?php echo $baseUrl; ?>login"
                            class="mt-4 text-center btn btn-outline-primary w-100 d-block text-decoration-none"
                            role="button">
                            Sudah punya akun? Login
                        </a>
                    </form>
                    <!-- Akhir Bagian Form -->
                </div>
            </div>
    </section>
</body>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/register.js"></script>

</html>