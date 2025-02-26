<?php
// activate.php

// Memuat config.php dan user_actions_config.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
use voku\helper\AntiXSS;

// Start the session and generate a CSRF token
startSession();

// Load variable
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect to the index page if the user is already logged in
redirect_if_logged_in();

$message = '';

// Establish database connection using the getPDOConnection function from user_actions_config.php
$pdo = getPDOConnection($config, $env);

// Activate account if activation code and user ID are provided
if (isset($_GET['code'])) {
    $activationCode = $_GET['code'];

    // Call the activateAccount function with the activation code
    $message = activateAccount($activationCode, $env, $config);
} else {
    $message = 'Invalid request: activation code missing.';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun - Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>favicon.ico" />
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <!-- Custom Styles CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css">
</head>

<body class="login-page">
    <section class="h-100 d-flex justify-content-center align-items-center">
        <div class="card-wrapper">
            <div class="brand">
                <a href="<?php echo $baseUrl; ?>">
                    <img src="<?php echo $baseUrl; ?>assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia"
                        srcset="">
            </div>
        </div>
    </section>

    <section class="activate-message">
        <div class="container mt-5">
            <h2>Aktivasi Akun</h2>
            <div class="alert alert-info"><?php echo $message; ?></div>
        </div>
    </section>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
</body>

</html>