<?php
// user_actions_config.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/validate.php';

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Carbon\Carbon;

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

// Include PHPMailer files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Memuat konfigurasi lingkungan
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

/**
 * Get a PDO connection to the database.
 * This function retrieves environment-specific configuration settings, establishes a connection
 * to a MySQL database using PDO, and sets the error mode to exceptions for better error handling.
 * @return PDO|null Returns a PDO instance for database interaction or null if an error occurs.
 */
function getPDOConnection()
{
    try {
        // Retrieve environment-specific configuration settings
        $config = getEnvironmentConfig();
        // Create a new PDO instance with the database credentials from the configuration
        $pdo = new PDO(
            "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}",
            $config['DB_USER'],
            $config['DB_PASS']
        );
        // Set the error mode to exceptions to catch any potential issues
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Return the PDO instance for further database interaction
        return $pdo;
    } catch (PDOException $e) {
        // Log the error message for debugging purposes
        handleError("Database Error: " . $e->getMessage(), ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live');
        // Inform the user that an error occurred without revealing sensitive details
        echo 'Database Error: An error occurred. Please try again later.';
        // Return null if the connection fails
        return null;
    }
}

/**
 * Get a configured instance of PHPMailer.
 * Initializes and configures PHPMailer using SMTP settings from environment variables.
 * Returns the configured PHPMailer instance for sending emails.
 * @return PHPMailer Configured PHPMailer instance.
 * @throws Exception If PHPMailer encounters an error during setup.
 */
function getMailer()
{
    $mail = new PHPMailer(true); // Create a new PHPMailer instance

    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = $_ENV['MAIL_HOST']; // Set the SMTP server to send through
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = $_ENV['MAIL_USERNAME']; // SMTP username from environment
    $mail->Password = $_ENV['MAIL_PASSWORD']; // SMTP password from environment
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION']; // Enable encryption (e.g., SSL/TLS)
    $mail->Port = $_ENV['MAIL_PORT']; // Set the SMTP port

    return $mail; // Return the configured PHPMailer instance
}

/**
 * Start a secure session and generate a CSRF token.
 * Checks if the session is started and sets up secure session parameters.
 * Generates a CSRF token if it does not exist in the session.
 * @return void
 */
function startSecureSession()
{
    if (session_status() === PHP_SESSION_NONE) { // Check if session has not started
        session_set_cookie_params([ // Set secure session cookie parameters
            'path' => '/SCI/',
            'domain' => '',
            'secure' => false, // Not using secure connection (for local testing)
            'httponly' => true, // Restrict access to session cookie via JavaScript
            'samesite' => 'Strict' // Enforce strict SameSite policy
        ]);
        session_start(); // Start the session
        session_regenerate_id(true); // Regenerate session ID for security
    }

    if (empty($_SESSION['csrf_token'])) { // Check if CSRF token does not exist in session
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate and store CSRF token
    }
}

/**
 * Starts a session and generates a CSRF token if not already present.
 *
 * @return void
 */
function startSession()
{
    if (session_status() === PHP_SESSION_NONE)
        session_start(); // Starts session if none exists
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate CSRF token if not present
}

/**
 * Regenerates the session ID to prevent session fixation attacks.
 *
 * @return void
 */
function regenerateSessionId()
{
    if (session_status() === PHP_SESSION_ACTIVE)
        session_regenerate_id(true); // Regenerate session ID
}

/**
 * Checks if the user is logged in by verifying the session for a user ID.
 *
 * @return mixed Returns the user ID if logged in, otherwise false.
 */
function is_useronline()
{
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : false; // Return user ID or false
}

/**
 * Logs out the current user by destroying the session.
 *
 * @return string Message indicating the result of logout.
 */
function logoutUser()
{
    startSession(); // Start session if not already started
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy session
    return 'Logged out successfully.'; // Return logout message
}

/**
 * Log the user in.
 * 
 * This function attempts to authenticate the user by comparing the provided credentials with the database.
 * If the credentials are valid and the account is active, a session is started, and the user is logged in.
 * 
 * @param string $username The username of the user attempting to log in.
 * @param string $password The password provided by the user.
 * @return string A message indicating the result of the login attempt.
 */
function loginUser($username, $password)
{
    $pdo = getPDOConnection();
    if (!$pdo) {
        return 'Database error, please try again later.';
    }

    try {
        $query = "SELECT user_id, username, password, isactive FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['isactive'] == 1) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    return 'Login successful.';
                } else {
                    return 'Account not activated.';
                }
            }
        }
        return 'Invalid credentials.';
    } catch (PDOException $e) {
        return 'Internal error. Please contact support.';
    }
}

