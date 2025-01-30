<?php
// resend_activation_email.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
use Symfony\Component\HttpClient\HttpClient;

// Start the session and generate a CSRF token
startSession();

// Load environment configuration
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);

// Validate reCAPTCHA environment variables
validateReCaptchaEnvVariables();

$client = HttpClient::create();

$message = '';

// Proses pengiriman ulang email aktivasi jika nama pengguna disediakan
$pdo = getPDOConnection();
if (!$pdo) {
    $message = 'Database connection failed. Please try again later.';
} else {
    // Menangani permintaan kirim ulang email aktivasi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        // Cek token CSRF yang dikirimkan
        $receivedCsrfToken = $_POST['csrf_token'] ?? null;
        $sessionCsrfToken = $_SESSION['csrf_token'] ?? null;

        // Validasi CSRF token
        if (!validateCsrfToken($receivedCsrfToken)) {
            $message = 'Invalid CSRF token. Please try again.';
        } else {
            // Validasi reCAPTCHA setelah CSRF token valid
            $validationResult = validateCsrfAndRecaptcha($_POST, $client);

            if ($validationResult !== true) {
                $message = $validationResult;
            } else {
                // Lanjutkan dengan proses jika validasi berhasil
                $username = trim($_POST['username']);
                $message = resendActivationEmail($username);
            }
        }
    }
}

// Jika CSRF token belum ada di session, buat token baru
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Activation Email - Sarjana Canggih Indonesia</title>
    <link rel="icon" href="<?php echo $baseUrl; ?>assets/images/logoscblue.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styles CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css">
    <script src="https://www.google.com/recaptcha/api.js" defer></script>
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
                    <img src="<?php echo $baseUrl; ?>assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia">
                </a>
            </div>
            <div class="card fat">
                <div class="card-body">
                    <h4 class="card-title">Kirim Ulang Email Aktivasi</h4>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="form-group mb-3">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                            <div class="invalid-feedback">
                                Username diperlukan.
                            </div>
                        </div>

                        <!-- reCAPTCHA -->
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>

                        <div class="form-group m-0">
                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">Kirim Ulang Email</button>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="<?php echo $baseUrl; ?>auth/login.php">Kembali ke Halaman Masuk</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>

</html>