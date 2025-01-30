<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Load environment variables from a .env file.
 * This script checks if the .env file has already been loaded. If not, it attempts to load the .env file
 * and set the environment variables. If successful, it marks the .env file as loaded to avoid reloading
 * in future requests.
 */
$rootDir = __DIR__ . '/../';
$dotenvFile = $rootDir . '.env';

if (getenv('ENV_LOADED')) {
    error_log('.env file already loaded, skipping...');
} else {
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
    if (!file_exists($dotenvFile) || !$dotenv->load()) {
        error_log('.env file not found or failed to load');
        exit;
    } else {
        putenv('ENV_LOADED=true');
        error_log('.env file loaded successfully');
    }
}

use voku\helper\AntiXSS;
$antiXSS = new AntiXSS();
use Jenssegers\Optimus\Optimus;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Carbon\Carbon;

date_default_timezone_set('Asia/Jakarta'); // Set zona waktu

$whoops = new Run;
$whoops->pushHandler(new PrettyPageHandler);
$whoops->register();

/**
 * Initialize Optimus encryption instance with keys from environment variables.
 */
$prime = $_ENV['OPTIMUS_PRIME'];
$inverse = $_ENV['OPTIMUS_INVERSE'];
$random = $_ENV['OPTIMUS_RANDOM'];

/**
 * Initialize Optimus encryption with the environment keys.
 */
$optimus = new Optimus($prime, $inverse, $random);

/**
 * Retrieves the appropriate base URL based on the environment.
 *
 * @param array $config Configuration array containing 'BASE_URL'.
 * @param string $liveUrl The live URL to compare with 'BASE_URL'.
 * @return string The appropriate base URL.
 */
function getBaseUrl($config, $liveUrl)
{
    // If the base URL matches the live URL, return it as is; otherwise, append 'public/' for local environments
    return ($config['BASE_URL'] === $liveUrl) ? $config['BASE_URL'] : $config['BASE_URL'] . 'public/';
}

/**
 * Retrieves the environment-specific configuration settings.
 *
 * @return array The configuration settings for the current environment.
 */
function getEnvironmentConfig()
{
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';
    return [
        'local' => [
            'BASE_URL' => $_ENV['LOCAL_URL'],
            'DB_HOST' => $_ENV['DB_HOST'],
            'DB_USER' => $_ENV['DB_USER'],
            'DB_PASS' => $_ENV['DB_PASS'],
            'DB_NAME' => $_ENV['DB_NAME'],
            'RECAPTCHA_SITE_KEY' => $_ENV['RECAPTCHA_SITE_KEY'],
            'RECAPTCHA_SECRET_KEY' => $_ENV['RECAPTCHA_SECRET_KEY'],
            'MAIL_HOST' => $_ENV['MAIL_HOST'],
            'MAIL_USERNAME' => $_ENV['MAIL_USERNAME'],
            'MAIL_PASSWORD' => $_ENV['MAIL_PASSWORD'],
            'MAIL_PORT' => $_ENV['MAIL_PORT'],
            'MAIL_ENCRYPTION' => $_ENV['MAIL_ENCRYPTION'],
        ],
        'live' => [
            'BASE_URL' => $_ENV['LIVE_URL'],
            'DB_HOST' => $_ENV['LIVE_DB_HOST'],
            'DB_USER' => $_ENV['LIVE_DB_USER'],
            'DB_PASS' => $_ENV['LIVE_DB_PASS'],
            'DB_NAME' => $_ENV['LIVE_DB_NAME'],
            'RECAPTCHA_SITE_KEY' => $_ENV['LIVE_RECAPTCHA_SITE_KEY'],
            'RECAPTCHA_SECRET_KEY' => $_ENV['LIVE_RECAPTCHA_SECRET_KEY'],
            'MAIL_HOST' => $_ENV['LIVE_MAIL_HOST'],
            'MAIL_USERNAME' => $_ENV['LIVE_MAIL_USERNAME'],
            'MAIL_PASSWORD' => $_ENV['LIVE_MAIL_PASSWORD'],
            'MAIL_PORT' => $_ENV['LIVE_MAIL_PORT'],
            'MAIL_ENCRYPTION' => $_ENV['LIVE_MAIL_ENCRYPTION'],
        ]
    ][$env];
}

function isLiveEnvironment()
{
    return ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1');
}

