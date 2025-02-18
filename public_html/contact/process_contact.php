<?php

// Include necessary libraries and configuration files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Dotenv\Dotenv;

// Load environment variables from the .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Start session to manage user sessions
startSession();

// Validate the CSRF token to protect against cross-site request forgery
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token."); // Terminate if CSRF token is invalid
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.'); // Terminate if request method is not POST
}

// Initialize HTTP client for reCAPTCHA and CSRF validation
$client = HttpClient::create();
$validationResult = validateCsrfAndRecaptcha($_POST, $client);

// If validation fails, stop execution and display error message
if ($validationResult !== true) {
    die($validationResult); // Display error and stop further execution
}

// Check for a honeypot field to detect spam bots
if (!empty($_POST['form-wa-honeypot'])) {
    die("Spam detected via honeypot."); // Stop execution if honeypot field is filled
}

// Sanitize the user input for the name, email, and message fields to prevent injection attacks
$nama = sanitize_input($_POST['form-wa-nama']);
$email = sanitize_input($_POST['form-wa-email']);
$pesan = sanitize_input($_POST['form-wa-pesan']);

// Retrieve the phone number from environment variables
$contact = $_ENV['PHONE_NUMBER'];

// Construct the message to be sent to WhatsApp
$message = "Nama: $nama\nEmail: $email\nPesan: $pesan";
// URL encode the message to make it safe for use in a URL
$encodedMessage = urlencode($message);

// Create the WhatsApp URL with the pre-filled message
$link = "https://api.whatsapp.com/send?phone=$contact&text=$encodedMessage";

// Clear any existing output buffer if necessary
if (ob_get_length() > 0) {
    ob_clean(); // Clean output buffer to avoid unwanted content in the response
}

// Redirect the user to the WhatsApp link for sending the message
header("Location: $link");

// Exit the script to ensure no further code is executed
exit();
