<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * This function performs the task of loading environment variables from a .env file.
 * 1. Checks if the .env file has been loaded previously.
 * 2. If not loaded, attempts to load the .env file and set environment variables.
 * 3. If successful, marks the .env file as loaded to avoid reloading in future requests.
 */
$rootDir = __DIR__ . '/../';
$dotenvFile = $rootDir . '.env';

// Step 1: Check if the .env file has already been loaded
if (getenv('ENV_LOADED')) {
    error_log('.env file already loaded, skipping...');
} else {
    // Step 2: Load the .env file if not loaded
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir);

    if (!file_exists($dotenvFile) || !$dotenv->load()) {
        $errorMessage = '.env file not found or failed to load';
        error_log($errorMessage);
        exit;
    } else {
        // Step 3: Mark that the .env file is loaded by setting ENV_LOADED environment variable
        putenv('ENV_LOADED=true');
        $successMessage = '.env file loaded successfully';
        error_log($successMessage);
    }
}

use voku\helper\AntiXSS;
$antiXSS = new AntiXSS();
use Jenssegers\Optimus\Optimus;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;

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
 * Retrieves the environment-specific configuration settings.
 * 
 * This function performs this task:
 * 1. Checks if the current environment is 'local' or 'live' based on the HTTP host.
 * 2. Returns the corresponding configuration settings for database connection, recaptcha keys, and mail configuration.
 *
 * @return array The configuration settings for the current environment.
 */
function getEnvironmentConfig()
{
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'; // Step 1

    return [
        'local' => [ // Step 2
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
        'live' => [ // Step 3
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
    ][$env]; // Step 4
}

/**
 * Retrieves the appropriate base URL based on the environment.
 * 
 * This function performs this task:
 * 1. Checks if the provided base URL matches the live URL.
 * 2. If they match, returns the base URL as is; if not, appends 'public/' to the base URL for local environments.
 *
 * @param array $config The configuration array containing the 'BASE_URL' key.
 * @param string $liveUrl The live URL to compare with the 'BASE_URL'.
 * 
 * @return string The appropriate base URL, either the live URL or the local URL with 'public/' appended.
 */
function getBaseUrl($config, $liveUrl)
{
    return ($config['BASE_URL'] === $liveUrl) ? $config['BASE_URL'] : $config['BASE_URL'] . 'public/'; // Step 1
}

/**
 * Handles error logging and script termination based on the environment.
 * 
 * This function performs this task:
 * 1. In the local environment, it terminates the script and displays the error message using Whoops.
 * 2. In the live environment, it logs the error message for further investigation.
 *
 * @param string $message The error message to be logged or displayed.
 * @param string $env The current environment ('local' or 'live').
 */
function handleError($message, $env)
{
    if ($env === 'local') { // Step 1
        $whoops = new Whoops\Run; // Step 2
        $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler); // Step 3
        $whoops->register(); // Step 4

        throw new Exception($message); // Step 5
    } else { // Step 6
        error_log($message); // Step 7
    }
}

/**
 * Validates the environment variables for reCAPTCHA and defines constants if missing.
 *
 * This function checks if the reCAPTCHA site key and secret key are defined as constants.
 * If they are not already defined, it checks the environment variables and defines the constants.
 * If the variables are missing, it triggers an error.
 */
function validateReCaptchaEnvVariables()
{
    if (!defined('RECAPTCHA_SITE_KEY') && !defined('RECAPTCHA_SECRET_KEY')) {
        $recaptchaSiteKey = $_ENV['RECAPTCHA_SITE_KEY'] ?? null;
        $recaptchaSecretKey = $_ENV['RECAPTCHA_SECRET_KEY'] ?? null;

        if (!$recaptchaSiteKey || !$recaptchaSecretKey) {
            handleError('reCAPTCHA environment variables are missing or incomplete.', ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live');
        }

        define('RECAPTCHA_SITE_KEY', $recaptchaSiteKey);
        define('RECAPTCHA_SECRET_KEY', $recaptchaSecretKey);

        $successMessage = 'reCAPTCHA environment variables validated and constants defined successfully.';
        error_log($successMessage);
    } else {
        $message = 'reCAPTCHA constants are already defined.';
        error_log($message);
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
 * This function checks if the provided CSRF token is valid and verifies 
 * the reCAPTCHA response by sending a request to the Google reCAPTCHA API. 
 * If either validation fails, an error message is logged or displayed 
 * depending on the environment.
 *
 * @param array $data The data containing the CSRF token and reCAPTCHA response.
 * @param HttpClientInterface $client The HTTP client used to send the reCAPTCHA verification request.
 * @return mixed Returns true if both CSRF and reCAPTCHA validation succeed, or an empty string if any validation fails.
 */
function validateCsrfAndRecaptcha($data, HttpClientInterface $client)
{
    $config = getEnvironmentConfig();
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    validateReCaptchaEnvVariables();

    $receivedCsrfToken = $data['csrf_token'] ?? null;
    if (!validateCsrfToken($receivedCsrfToken)) {
        handleError('Invalid CSRF token. Please try again.', $env);
        return '';
    }

    $recaptchaSecret = RECAPTCHA_SECRET_KEY;
    $recaptchaResponse = $data['g-recaptcha-response'] ?? '';

    if (empty($recaptchaResponse)) {
        handleError('Please verify you are not a robot by completing the reCAPTCHA.', $env);
        return '';
    }

    $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse
        ],
    ]);

    $data = $response->toArray();

    if (empty($data['success']) || $data['success'] !== true) {
        handleError('Invalid reCAPTCHA. Please try again.', $env);
        return '';
    }

    return true;
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