/**
 * Process user login.
 *
 * Tries to log in the user with the provided username and password.
 * Starts a session if not already started, sets session variables, 
 * and returns the result of the login attempt.
 *
 * @param string $username The username provided by the user.
 * @param string $password The password provided by the user.
 * @return string The result of the login attempt.
 */
function processLogin($username, $password)
{
    $login_result = loginUser($username, $password); // Attempt to log in the user

    if (trim($login_result) === 'Login successful.') { // Check if login was successful
        if (session_status() === PHP_SESSION_NONE) { // Start a session if none exists
            session_start();
        }
        $_SESSION['user_logged_in'] = true; // Set session variable for login status
        $_SESSION['username'] = $username; // Set session variable for username
        return $login_result; // Return login result if successful
    }

    return $login_result; // Return login result if unsuccessful
}

/**
 * Remember the user by setting cookies for the username and encrypted password.
 * 
 * This function sets a "remember me" cookie with the provided username and an encrypted password that lasts for 30 days.
 * 
 * @param string $username The username to remember.
 * @param string $password The password to encrypt and store in a cookie.
 * @return void
 */
function rememberMe($username, $password)
{
    $expiryTime = time() + 86400 * 30; // Cookie valid for 30 days

    $encryptedPassword = password_hash($password, PASSWORD_BCRYPT);

    setcookie('username', $username, $expiryTime, '/SCI/');
    setcookie('password', $encryptedPassword, $expiryTime, '/SCI/');
}

/**
 * Attempt automatic login using cookies.
 * 
 * This function checks if the user has valid cookies for the username and encrypted password. If both exist,
 * it attempts to log the user in using the provided credentials. If successful, the user is logged in and redirected.
 * 
 * @return string|null A message indicating the result of the login attempt or null if successful.
 */
function autoLogin()
{
    if (isset($_COOKIE['username']) && isset($_COOKIE['password'])) {
        $username = $_COOKIE['username'];
        $encryptedPassword = $_COOKIE['password'];

        $login_result = loginUser($username, $encryptedPassword);

        if ($login_result === 'Login successful.') {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit();
        } else {
            return 'Invalid credentials from cookies.';
        }
    }
}

/**
 * Generate an activation code using the user's email.
 *
 * Combines the user's email, current timestamp, and a unique ID, 
 * then hashes the result using the SHA-256 algorithm to create an activation code.
 *
 * @param string $email The user's email address.
 * @return string The generated activation code.
 */
function generateActivationCode($email)
{
    $salt = bin2hex(random_bytes(32)); // Generate a random salt
    $uniqueString = $email . time() . uniqid() . $salt; // Add salt to unique string
    return hash('sha256', $uniqueString); // Return the hash
}

/**
 * Send an activation email to the user.
 *
 * Loads configuration, connects to the database, retrieves user data, 
 * generates or retrieves an activation code, constructs the activation link, 
 * and sends the activation email to the user.
 *
 * @param string $userEmail The email address to send the activation email to.
 * @param string $activationCode The activation code to include in the email.
 * @param string|null $username Optional. The username of the user, used to fetch additional user data if provided.
 * @return mixed Returns true if the email was sent successfully, or an error message otherwise.
 */
