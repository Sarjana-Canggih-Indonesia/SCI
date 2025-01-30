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
        $whoops = new Whoops\Run;
        $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
        $whoops->register();
        throw new Exception($message);
    } else {
        error_log($message);
        exit;
    }
}

/**
 * Validates and defines reCAPTCHA environment variables as constants.
 * 
 * Checks for existing constants first. Auto-detects environment based on HTTP_HOST.
 * Uses LIVE_ prefixed env variables in production. Throws detailed errors in local
 * development while logging securely in production.
 * 
 * @throws Exception In local environment if variables are missing
 * @return void
 */
function validateReCaptchaEnvVariables()
{
    $environment = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    if (!defined('RECAPTCHA_SITE_KEY') && !defined('RECAPTCHA_SECRET_KEY')) {
        $prefix = ($environment === 'live') ? 'LIVE_' : '';
        // Get environment variables with appropriate prefix
        $recaptchaSiteKey = $_ENV[$prefix . 'RECAPTCHA_SITE_KEY'] ?? null;
        $recaptchaSecretKey = $_ENV[$prefix . 'RECAPTCHA_SECRET_KEY'] ?? null;

        // Validate both keys exist
        if (!$recaptchaSiteKey || !$recaptchaSecretKey) {
            handleError('reCAPTCHA environment variables are missing or incomplete.', $environment);
        }

        define('RECAPTCHA_SITE_KEY', $recaptchaSiteKey);
        define('RECAPTCHA_SECRET_KEY', $recaptchaSecretKey);

        // Local environment debugging
        if ($environment === 'local') {
            error_log('reCAPTCHA variables loaded for local environment');
        }
    } else {
        // Prevent duplicate definitions
        if ($environment === 'local') {
            error_log('reCAPTCHA constants already defined');
        }
    }
}

/**
 * Validates CSRF token against session storage.
 * 
 * @param string $token Token received from client form submission
 * @return bool True if token matches session storage, false otherwise
 * @throws Exception If session cannot be started
 */
function validateCsrfToken($token)
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}

/**
 * Combined validation for CSRF token and reCAPTCHA v2 verification.
 * 
 * @param array $data Form submission data containing csrf_token and g-recaptcha-response
 * @param HttpClientInterface $client HTTP client for reCAPTCHA API communication
 * @return mixed True on success, empty string on failure with environment-appropriate error handling
 * @throws Exception Detailed error in local environment, silent fail in production
 */
function validateCsrfAndRecaptcha($data, HttpClientInterface $client)
{
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';
    validateReCaptchaEnvVariables();

    // Validasi CSRF token
    if (!validateCsrfToken($data['csrf_token'] ?? '')) {
        handleError('Invalid CSRF token.', $env);
        return '';
    }

    // Validasi reCAPTCHA
    $recaptchaResponse = $data['g-recaptcha-response'] ?? '';
    if (empty($recaptchaResponse)) {
        handleError('Please complete the reCAPTCHA.', $env);
        return '';
    }

    // Kirim permintaan ke Google reCAPTCHA API
    $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => ['secret' => RECAPTCHA_SECRET_KEY, 'response' => $recaptchaResponse],
    ]);

    $result = $response->toArray();
    if (!($result['success'] ?? false)) {
        handleError('reCAPTCHA verification failed.', $env);
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