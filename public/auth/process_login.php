<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/auth/validate.php';

use Symfony\Component\HttpClient\HttpClient;

// Start the session and generate a CSRF token
startSession();

// Load environment configuration
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);

// Sanitize user input
$user_input = $_GET['input'] ?? '';
$sanitized_input = sanitize_input($user_input);

// Validate reCAPTCHA environment variables
validateReCaptchaEnvVariables();

// Check if honeypot is filled
if (!empty($_POST['honeypot'])) {
    $error_message = 'Bot detected. Submission rejected.';
    handleError($error_message, $env); // Use handleError for error handling
} else {
    // Validate CSRF token and reCAPTCHA response
    $client = HttpClient::create();
    $error_message = validateCsrfAndRecaptcha($_POST, $client);

    if ($error_message === true) {
        // Process login after CSRF and reCAPTCHA validation
        $username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
        $password = isset($_POST['password']) ? sanitize_input($_POST['password']) : '';

        // Validate username
        $usernameViolations = validateUsername($username);
        if (count($usernameViolations) > 0) {
            // If there are validation errors for username
            $error_message = 'Username tidak sesuai atau tidak ditemukan.';
            handleError($error_message, $env); // Handle validation errors
            exit();
        }

        // Validate password
        $passwordViolations = validatePassword($password);
        if (count($passwordViolations) > 0) {
            // If there are validation errors for password
            $error_message = 'Password tidak sesuai atau tidak ditemukan.';
            handleError($error_message, $env); // Handle validation errors
            exit();
        }

        // Process login
        $error_message = processLogin($username, $password);

        if ($error_message === 'Login successful.') {
            // Process rememberMe if checked
            if (isset($_POST['rememberMe'])) {
                rememberMe($username, $password);
            }

            // Redirect to home page
            header("Location: $baseUrl");
            exit();
        } else {
            // If login fails, show the same generic error message
            $error_message = 'Username / Password tidak sesuai atau tidak ditemukan. Silahkan coba lagi atau lakukan reset password.';
            handleError($error_message, $env); // Handle login error handling
        }
    } else {
        handleError($error_message, $env); // Use handleError for CSRF/reCAPTCHA error handling
    }
}