function sendActivationEmail($userEmail, $activationCode, $username = null)
{
    $config = getEnvironmentConfig(); // Load environment configuration
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']); // Get the base URL
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'; // Determine the environment (local/live)

    $pdo = getPDOConnection(); // Establish database connection
    if (!$pdo) { // Check if database connection is successful
        handleError("Database connection failed while sending activation email.", $env);
        return 'Database connection failed';
    }

    try {
        if ($username) { // If username is provided, retrieve user data
            $query = "SELECT activation_code, isactive, email FROM users WHERE username = :username";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) { // If user exists, check activation status
                if ($user['isactive'] == 1) { // Check if the user is already active
                    return 'User is already active.';
                }
                if (empty($user['activation_code'])) { // If no activation code, generate one
                    $activationCode = generateActivationCode($user['email']);
                    $updateQuery = "UPDATE users SET activation_code = :activation_code WHERE username = :username";
                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->execute(['activation_code' => $activationCode, 'username' => $username]);
                } else {
                    $activationCode = $user['activation_code']; // Use existing activation code
                }
            } else {
                handleError("User {$username} does not exist.", $env); // Log error if user doesn't exist
                return 'User does not exist.';
            }
        }

        $activationLink = rtrim($baseUrl, '/') . "/auth/activate.php?code=$activationCode"; // Construct activation link

        $mail = getMailer(); // Initialize the mailer
        $mail->setFrom($config['MAIL_USERNAME'], 'Sarjana Canggih Indonesia'); // Set sender
        $mail->addAddress($userEmail); // Add recipient
        $mail->Subject = 'Activate your account'; // Set email subject
        $mail->Body = "Click the link to activate your account: $activationLink"; // Set email body

        if (!$mail->send()) { // Check if email was sent successfully
            handleError('Mailer Error: ' . $mail->ErrorInfo, $env);
            return 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }

        return true; // Return true if email sent successfully
    } catch (PDOException $e) { // Catch database exceptions
        handleError("PDOException occurred while sending activation email: " . $e->getMessage(), $env);
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Resend an activation email to the user.
 *
 * Loads configuration, connects to the database, retrieves user data, 
 * generates or retrieves the activation code, constructs the activation link, 
 * and resends the activation email to the user.
 *
 * @param string $username The username of the user to resend the activation email to.
 * @return string A message indicating the result of the operation.
 */
function resendActivationEmail($username)
{
    $config = getEnvironmentConfig(); // Load environment configuration
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']); // Get the base URL
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'; // Determine the environment (local/live)

    $pdo = getPDOConnection(); // Establish database connection
    if (!$pdo) { // Check if database connection is successful
        handleError("Database connection failed when trying to resend activation email.", $env);
        return 'Database connection failed';
    }

    try {
        $query = "SELECT email, activation_code, isactive FROM users WHERE username = :username"; // Retrieve user data
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) { // If user exists, proceed
            if ($user['isactive'] == 1) { // Check if the user is already active
                return 'User is already active.';
            }
            if (empty($user['activation_code'])) { // Generate activation code if not present
                $activationCode = generateActivationCode($user['email']);
                $updateQuery = "UPDATE users SET activation_code = :activation_code WHERE username = :username";
                $stmt = $pdo->prepare($updateQuery);
                $stmt->execute(['activation_code' => $activationCode, 'username' => $username]);
            } else {
                $activationCode = $user['activation_code']; // Use existing activation code
            }

            $activationLink = rtrim($baseUrl, '/') . "/auth/activate.php?code=$activationCode"; // Construct activation link
            $emailSent = sendActivationEmail($user['email'], $activationCode, $username); // Send activation email

            if ($emailSent === true) { // Check if email was sent successfully
                return 'Activation email resent successfully.';
            } else {
                handleError("Failed to send activation email to {$user['email']} with error: $emailSent", $env);
                return 'Error: ' . $emailSent;
            }
        } else {
            return 'User does not exist.'; // Return error if user does not exist
        }
    } catch (PDOException $e) { // Catch database exceptions
        handleError("PDOException occurred while resending activation email: " . $e->getMessage(), $env);
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Registers a new user by validating input and inserting them into the database.
 *
 * Validates username, email, and password. Checks if username or email already exists.
 * Hashes the password, generates an activation code, and inserts the user into the database.
 *
 * @param string $username The username of the user.
 * @param string $email The email of the user.
 * @param string $password The password of the user.
 * @param string $env The environment setting for error handling.
 * @return string A message indicating the result of the registration process.
 */
function registerUser($username, $email, $password, $env)
{
    $pdo = getPDOConnection(); // Establish database connection
    if (!$pdo) { // Check if database connection failed
        handleError('Database connection failed.', $env);
        return 'Internal server error. Please try again later.';
    }
    try {
        $usernameViolations = validateUsername($username); // Validate username
        if (count($usernameViolations) > 0)
            return $usernameViolations[0]->getMessage();
        $emailViolations = validateEmail($email); // Validate email
        if (count($emailViolations) > 0)
            return $emailViolations[0]->getMessage();
        $passwordViolations = validatePassword($password); // Validate password
        if (count($passwordViolations) > 0)
            return $passwordViolations[0]->getMessage();

        $checkQuery = "SELECT 1 FROM users WHERE username = :username OR email = :email"; // Check if username or email exists
        $stmt = $pdo->prepare($checkQuery);
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch())
            return 'Username or email already exists.';

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Hash password
        if ($hashedPassword === false) { // Check if password hashing failed
            handleError('Password hashing failed.', $env);
            return 'Internal server error. Please try again later.';
        }
        $activationCode = generateActivationCode($email); // Generate activation code
        $currentTime = Carbon::now()->toDateTimeString(); // Get current timestamp
        $insertQuery = "INSERT INTO users (username, email, password, isactive, activation_code, created_at) VALUES (:username, :email, :password, 0, :activation_code, :created_at)"; // Insert user into database
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'activation_code' => $activationCode,
            'created_at' => $currentTime
        ]);

        if ($stmt->rowCount() > 0) { // Check if insertion was successful
            $stmt = $pdo->prepare("SELECT activation_code FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $activationCode = $stmt->fetchColumn();
            return 'Registration successful. Please activate your account via email. Activation Code: ' . $activationCode;
        }
    } catch (PDOException $e) { // Catch database exceptions
        handleError('Database error: ' . $e->getMessage(), $env);
        return 'Internal server error. Please try again later.';
    }
    return 'Registration failed. Please try again later.';
}