/**
 * Handles error logging and script termination based on the environment.
 *
 * @param string $message The error message to be logged or displayed.
 * @param string $env The current environment ('local' or 'live').
 */
function handleError($message, $env)
{
    if ($env === 'local') {
        // Initialize Whoops error handler for local environment
        $whoops = new Whoops\Run;
        $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
        $whoops->register();
        throw new Exception($message); // Throw an exception to stop execution and display error
    } else {
        error_log($message); // Log the error for later review in live environment
    }
}

/**
 * Validates and defines reCAPTCHA environment variables as constants if not already set.
 */
function validateReCaptchaEnvVariables()
{
    if (!defined('RECAPTCHA_SITE_KEY') && !defined('RECAPTCHA_SECRET_KEY')) {
        // Deteksi environment terlebih dahulu
        $environment = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';
        $prefix = ($environment === 'live') ? 'LIVE_' : '';

        // Ambil key dengan prefix yang sesuai
        $recaptchaSiteKey = $_ENV[$prefix . 'RECAPTCHA_SITE_KEY'] ?? null;
        $recaptchaSecretKey = $_ENV[$prefix . 'RECAPTCHA_SECRET_KEY'] ?? null;

        if (!$recaptchaSiteKey || !$recaptchaSecretKey) {
            handleError(
                'reCAPTCHA environment variables are missing or incomplete. Environment: ' . $environment,
                $environment
            );
        }

        define('RECAPTCHA_SITE_KEY', $recaptchaSiteKey);
        define('RECAPTCHA_SECRET_KEY', $recaptchaSecretKey);

        error_log('reCAPTCHA variables loaded for ' . $environment . ' environment');
    } else {
        error_log('reCAPTCHA constants already defined');
    }
}

/**
 * Validate the CSRF token by comparing the session token with the received token.
 *
 * @param string $token The CSRF token to validate.
 * @return bool True if the token is valid, false otherwise.
 */
function validateCsrfToken($token)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
        return true;
    }
    return false;
}

/**
 * Validates CSRF token and reCAPTCHA response.
 *
 * This function checks if the provided CSRF token is valid and verifies the reCAPTCHA response 
 * by sending a request to the Google reCAPTCHA API. If either validation fails, an error message 
 * is logged or displayed depending on the environment.
 *
 * @param array $data Contains the CSRF token and reCAPTCHA response.
 * @param HttpClientInterface $client HTTP client for sending the reCAPTCHA verification request.
 * @return mixed Returns true if both CSRF and reCAPTCHA validation succeed, or an empty string if any validation fails.
 */
function validateCsrfAndRecaptcha($data, HttpClientInterface $client)
{
    $config = getEnvironmentConfig(); // Retrieve environment configuration
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'; // Determine environment type

    validateReCaptchaEnvVariables(); // Ensure reCAPTCHA environment variables are set

    $receivedCsrfToken = $data['csrf_token'] ?? null; // Extract CSRF token from request
    if (!validateCsrfToken($receivedCsrfToken)) { // Validate CSRF token
        handleError('Invalid CSRF token. Please try again.', $env);
        return '';
    }

    $recaptchaSecret = RECAPTCHA_SECRET_KEY; // Get reCAPTCHA secret key
    $recaptchaResponse = $data['g-recaptcha-response'] ?? ''; // Extract reCAPTCHA response

    if (empty($recaptchaResponse)) { // Ensure reCAPTCHA response is provided
        handleError('Please verify you are not a robot by completing the reCAPTCHA.', $env);
        return '';
    }

    // Send reCAPTCHA verification request to Google's API
    $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => ['secret' => $recaptchaSecret, 'response' => $recaptchaResponse],
    ]);

    $data = $response->toArray(); // Convert response to an array

    if (empty($data['success']) || $data['success'] !== true) { // Check if reCAPTCHA verification succeeded
        handleError('Invalid reCAPTCHA. Please try again.', $env);
        return '';
    }

    return true; // Return success if both CSRF and reCAPTCHA validation pass
}

/**
 * Sanitize input to prevent XSS attacks by cleaning harmful characters.
 *
 * @param string $input The input to sanitize.
 * @return string The sanitized input.
 */
function sanitize_input($input)
{
    $xss = new voku\helper\AntiXSS();
    return $xss->xss_clean($input);
}