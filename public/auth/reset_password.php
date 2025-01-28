<?php
// reset_password.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

startSession();

$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);

$user_input = $_GET['input'] ?? '';
$sanitized_input = sanitize_input($user_input);

autoLogin();
validateReCaptchaEnvVariables();
redirect_if_logged_in();

// Ambil token dari URL
$token = $_GET['hash'] ?? '';

// Get PDO connection
$pdo = getPDOConnection();
if (!$pdo) {
    die('Database connection failed.');
}

// Validasi token dan ambil data user
$user = validateResetToken($token, $pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    $new_password = $_POST['password'] ?? '';

    // Validate CSRF token
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $csrf_token) {
        die('CSRF token validation failed.');
    }

    // Validate reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_secret = RECAPTCHA_SECRET_KEY;
    $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha_response";
    $recaptcha_data = json_decode(file_get_contents($recaptcha_url));

    if (!$recaptcha_data->success) {
        die('reCAPTCHA validation failed.');
    }

    // Update password jika token valid
    if ($user) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        updateUserPassword($user['user_id'], $hashed_password, $pdo); // Pass PDO connection to the function
        markTokenAsUsed($token, $pdo); // Pass PDO connection to the function

        // Redirect to login page with success message
        header("Location: login.php?message=Password+reset+successfully.");
        exit();
    } else {
        die('Invalid or expired token.');
    }
}

// Function to validate the reset token
function validateResetToken($token, $pdo)
{
    $sql = "SELECT pr.user_id, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.user_id
            WHERE pr.hash = :hash 
              AND pr.completed = 0 
              AND pr.expires_at > NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hash' => $token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update the user's password
function updateUserPassword($user_id, $hashed_password, $pdo)
{
    $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['password' => $hashed_password, 'user_id' => $user_id]);
}

// Function to mark the token as used
function markTokenAsUsed($token, $pdo)
{
    $sql = "UPDATE password_resets 
            SET completed = 1, completed_at = NOW() 
            WHERE hash = :hash";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hash' => $token]);
}
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

                    <form action="" method="POST">
                        <div class="form-group">
                            <input type="hidden" name="token"
                                value="<?php echo htmlspecialchars($_GET['hash'] ?? ''); ?>" />
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                            <label for="password" class="mb-3 text-start d-block">New Password</label>
                            <div style="position:relative" id="eye-password-0">
                                <input id="new-password" type="password" name="password" class="form-control" required
                                    autofocus style="padding-right: 60px;" />
                                <div class="invalid-feedback">Password is required</div>
                                <div class="btn btn-sm" id="passeye-toggle-0"
                                    style="position: absolute; right: 10px; top: 7px; padding: 2px 7px; font-size: 16px; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>
                        <div class="form-text text-muted text-start mb-3">
                            Pastikan menggunakan password yang kuat dan mudah untuk diingat
                        </div>
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
</body>

</html>