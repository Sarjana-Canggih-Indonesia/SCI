<?php
// reset_password.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

startSession();

$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];
// Deteksi environment
$isLiveEnvironment = ($config['BASE_URL'] === $_ENV['LIVE_URL']);
setCacheHeaders($isLive); // Set header no cache saat local environment

$user_input = $_GET['input'] ?? '';
$sanitized_input = sanitize_input($user_input);

validateReCaptchaEnvVariables();
redirect_if_logged_in();

// Ambil token dari URL
$token = $_GET['hash'] ?? '';

// Get PDO connection
$pdo = getPDOConnection($config, $env);
if (!$pdo) {
    die('Database connection failed.');
}

// Handle password reset logic
handlePasswordReset($token, $pdo, $baseUrl);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Sarjana Canggih</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>favicon.ico" />
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
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
        <div class="card-wrapper text-center">
            <div class="brand">
                <a href="<?php echo $baseUrl; ?>">
                    <img src="<?php echo $baseUrl; ?>assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia">
                </a>
            </div>

            <div class="card fat">
                <div class="card-body">
                    <h4 class="text-start">Reset Password</h4>

                    <!-- Display the message if available -->
                    <?php if (isset($_GET['message'])): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($_GET['message']); ?></div>
                    <?php endif; ?>

                    <form class="my-reset-password-validation" action="" method="POST">
                        <div class="form-group">
                            <input type="hidden" name="token"
                                value="<?php echo htmlspecialchars($_GET['hash'] ?? ''); ?>" />
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                            <label for="password" class="mb-3 text-start d-block">New Password</label>
                            <div style="position:relative" id="reset-password-eye-password-0">
                                <input id="new-password" type="password" name="password" class="form-control" required
                                    autofocus style="padding-right: 60px;" />
                                <div class="invalid-feedback">Password is required</div>
                                <div class="btn btn-sm" id="reset-password-passeye-toggle-0"
                                    style="position: absolute; right: 10px; top: 7px; padding: 2px 7px; font-size: 16px; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                            <label for="confirm-password" class="mb-3 text-start d-block">Confirm Password</label>
                            <div style="position:relative" id="reset-password-eye-password-1">
                                <input id="confirm-password" type="password" name="confirm_password"
                                    class="form-control" required style="padding-right: 60px;" />
                                <div class="invalid-feedback">Confirm Password is required</div>
                                <div class="btn btn-sm" id="reset-password-passeye-toggle-1"
                                    style="position: absolute; right: 10px; top: 7px; padding: 2px 7px; font-size: 16px; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="g-recaptcha mb-3" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/resetpassword.js"></script>
</body>

</html>