/**
 * Retrieves user information from the database.
 *
 * @param int $userId The ID of the user whose information is to be retrieved.
 * @return array|null An associative array of user information or null if the user does not exist or an error occurs.
 */
function getUserInfo($userId)
{
    $pdo = getPDOConnection(); // Establish PDO connection to the database
    if (!$pdo)
        return null; // Return null if connection fails

    try {
        $query = "SELECT u.user_id, u.username, u.email, u.role, 
                         up.first_name, up.last_name, up.phone, up.address, up.city, up.country, up.profile_image_filename
                  FROM users u
                  LEFT JOIN user_profiles up ON u.user_id = up.user_id
                  WHERE u.user_id = :user_id";
        $stmt = $pdo->prepare($query); // Prepare the SQL query
        $stmt->execute(['user_id' => $userId]); // Execute query with user ID parameter
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch user information

        return $userInfo ?: null; // Return user info or null if not found
    } catch (PDOException $e) {
        handleError('Error: ' . $e->getMessage(), 'live'); // Log error in case of exception
        return null; // Return null on error
    }
}

/**
 * Returns the URL of the user's profile image.
 *
 * @param string|null $imageFilename The filename of the user's profile image. If null or empty, the default image is returned.
 * @return string The URL of the user's profile image or the default profile image if no filename is provided.
 */
function default_profile_image($imageFilename)
{
    $pdo = getPDOConnection(); // Establish PDO connection to the database
    if (!$pdo)
        return null; // Return null if connection fails

    try {
        $config = getEnvironmentConfig(); // Get the environment configuration

        $baseUrl = $config['BASE_URL'] . '/uploads/user_images/'; // Define the base URL for user images

        if ($_SERVER['HTTP_HOST'] !== 'localhost') // Modify the base URL for live environments
            $baseUrl = dirname($baseUrl); // Go one folder up for live environments

        if (empty($imageFilename)) // Check if image filename is empty
            return $baseUrl . 'default-profile.svg'; // Return the default profile image URL

        return $baseUrl . $imageFilename; // Return the constructed URL for the user's profile image
    } catch (PDOException $e) {
        handleError('Error: ' . $e->getMessage(), 'live'); // Log error in case of exception
        return null; // Return null on error
    }
}

