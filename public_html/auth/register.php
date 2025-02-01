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

// Sanitize user input
$user_input = $_GET['input'] ?? '';
$sanitized_input = sanitize_input($user_input);

startSession();

$client = HttpClient::create();

// Validate reCAPTCHA environment variables
validateReCaptchaEnvVariables();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF dan reCAPTCHA
    $validationResult = validateCsrfAndRecaptcha($_POST, $client);

    if ($validationResult !== true) {
        $_SESSION['error_message'] = 'Invalid CSRF token or reCAPTCHA. Please try again.';
        header("Location: register.php");
        exit();
    } else {
        // Honeypot Field Check
        if (!empty($_POST['honeypot'])) {
            $_SESSION['error_message'] = 'Bot detected. Submission rejected.';
            header("Location: register.php");
            exit();
        } else {
            // Sanitize and validate form inputs
            $username = sanitize_input(trim($_POST['username']));
            $email = sanitize_input(trim($_POST['email']));
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate password confirmation
            if ($password !== $confirm_password) {
                $_SESSION['error_message'] = 'Passwords do not match.';
                header("Location: register.php");
                exit();
            }

            // Register the user
            $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';
            $registrationResult = registerUser($username, $email, $password, $env);

            // Periksa apakah hasilnya adalah array dengan pesan sukses
            if (strpos($registrationResult, 'Registration successful') !== false) {
                // Registrasi berhasil, ambil activation code
                preg_match('/Activation Code: (\S+)/', $registrationResult, $matches);
                $activationCode = $matches[1] ?? '';

                // Kirimkan email aktivasi
                $activationResult = sendActivationEmail($email, $activationCode, $username);

                if ($activationResult === true) {
                    $_SESSION['success_message'] = "Registration successful! Please check your email to activate your account.";
                } else {
                    // Jika pengiriman email gagal, tangani kesalahan
                    $_SESSION['error_message'] = $activationResult;
                }
            } else {
                // Jika registrasi gagal, tangani kesalahan
                $_SESSION['error_message'] = $registrationResult;
            }

            // Redirect ke halaman yang sama untuk menghindari resubmission
            header("Location: register.php");
            exit();
        }
    }
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
                <a href="<?php echo $config['BASE_URL']; ?>/">
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

                        <div class="form-group m-0">
                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">Register</button>
                        </div>
                        <div class="mt-4 text-center">
                            Sudah punya akun? <a href="<?php echo $baseUrl; ?>/auth/login.php">Masuk</a>
                        </div>
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