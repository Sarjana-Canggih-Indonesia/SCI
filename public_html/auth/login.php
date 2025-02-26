<?php
// login.php

require_once __DIR__ . '/../../config/config.php'; // Include configuration file
require_once __DIR__ . '/../../config/user_actions_config.php'; // Include user actions configuration file

startSession(); // Start the session and generate a CSRF token

// Retrieve error from session (if any)
$error_message = '';
if (isset($_SESSION['error_message'])) {
  $error_message = $_SESSION['error_message'];
  unset($_SESSION['error_message']); // Remove after displaying
}

$config = getEnvironmentConfig(); // Load environment configuration
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']); // Get the base URL from the configuration
$isLive = $config['is_live'];

// Set no-cache headers for local environment
setCacheHeaders($isLive);

$user_input = $_GET['input'] ?? ''; // Get user input from the query string
$sanitized_input = sanitize_input($user_input); // Sanitize the user input to prevent XSS

// Perform auto-login if applicable
autoLogin($config, $env);

// Validate reCAPTCHA environment variables
validateReCaptchaEnvVariables();

// Redirect to the index page if the user is already logged in
redirect_if_logged_in();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Sarjana Canggih Indonesia</title>
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
  <!-- Custom Inline CSS -->
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
      <!-- Brand / Logo Section  -->
      <div class="brand"> <a href="<?php echo $baseUrl; ?>">
          <img src="<?php echo $baseUrl; ?>assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia">
        </a>
      </div>
      <div class="card fat">
        <div class="card-body">
          <h4 class="card-title">Masuk</h4>
          <!-- Menampilkan Pesan Kesalahan -->
          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
              <?php echo $error_message; ?>
            </div>
          <?php endif; ?>
          <!-- Bagian Form -->
          <form action="<?php echo $baseUrl; ?>process_login" method="POST" class="my-login-validation">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <!-- Input Username -->
            <div class="form-group">
              <label for="username">Username atau Email <span style="color: red">*</span></label>
              <input id="username" type="text" class="form-control" name="username"
                value="<?php echo isset($_COOKIE['username']) ? $_COOKIE['username'] : ''; ?>" required autofocus>
              <div class="invalid-feedback">Username atau Email diperlukan</div>
            </div>

            <!-- Pesan error -->
            <?php if (!empty($error_message)): ?>
              <div class="alert alert-danger">
                <?php echo $error_message; ?>
              </div>
            <?php endif; ?>
            <!-- Input Password -->
            <div class="form-group">
              <div class="d-flex justify-content-between">
                <label for="login_+password" class="form-label">Password <span style="color: red">*</span></label>
                <a href="forgot_password" class="text-end text-decoration-none">Lupa Password?</a>
              </div>
              <div style="position:relative" id="posisi-login-password-0">
                <input id="password" type="password" class="form-control" name="password" required
                  style="padding-right: 60px;">
                <div class="invalid-feedback">Password is required</div>
                <div class="btn btn-sm" id="toggle-login-password-0"
                  style="position: absolute; right: 10px; top: 7px; padding: 2px 7px; font-size: 16px; cursor: pointer;">
                  <i class="fas fa-eye"></i>
                </div>
              </div>
            </div>
            <!-- Remember Me Checkbox -->
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe" <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="rememberMe">Remember Me</label>
            </div>
            <br>
            <!-- Honeypot Field -->
            <input type="text" name="honeypot" class="honeypot" style="display: none;">
            <!-- reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>" required>
            </div>
            <br>
            <!-- Submit Button -->
            <div class="form-group m-0">
              <button type="submit" class="btn btn-primary btn-lg w-100">
                Masuk
              </button>
            </div>
            <!-- Link Registrasi -->
            <div class="mt-4 text-center"> Belum punya akun? <a href="<?php echo $baseUrl; ?>register">Buat
                Akun</a>
            </div>
          </form>
          <!-- Akhir Bagian Form -->
        </div>
      </div>
    </div>
  </section>
  <!-- External JS libraries -->
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS scripts -->
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/login.js"></script>
</body>

</html>