/**
 * Activates a user account using the activation code.
 *
 * @param string $activationCode The activation code sent to the user.
 * @return string A message indicating the result of the activation process.
 */
function activateAccount($activationCode)
{
    define('DB_CONNECTION_FAILED', 'Database connection failed');
    define('ACCOUNT_ACTIVATED_SUCCESS', 'Account activated successfully.');
    define('INVALID_ACTIVATION_CODE', 'Invalid activation code.');
    define('ERROR_OCCURED', 'Error: ');

    $config = getEnvironmentConfig(); // Load environment configuration
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'; // Determine the environment

    $activationCode = sanitize_input($activationCode); // Sanitize the input
    if (strlen($activationCode) !== 64 || !ctype_xdigit($activationCode)) { // Validate activation code format
        handleError('Invalid activation code format: ' . $activationCode, $env);
        return INVALID_ACTIVATION_CODE;
    }

    $pdo = getPDOConnection(); // Establish database connection
    if (!$pdo) {
        handleError(DB_CONNECTION_FAILED, $env);
        return DB_CONNECTION_FAILED;
    }

    try {
        $pdo->beginTransaction(); // Begin database transaction
        $query = "UPDATE users SET isactive = 1 WHERE activation_code = :activation_code"; // Update query
        $stmt = $pdo->prepare($query);
        $stmt->execute(['activation_code' => $activationCode]); // Execute the query

        if ($stmt->rowCount() === 0) { // Check if activation code is valid
            handleError('No rows affected, invalid activation code: ' . $activationCode, $env);
            $pdo->rollBack(); // Rollback transaction
            return INVALID_ACTIVATION_CODE;
        }

        $pdo->commit(); // Commit transaction
        return ACCOUNT_ACTIVATED_SUCCESS;
    } catch (PDOException $e) { // Handle database errors
        $pdo->rollBack(); // Rollback on error
        handleError('Database error: ' . $e->getMessage(), $env);
        return ERROR_OCCURED . $e->getMessage();
    }
}

/**
 * Handles the password reset process for users.
 *
 * This function validates the input, checks the database for the user,
 * generates a password reset hash, and sends a reset link via email.
 *
 * @param string $email_or_username The email or username provided by the user.
 * @param string $recaptcha_response The reCAPTCHA response token.
 * @param string $csrf_token The CSRF token for form protection.
 * @param HttpClientInterface $httpClient An HTTP client instance for validating reCAPTCHA.
 * @return array Returns an array with 'status' (success/error) and 'message'.
 */
