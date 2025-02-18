<?php
// resend_activation_email.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

use Symfony\Component\HttpClient\HttpClient;
use voku\helper\AntiXSS;

// Start session and load environment config
startSession();
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';
$isLive = $config['is_live'];
// Deteksi environment
$isLiveEnvironment = ($config['BASE_URL'] === $_ENV['LIVE_URL']);
setCacheHeaders($isLive); // Set header no cache saat local environment

// Validate reCAPTCHA environment variables
validateReCaptchaEnvVariables();

$client = HttpClient::create();
$message = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot Field Check
    if (!empty($_POST['honeypot'])) {
        handleError('Bot detected. Submission rejected.', $env);
    } else {
        // Validasi CSRF dan reCAPTCHA
        $validationResult = validateCsrfAndRecaptcha($_POST, $client);
        if ($validationResult !== true) {
            $message = 'Invalid CSRF token or reCAPTCHA. Please try again.';
        } else {
            // Validasi input kosong
            if (empty(trim($_POST['identifier']))) {
                $message = 'Email atau Username tidak boleh kosong.';
            } else {
                // Validasi dan sanitasi input
                $identifier = sanitize_input(trim($_POST['identifier']));
                $message = resendActivationEmail($identifier);
            }
        }
    }
}

// Generate CSRF token if not exists
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
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
                    <!-- Kembali ke halaman login -->
                    <div class="d-flex text-start mb-2">
                        <a href="<?php echo $baseUrl; ?>login" class="btn btn-outline-primary"
                            onclick="return confirm('Are you sure you want to go back?');">
                            <i class="fa fa-arrow-left"></i> Back to Login</a>
                    </div>

                    <h4 class="card-title">Kirim Ulang Email Aktivasi</h4>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form id="form-resend-activation-code" action="" method="POST">
                        <!-- Honeypot Field -->
                        <input type="text" name="honeypot" class="honeypot" style="display: none;">

                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="form-group mb-3">
                            <label for="identifier">Email atau Username</label>
                            <input type="text" id="identifier" name="identifier" class="form-control" required>
                            <div class="invalid-feedback">Field ini diperlukan.</div>
                        </div>

                        <!-- reCAPTCHA -->
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>

                        <div class="form-group m-0">
                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">Kirim Ulang Email</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>

<!-- External JS -->
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
<!-- Custom JavaScript untuk validasi -->
<script>
    document.getElementById('form-resend-activation-code').addEventListener('submit', function (event) {
        let identifierField = document.getElementById('identifier');
        let identifierValue = identifierField.value.trim();

        if (identifierValue === '') {
            event.preventDefault(); // Mencegah submit
            alert('Email atau Username tidak boleh kosong.');
            identifierField.classList.add('is-invalid');
        } else {
            identifierField.classList.remove('is-invalid');
        }
    });
</script>

</html>