<?php
// process_contact.php

/**
 * Handles WhatsApp contact form submissions with security checks and validation
 * 
 * 1. Loads configuration and dependencies
 * 2. Performs security validations (CSRF, reCAPTCHA, honeypot)
 * 3. Processes and sanitizes user input
 * 4. Generates WhatsApp API link
 * 5. Redirects user to WhatsApp
 */

// [1] Load core configuration and dependencies (already includes Dotenv, HttpClient, etc.)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

use Symfony\Component\HttpClient\HttpClient;

// [2] Session management for CSRF and flash messages
startSession();

// [3] Request method validation - MUST be first validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    handleError('Invalid request method', isLive() ? 'live' : 'local');
}

// [4] CSRF token validation - prevent cross-site request forgery
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    handleError('Invalid CSRF token', isLive() ? 'live' : 'local');
}

// [5] Combined CSRF + reCAPTCHA validation using Symfony HttpClient
try {
    $client = HttpClient::create();
    $recaptchaValidation = validateCsrfAndRecaptcha($_POST, $client);

    if ($recaptchaValidation !== true) {
        handleError($recaptchaValidation, isLive() ? 'live' : 'local');
    }
} catch (\Exception $e) {
    handleError('Security validation failed: ' . $e->getMessage(), isLive() ? 'live' : 'local');
}

// [6] Honeypot detection - anti-spam measure
if (!empty($_POST['form-wa-honeypot'])) {
    handleError('Spam detected via honeypot', isLive() ? 'live' : 'local');
}

// [7] Input sanitization using AntiXSS from config.php
$sanitizedData = [
    'nama' => sanitize_input($_POST['form-wa-nama'] ?? ''),
    'email' => sanitize_input($_POST['form-wa-email'] ?? ''),
    'pesan' => sanitize_input($_POST['form-wa-pesan'] ?? '')
];

// [8] Validate required fields after sanitization
if (empty($sanitizedData['nama']) || empty($sanitizedData['pesan'])) {
    handleError('Name and message are required fields', isLive() ? 'live' : 'local');
}

// [9] Prepare WhatsApp API parameters
$whatsappData = [
    'phone' => $_ENV['PHONE_NUMBER'], // From .env via config.php
    'message' => urlencode("Nama: {$sanitizedData['nama']}\nEmail: {$sanitizedData['email']}\nPesan: {$sanitizedData['pesan']}")
];

// [10] Build WhatsApp deep link
$whatsappUrl = "https://api.whatsapp.com/send?phone={$whatsappData['phone']}&text={$whatsappData['message']}";

// [11] Ensure clean redirect without output buffering issues
if (ob_get_level() > 0) {
    ob_end_clean();
}

// [12] Final redirect to WhatsApp
header("Location: $whatsappUrl");
exit();