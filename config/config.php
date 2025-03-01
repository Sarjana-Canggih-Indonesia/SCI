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

date_default_timezone_set('Asia/Jakarta');

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
    return ($config['BASE_URL'] === $liveUrl) ? $config['BASE_URL'] : $config['BASE_URL'] . 'public_html/';
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
            'is_live' => false,
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
            'is_live' => true,
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
 * Checks if the current environment is live (production).
 *
 * @return bool Returns `true` if the environment is live (production), otherwise `false`.
 */
function isLive()
{
    return ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1');
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
 * Validate the CSRF token to prevent Cross-Site Request Forgery (CSRF) attacks.
 *
 * This function checks if the provided CSRF token matches the one stored in the session.
 * If the token is missing, it returns a 400 Bad Request HTTP response.
 * If the token is invalid, it returns a 403 Forbidden HTTP response.
 * The function ensures that session handling is properly initialized before validation.
 *
 * @param string $token The CSRF token submitted via the request.
 * @return bool Returns true if the token is valid; otherwise, execution halts with an HTTP response.
 */
function validateCSRFToken(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start(); // Start the session if it has not been started
    }

    if (!isset($_SESSION['csrf_token'])) {
        http_response_code(400); // Send HTTP 400 Bad Request response
        exit('400 Bad Request: CSRF token is missing.'); // Stop script execution
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403); // Send HTTP 403 Forbidden response
        exit('403 Forbidden: Invalid CSRF token.'); // Stop script execution
    }

    return true; // CSRF validation successful
}

/**
 * Validates both CSRF token and Google reCAPTCHA v2 response.
 *
 * This function ensures that the request is secure by verifying:
 * - CSRF token validity to prevent cross-site request forgery.
 * - Google reCAPTCHA v2 response to mitigate automated bot submissions.
 *
 * @param array $data The submitted form data, containing:
 *                    - `csrf_token` (string): The CSRF token for validation.
 *                    - `g-recaptcha-response` (string): The reCAPTCHA v2 response.
 * @param HttpClientInterface $client An HTTP client used to communicate with Google’s reCAPTCHA API.
 * 
 * @return bool|string Returns true if both validations pass. Returns an empty string on failure.
 * 
 * @throws Exception If in a local environment and an error occurs, an exception is thrown.
 *                   In a production environment, errors fail silently.
 */
function validateCsrfAndRecaptcha($data, HttpClientInterface $client)
{
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'; // Determine environment (local or live)
    validateReCaptchaEnvVariables(); // Ensure reCAPTCHA environment variables are set

    if (!validateCsrfToken($data['csrf_token'] ?? '')) { // Validate CSRF token
        handleError('Invalid CSRF token.', $env);
        return '';
    }

    $recaptchaResponse = $data['g-recaptcha-response'] ?? ''; // Get reCAPTCHA response
    if (empty($recaptchaResponse)) { // Check if reCAPTCHA is filled
        handleError('Please complete the reCAPTCHA.', $env);
        return '';
    }

    // Send a request to Google reCAPTCHA API for verification
    $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => ['secret' => RECAPTCHA_SECRET_KEY, 'response' => $recaptchaResponse],
    ]);

    $result = $response->toArray(); // Convert response to an array
    if (!($result['success'] ?? false)) { // Check if reCAPTCHA validation was successful
        handleError('reCAPTCHA verification failed.', $env);
        return '';
    }

    return true; // Return true if both CSRF and reCAPTCHA validations pass
}

/**
 * Sanitizes user input to prevent XSS (Cross-Site Scripting) attacks.
 *
 * This function utilizes the `AntiXSS` library from voku to filter out 
 * potentially harmful characters and scripts from user-supplied input.
 *
 * @param string $input The raw user input that needs to be sanitized.
 *                      - This can be data from forms, URLs, or any external sources.
 * 
 * @return string The sanitized input string, free from harmful scripts or tags.
 */
function sanitize_input($input)
{
    $xss = new voku\helper\AntiXSS(); // Initialize AntiXSS library for filtering
    return $xss->xss_clean($input); // Perform XSS sanitization and return cleaned input
}