function processPasswordResetRequest($email_or_username, $recaptcha_response, $csrf_token, HttpClientInterface $httpClient, $config, $baseUrl)
{
    // Set the default timezone to Asia/Jakarta
    date_default_timezone_set('Asia/Jakarta');

    // Load environment configuration and set environment type based on host
    $config = getEnvironmentConfig();
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    // Validate CSRF token and reCAPTCHA response using a helper function
    if (!validateCsrfAndRecaptcha(['csrf_token' => $csrf_token, 'g-recaptcha-response' => $recaptcha_response], $httpClient)) {
        handleError('Invalid CSRF token or reCAPTCHA.', $env);
        return ['status' => 'error', 'message' => 'Invalid CSRF token or reCAPTCHA.'];
    }

    // Check if the provided input is an email or username and validate accordingly
    $isEmail = filter_var($email_or_username, FILTER_VALIDATE_EMAIL);
    $violations = $isEmail ? validateEmail($email_or_username) : validateUsername($email_or_username);

    if (count($violations) > 0) {
        $errorMessages = [];
        foreach ($violations as $violation) {
            $errorMessages[] = $violation->getMessage();
        }
        handleError(implode('<br>', $errorMessages), $env);
        return ['status' => 'error', 'message' => implode('<br>', $errorMessages)];
    }

    // Establish a database connection
    $pdo = getPDOConnection();
    if (!$pdo) {
        handleError('Database connection error.', $env);
        return ['status' => 'error', 'message' => 'Database connection error.'];
    }

    // Check if the email or username exists in the database
    $stmt = $pdo->prepare("SELECT user_id, email FROM users WHERE email = :input OR username = :input");
    $stmt->execute(['input' => $email_or_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        handleError('Email or username not found.', $env);
        return ['status' => 'error', 'message' => 'Email or username not found.'];
    }

    // Generate a unique hash for the password reset token
    $userId = $user['user_id'];
    $userEmail = $user['email'];
    $resetHash = generateActivationCode($userEmail);

    // Set expiration time using Carbon
    $expiresAt = Carbon::now()->addHour()->toDateTimeString();

    // Clear expired reset tokens for the user
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id OR expires_at <= NOW()");
    $stmt->execute(['user_id' => $userId]);

    // Save the new reset token in the database
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, hash, expires_at) VALUES (:user_id, :hash, :expires_at)");
    if (!$stmt->execute(['user_id' => $userId, 'hash' => $resetHash, 'expires_at' => $expiresAt])) {
        $errorMessage = "Failed to save reset token to database for user ID: $userId";
        handleError($errorMessage, $env);
        return ['status' => 'error', 'message' => 'Failed to process your request. Please try again later.'];
    }

    // Generate the password reset link
    $resetLink = generateResetPasswordLink($resetHash);

    // Send the password reset email
    $emailSent = sendResetPasswordEmail($userEmail, $resetLink);

    if ($emailSent) {
        return ['status' => 'success', 'message' => 'Password reset instructions have been sent to your email.'];
    } else {
        $errorMessage = "Failed to send reset password email for user ID: $userId";
        handleError($errorMessage, $env);
        return ['status' => 'error', 'message' => 'Failed to send password reset email.'];
    }
}

/**
 * Generate a reset password link.
 *
 * @param string $resetHash The unique hash for the reset request.
 * @return string The full reset password link.
 */
function generateResetPasswordLink($resetHash)
{
    global $baseUrl;
    return rtrim($baseUrl, '/') . "/auth/reset_password.php?hash=$resetHash";
}

/**
 * Sends a reset password email to the user.
 *
 * This function creates a mailer instance, sets up the email content including the reset password link,
 * and attempts to send the email. If the sending fails, it handles the error according to the environment configuration.
 *
 * @param string $userEmail The email address of the user to whom the reset password email will be sent.
 * @param string $resetLink The reset password link that the user can click to reset their password.
 * @return bool Returns true if the email was sent successfully, false otherwise.
 */
function sendResetPasswordEmail($userEmail, $resetLink)
{
    global $config;
    try {
        $mail = getMailer();
        $mail->setFrom($config['MAIL_USERNAME'], 'Sarjana Canggih Indonesia');
        $mail->addAddress($userEmail);
        $mail->Subject = 'Password Reset Request';

        // Email body dengan pesan tambahan
        $mail->Body = "
            <p>Anda menerima email ini karena ada permintaan reset password untuk akun Anda.</p>
            <p>Silakan klik tautan di bawah ini untuk mereset password Anda:</p>
            <p><a href='$resetLink'>Reset Password</a></p>
            <p>Jika Anda tidak melakukan permintaan ini, abaikan email ini.</p>
            <p>Tautan ini akan kedaluwarsa dalam 1 jam.</p>
            <p>Terima kasih,</p>
            <p>Tim Sarjana Canggih Indonesia</p>
        ";
        $mail->isHTML(true); // Mengaktifkan format HTML untuk email

        return $mail->send();
    } catch (Exception $e) {
        $envConfig = getEnvironmentConfig();
        handleError("Failed to send reset password email: " . $e->getMessage(), $envConfig['BASE_URL']);
        return false;
    }
}

