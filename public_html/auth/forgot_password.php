<?php
// forgot_password.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;

// Start the session and generate a CSRF token
startSession();

// Load environment configuration
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];
// Deteksi environment
$isLiveEnvironment = ($config['BASE_URL'] === $_ENV['LIVE_URL']);
setCacheHeaders($isLive); // Set header no cache saat local environment

// Sanitize user input
$user_input = $_GET['input'] ?? '';
$sanitized_input = sanitize_input($user_input);

// Validate reCAPTCHA environment variables
validateReCaptchaEnvVariables();

// Redirect to the index page if the user is already logged in
redirect_if_logged_in();

// Initialize HttpClient
$httpClient = HttpClient::create();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_username = sanitize_input($_POST['email_or_username'] ?? '');
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Process the password reset request
    $result = processPasswordResetRequest($email_or_username, $recaptcha_response, $csrf_token, $httpClient, $config, $baseUrl);

    // Store the result message in a JavaScript variable
    echo '<script type="text/javascript">';
    echo 'var resultStatus = "' . $result['status'] . '";';
    echo 'var resultMessage = "' . $result['message'] . '";';
    echo '</script>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sarjana Canggih</title>
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
                    <!-- Kembali ke halaman login  -->
                    <div class="d-flex text-start mb-2">
                        <a href="<?php echo $baseUrl; ?>login" class="btn btn-outline-primary"
                            onclick="return confirm('Are you sure you want to go back?');">
                            <i class="fa fa-arrow-left"></i> Back to Login</a>
                    </div>
                    <h4 class="text-start">Lupa Password</h4>
                    <form action="forgot_password.php" method="POST" class="my-forgot-password-validation">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <div class="mb-3">
                                <label for="email_or_username" class="mb-3 text-start d-block">E-mail Address or
                                    Username</label>
                                <input type="text" name="email_or_username" id="email_or_username"
                                    class="form-control mb-3" required autofocus>
                                <div class="invalid-feedback">Email or Username is not valid</div>
                                <div class="form-text text-muted text-start">
                                    By clicking "Reset Password", we will send an email to reset your password.
                                </div>
                            </div>
                        </div>
                        <div class="g-recaptcha mb-3" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- BAGIAN SCRIPT -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/forgotpassword.js"></script>
</body>

</html>