/**
 * Escapes special HTML characters to prevent XSS (Cross-Site Scripting) attacks.
 *
 * This function converts special characters into their corresponding HTML entities, 
 * ensuring that user-supplied input does not execute unintended JavaScript or HTML code.
 *
 * @param string $data The input string that needs to be sanitized for safe HTML output.
 * @return string The escaped string with special HTML characters converted to entities.
 */
function escapeHTML(string $data): string
{
    // Convert special characters to HTML entities to prevent XSS attacks
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sets cache-related headers for the response.
 *
 * This function configures the `Cache-Control` and `Expires` headers based on 
 * whether the page is in a live environment or not.
 *
 * @param bool $isLive Indicates whether the page is in a live environment.
 *                     - If true, enables caching for 1 hour.
 *                     - If false, disables caching.
 * 
 * @return void
 */
function setCacheHeaders(bool $isLive): void
{
    header('Cache-Control: ' . ($isLive
        ? 'public, max-age=3600, must-revalidate' // Enable caching for 1 hour in live mode
        : 'no-cache, must-revalidate')); // Disable caching in non-live mode

    header('Expires: ' . ($isLive
        ? Carbon::now()->addHour()->toRfc7231String() // Set expiration time 1 hour ahead in live mode
        : Carbon::now()->subYear()->toRfc7231String())); // Set expiration time to a year ago in non-live mode
}

/**
 * Updates cache headers on redirect to ensure the latest changes are visible.
 *
 * If the $forceNoCache flag is true, the function sets no-cache headers,
 * otherwise it calls the setCacheHeaders() function based on the $isLive flag.
 *
 * @param bool $forceNoCache Flag indicating whether to force no-cache headers.
 * @param bool $isLive       Flag indicating whether the environment is live.
 */
function updateCacheHeadersOnRedirect($forceNoCache, $isLive)
{
    if ($forceNoCache) {
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
    } else {
        setCacheHeaders($isLive);
    }
}

/**
 * Retrieves flash messages from the session and clears them afterward.
 *
 * This function checks for the presence of flash messages in the session, returning an
 * associative array with keys 'success', 'error', and 'forceNoCache'. If a flash message is
 * found, it sets 'forceNoCache' to true and retrieves either a success or error message accordingly.
 * After processing, it removes the flash message data from the session.
 *
 * @return array{
 *     success: string,
 *     error: string,
 *     forceNoCache: bool
 * } An array containing flash messages and the no-cache flag.
 */
function getFlashMessages()
{
    $flash = [
        'success' => '',
        'error' => '',
        'forceNoCache' => false
    ];

    if (isset($_SESSION['form_success'])) {
        $flash['forceNoCache'] = true;
        if ($_SESSION['form_success']) {
            $flash['success'] = $_SESSION['success_message'] ?? '';
        } else {
            $flash['error'] = $_SESSION['error_message'] ?? '';
        }
        unset($_SESSION['form_success'], $_SESSION['success_message'], $_SESSION['error_message']);
    }
    return $flash;
}

/**
 * Processes flash messages and updates cache headers on redirect.
 *
 * Retrieves flash messages from the session and then updates the cache headers
 * based on the 'forceNoCache' flag and the live environment setting.
 *
 * @param bool $isLive Flag indicating whether the environment is live.
 * @return array The flash messages retrieved from the session.
 */
function processFlashMessagesAndHeaders($isLive)
{
    $flash = getFlashMessages();
    updateCacheHeadersOnRedirect($flash['forceNoCache'], $isLive);
    return $flash;
}

/**
 * Configures and sends HTTP response headers for API requests.
 *
 * This function sets the appropriate headers for API responses, including
 * content type, CORS (Cross-Origin Resource Sharing) policies, and allowed 
 * HTTP methods and headers.
 *
 * @param string $contentType The content type of the response. Defaults to 'application/json'.
 * @return void
 */
function configureApiHeaders($contentType = 'application/json')
{
    header("Content-Type: $contentType"); // Set the Content-Type header for the API response

    // Determine the allowed origin based on the environment (local or production)
    $allowedOrigin = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'http://localhost/SCI/' : 'https://sarjanacanggihindonesia.com';

    header("Access-Control-Allow-Origin: $allowedOrigin"); // Set allowed origin for CORS
    header("Access-Control-Allow-Credentials: true"); // Allow credentials (cookies, authentication headers)
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // Specify allowed HTTP methods
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN"); // Specify allowed request headers
}