<?php
/**
 * Handles the processing of the contact form submission, including validation and redirection to WhatsApp.
 *
 * This script performs the following tasks:
 * - Validates the request method
 * - Validates the CSRF token and reCAPTCHA
 * - Validates honeypot field for bot detection
 * - Sanitizes and processes form input
 * - Redirects the user to WhatsApp with a pre-filled message
 *
 * @package ContactForm
 */

// Load required files and libraries
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Dotenv\Dotenv;

/**
 * Load environment variables from the .env file.
 */
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

/**
 * Start session and initialize CSRF token.
 */
startSession();

/**
 * Ensure the form is submitted via POST method.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

/**
 * Validate CSRF token and reCAPTCHA.
 *
 * @param array $postData The form data from POST request.
 * @param HttpClientInterface $client The HTTP client instance for validation.
 * @return bool|string Returns true if validation succeeds, otherwise returns an error message.
 */
$client = HttpClient::create();
$validationResult = validateCsrfAndRecaptcha($_POST, $client);
if ($validationResult !== true) {
    die($validationResult);
}

/**
 * Detect spam submission using honeypot field.
 */
if (!empty($_POST['form-wa-honeypot'])) {
    die("Spam detected via honeypot.");
}

/**
 * Sanitize and clean the form input values.
 *
 * @param string $input The raw form input.
 * @return string The sanitized form input.
 */
$nama = sanitize_input($_POST['form-wa-nama']);
$email = sanitize_input($_POST['form-wa-email']);
$pesan = sanitize_input($_POST['form-wa-pesan']);

/**
 * Retrieve the WhatsApp contact number from the environment variables.
 *
 * @var string
 */
$contact = $_ENV['PHONE_NUMBER'];

/**
 * Prepare the message to be sent to WhatsApp.
 *
 * @var string
 */
$message = "Nama: $nama\nEmail: $email\nPesan: $pesan";
$encodedMessage = urlencode($message);

/**
 * Generate the WhatsApp link to redirect the user.
 *
 * @var string
 */
$link = "https://api.whatsapp.com/send?phone=$contact&text=$encodedMessage";

/**
 * Redirect the user to the WhatsApp link.
 */
header("Location: $link");
exit();