/**
 * Validates the reset token and retrieves user information if the token is valid.
 *
 * @param string $token The reset token to validate.
 * @param PDO $pdo The PDO database connection object.
 * @return array|null Returns an associative array containing user_id and email if the token is valid, otherwise null.
 */
function validateResetToken($token, $pdo)
{
    // SQL query to select user_id and email from password_resets and users tables
    $sql = "SELECT pr.user_id, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.user_id
            WHERE pr.hash = :hash 
              AND pr.completed = 0 
              AND pr.expires_at > NOW()";
    $stmt = $pdo->prepare($sql); // Prepare the SQL statement
    $stmt->execute(['hash' => $token]); // Execute the statement with the provided token
    return $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the result as an associative array
}

/**
 * Updates the user's password in the database.
 *
 * @param int $user_id The ID of the user whose password is to be updated.
 * @param string $hashed_password The new hashed password.
 * @param PDO $pdo The PDO database connection object.
 */
function updateUserPassword($user_id, $hashed_password, $pdo)
{
    // SQL query to update the user's password
    $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql); // Prepare the SQL statement
    $stmt->execute(['password' => $hashed_password, 'user_id' => $user_id]); // Execute the statement with the new password and user_id
}

/**
 * Marks the reset token as used in the database.
 *
 * @param string $token The reset token to mark as used.
 * @param PDO $pdo The PDO database connection object.
 */
function markTokenAsUsed($token, $pdo)
{
    // SQL query to mark the token as used by setting completed to 1 and updating completed_at
    $sql = "UPDATE password_resets 
            SET completed = 1, completed_at = NOW() 
            WHERE hash = :hash";
    $stmt = $pdo->prepare($sql); // Prepare the SQL statement
    $stmt->execute(['hash' => $token]); // Execute the statement with the provided token
}

/**
 * Change the user's email address.
 *
 * @param int $userId The user ID.
 * @param string $newEmail The new email address to set.
 * @return string A message indicating the result of the operation.
 */

function handlePasswordReset($token, $pdo): void
{
    $user = validateResetToken($token, $pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['token'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';
        $new_password = $_POST['password'] ?? '';

        validateCSRFToken($csrf_token);

        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        $recaptcha_secret = RECAPTCHA_SECRET_KEY;
        $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha_response";
        $recaptcha_data = json_decode(file_get_contents($recaptcha_url));

        if (!$recaptcha_data->success) {
            die('reCAPTCHA validation failed.');
        }

        if ($user) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            updateUserPassword($user['user_id'], $hashed_password, $pdo);
            markTokenAsUsed($token, $pdo);

            header("Location: login.php?message=Password+reset+successfully.");
            exit();
        } else {
            die('Invalid or expired token.');
        }
    }
}

function changeEmail($userId, $newEmail)
{
    $pdo = getPDOConnection();
    if (!$pdo)
        return 'Database connection failed';

    try {
        $query = "UPDATE users SET email = :email WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'email' => $newEmail,
            'user_id' => $userId
        ]);
        return 'Email address updated successfully.';
    } catch (PDOException $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Delete a user's account.
 *
 * @param int $userId The user ID.
 * @return string A message indicating the result of the operation.
 */
function deleteAccount($userId)
{
    $pdo = getPDOConnection();
    if (!$pdo)
        return 'Database connection failed';

    try {
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        return 'Account deleted successfully.';
    } catch (PDOException $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Redirects the user to the homepage if logged in.
 * 
 * This function performs the following tasks:
 * 1. Starts the session if not already started.
 * 2. Checks if the user is logged in by verifying the session.
 * 3. Redirects the user to the homepage based on the environment configuration.
 *
 * @return void
 */
function redirect_if_logged_in()
{
    startSession(); // Step 1: Start the session if not already started
    if (is_useronline()) { // Step 2: Check if the user is logged in
        $config = getEnvironmentConfig(); // Step 3: Get environment configuration
        $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']); // Step 4: Get base URL based on environment
        header("Location: {$baseUrl}"); // Step 5: Redirect to homepage
        exit();
    }
}