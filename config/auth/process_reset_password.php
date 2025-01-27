<?php
// Start the session if not already started
session_start();

// Assuming you have some way to get the message
$message = ''; // Default message

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $token = $_POST['token'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate the CSRF token
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $message = "Invalid CSRF token. Please try again.";
    }

    // Proceed if the CSRF token is valid
    if (empty($message)) {
        // Your password reset logic goes here (e.g., hash the password, update database, etc.)

        // Assuming password reset is successful
        $message = 'Your password has been reset successfully. You can now log in with the new password.';

        // Redirect back to reset_password.php with success message
        header("Location: reset_password.php?message=" . urlencode($message) . "&token=" . urlencode($token));
        exit;
    }
}

// If something goes wrong, we can redirect back with an error message
header("Location: ../../public/auth/reset_password.php?message=" . urlencode($message) . "&token=" . urlencode($token));
exit;
