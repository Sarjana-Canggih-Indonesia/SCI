<?php
// user_actions_config.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/validate.php';

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\ConstraintViolationList;

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
 *
 * This function performs the following tasks:
 * 1. Retrieves the environment-specific configuration settings (database credentials).
 * 2. Establishes a connection to a MySQL database using PDO.
 * 3. Sets the error mode to exceptions for better error handling.
 * 
 * @return PDO|null PDO instance for database interaction or null if an error occurs.
 */
function getPDOConnection()
{
    try {
        // 1. Retrieve environment-specific configuration settings
        $config = getEnvironmentConfig();

        // 2. Create a new PDO instance with the database credentials from the configuration
        $pdo = new PDO(
            "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}",
            $config['DB_USER'],
            $config['DB_PASS']
        );

        // 3. Set the error mode to exceptions to catch any potential issues
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 4. Return the PDO instance for further database interaction
        return $pdo;
    } catch (PDOException $e) {
        // 5. Log the error message for debugging purposes
        handleError("Database Error: " . $e->getMessage(), ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live');

        // 6. Inform the user that an error occurred without revealing sensitive details
        echo 'Database Error: An error occurred. Please try again later.';

        // 7. Return null if the connection fails
        return null;
    }
}

/**
 * Get a configured instance of PHPMailer.
 * 
 * This function initializes a new PHPMailer object and configures it with SMTP settings
 * fetched from environment variables. The configured PHPMailer object is then returned
 * for sending emails.
 * 
 * @return PHPMailer Configured PHPMailer instance.
 * @throws Exception If PHPMailer encounters an error during setup.
 */
function getMailer()
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'];
    $mail->Password = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
    $mail->Port = $_ENV['MAIL_PORT'];

    return $mail;
}

/**
 * Start a secure session and generate a CSRF token.
 * 
 * This function checks if the session has started and sets up the session with secure
 * parameters. It also generates a CSRF token if it does not already exist in the session.
 * 
 * @return void
 */
function startSecureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'path' => '/SCI/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
        session_regenerate_id(true);
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Start a session and generate a CSRF token.
 * 
 * This function starts a session if it is not already started and generates a CSRF token
 * if it is not present in the session.
 * 
 * @return void
 */
function startSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Regenerate the session ID for security purposes.
 * 
 * This function regenerates the session ID to prevent session fixation attacks.
 * 
 * @return void
 */
function regenerateSessionId()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Check if the user is logged in.
 * 
 * This function checks if the user ID is stored in the session, indicating that the user is logged in.
 * 
 * @return mixed The user ID if the user is logged in, false otherwise.
 */
function is_useronline()
{
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    return false;
}

/**
 * Log out the current user by destroying the session.
 *
 * @return string A message indicating the result of the operation.
 */
function logoutUser()
{
    startSession();
    session_unset();
    session_destroy();
    return 'Logged out successfully.';
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
 * This function performs the following tasks:
 * 1. Attempts to log in the user using the provided username and password.
 * 2. Starts a session if one is not already active.
 * 3. Sets session variables if the login is successful.
 * 4. Returns the result of the login attempt.
 *
 * @param string $username The username provided by the user.
 * @param string $password The password provided by the user.
 * @return string The result of the login attempt.
 */
function processLogin($username, $password)
{
    // Step 1: Attempt to log in the user
    $login_result = loginUser($username, $password);

    // Step 2: Check if the login was successful
    if (trim($login_result) === 'Login successful.') {
        // Step 3: Start a session if one is not already active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Step 4: Set session variables for the logged-in user
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;

        // Step 5: Return the login result
        return $login_result;
    }

    // Step 6: Return the login result if unsuccessful
    return $login_result;
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
 * This function performs the following tasks:
 * 1. Combines the user's email, current timestamp, and a unique ID to create a unique string.
 * 2. Hashes the combined string using the SHA-256 algorithm to generate the activation code.
 * 3. Returns the generated activation code.
 *
 * @param string $email The user's email address.
 * @return string The generated activation code.
 */
function generateActivationCode($email)
{
    // Step 1: Combine the email, current timestamp, and a unique ID
    $uniqueString = $email . time() . uniqid();

    // Step 2: Hash the combined string using SHA-256
    $activationCode = hash('sha256', $uniqueString);

    // Step 3: Return the generated activation code
    return $activationCode;
}

/**
 * Send an activation email to the user.
 *
 * This function performs the following tasks:
 * 1. Loads environment configuration and determines the base URL.
 * 2. Establishes a database connection.
 * 3. Retrieves user data and validates activation status if a username is provided.
 * 4. Generates or retrieves an activation code if necessary.
 * 5. Constructs the activation link and sends the activation email.
 * 6. Handles errors and logs them based on the environment.
 *
 * @param string $userEmail The email address to send the activation email to.
 * @param string $activationCode The activation code to include in the email.
 * @param string|null $username Optional. The username of the user, used to fetch additional user data if provided.
 * @return mixed Returns true if the email was sent successfully, or an error message otherwise.
 */
function sendActivationEmail($userEmail, $activationCode, $username = null)
{
    // Step 1: Load environment configuration and determine the base URL
    $config = getEnvironmentConfig();
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    // Step 2: Establish a database connection
    $pdo = getPDOConnection();
    if (!$pdo) {
        handleError("Database connection failed while sending activation email.", $env);
        return 'Database connection failed';
    }

    try {
        // Step 3: Retrieve user data and validate activation status if username is provided
        if ($username) {
            $query = "SELECT activation_code, isactive, email FROM users WHERE username = :username";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Step 4: Check if the user is already active
                if ($user['isactive'] == 1) {
                    return 'User is already active.';
                }

                // Step 5: Generate or retrieve the activation code
                if (empty($user['activation_code'])) {
                    $activationCode = generateActivationCode($user['email']);
                    $updateQuery = "UPDATE users SET activation_code = :activation_code WHERE username = :username";
                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->execute(['activation_code' => $activationCode, 'username' => $username]);
                } else {
                    $activationCode = $user['activation_code'];
                }
            } else {
                handleError("User {$username} does not exist.", $env);
                return 'User does not exist.';
            }
        }

        // Step 6: Construct the activation link
        $activationLink = rtrim($baseUrl, '/') . "/auth/activate.php?code=$activationCode";

        // Step 7: Initialize the mailer and send the activation email
        $mail = getMailer();
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'error_log';

        $mail->setFrom($config['MAIL_USERNAME'], 'Sarjana Canggih Indonesia');
        $mail->addAddress($userEmail);
        $mail->Subject = 'Activate your account';
        $mail->Body = "Click the link to activate your account: $activationLink";

        // Step 8: Check if the email was sent successfully
        if (!$mail->send()) {
            handleError('Mailer Error: ' . $mail->ErrorInfo, $env);
            return 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }

        return true;
    } catch (PDOException $e) {
        // Step 9: Handle database errors
        handleError("PDOException occurred while sending activation email: " . $e->getMessage(), $env);
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Resend an activation email to the user.
 *
 * This function performs the following tasks:
 * 1. Loads environment configuration and determines the base URL.
 * 2. Establishes a database connection.
 * 3. Retrieves user data and validates activation status.
 * 4. Generates or retrieves an activation code if necessary.
 * 5. Constructs the activation link and sends the activation email.
 * 6. Handles errors and logs them based on the environment.
 *
 * @param string $username The username of the user to resend the activation email to.
 * @return string A message indicating the result of the operation.
 */
function resendActivationEmail($username)
{
    // Step 1: Load environment configuration and determine the base URL
    $config = getEnvironmentConfig();
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    // Step 2: Establish a database connection
    $pdo = getPDOConnection();
    if (!$pdo) {
        handleError("Database connection failed when trying to resend activation email.", $env);
        return 'Database connection failed';
    }

    try {
        // Step 3: Retrieve user data and validate activation status
        $query = "SELECT email, activation_code, isactive FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Step 4: Check if the user is already active
            if ($user['isactive'] == 1) {
                return 'User is already active.';
            }

            // Step 5: Generate or retrieve the activation code
            if (empty($user['activation_code'])) {
                $activationCode = generateActivationCode($user['email']);
                $updateQuery = "UPDATE users SET activation_code = :activation_code WHERE username = :username";
                $stmt = $pdo->prepare($updateQuery);
                $stmt->execute(['activation_code' => $activationCode, 'username' => $username]);
            } else {
                $activationCode = $user['activation_code'];
            }

            // Step 6: Construct the activation link and send the activation email
            $activationLink = rtrim($baseUrl, '/') . "/auth/activate.php?code=$activationCode";
            $emailSent = sendActivationEmail($user['email'], $activationCode, $username);

            // Step 7: Check if the email was sent successfully
            if ($emailSent === true) {
                return 'Activation email resent successfully.';
            } else {
                handleError("Failed to send activation email to {$user['email']} with error: $emailSent", $env);
                return 'Error: ' . $emailSent;
            }
        } else {
            // Step 8: Handle case where user does not exist
            return 'User does not exist.';
        }
    } catch (PDOException $e) {
        // Step 9: Handle database errors
        handleError("PDOException occurred while resending activation email: " . $e->getMessage(), $env);
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Registers a new user.
 * 
 * Task:
 * 1. Validates the username, email, and password.
 * 2. Checks if the username or email already exists in the database.
 * 3. Hashes the password before storing it.
 * 4. Generates an activation code and inserts the user into the database.
 * 
 * @param string $username The username of the user.
 * @param string $email The email of the user.
 * @param string $password The password of the user.
 * @param string $env The environment setting for error handling.
 * 
 * @return string A message indicating the result of the registration process.
 */
function registerUser($username, $email, $password, $env)
{
    // Step 1: Get PDO connection
    $pdo = getPDOConnection();
    if (!$pdo) {
        handleError('Database connection failed.', $env);
        return 'Internal server error. Please try again later.';
    }

    try {
        // Step 2: Validate Username using validate.php function
        $usernameViolations = validateUsername($username);
        if (count($usernameViolations) > 0) {
            return $usernameViolations[0]->getMessage(); // Return the first violation message
        }

        // Step 3: Validate Email using validate.php function
        $emailViolations = validateEmail($email);
        if (count($emailViolations) > 0) {
            return $emailViolations[0]->getMessage(); // Return the first violation message
        }

        // Step 4: Validate Password using validate.php function
        $passwordViolations = validatePassword($password);
        if (count($passwordViolations) > 0) {
            return $passwordViolations[0]->getMessage(); // Return the first violation message
        }

        // Step 5: Check if the username or email already exists
        $checkQuery = "SELECT 1 FROM users WHERE username = :username OR email = :email";
        $stmt = $pdo->prepare($checkQuery);
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            return 'Username or email already exists.';
        }

        // Step 6: Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        if ($hashedPassword === false) {
            handleError('Password hashing failed.', $env);
            return 'Internal server error. Please try again later.';
        }

        // Step 7: Generate an activation code
        $activationCode = generateActivationCode($email);

        // Step 8: Insert the user into the database
        $insertQuery = "
            INSERT INTO users (username, email, password, isactive, activation_code, created_at) 
            VALUES (:username, :email, :password, 0, :activation_code, NOW())
        ";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'activation_code' => $activationCode
        ]);

        // Step 9: Check if insertion was successful
        if ($stmt->rowCount() > 0) {
            // Ambil kode aktivasi dari hasil query
            $stmt = $pdo->prepare("SELECT activation_code FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $activationCode = $stmt->fetchColumn();
            return 'Registration successful. Please activate your account via email. Activation Code: ' . $activationCode;
        }
    } catch (PDOException $e) {
        // Step 10: Handle database-related exceptions
        handleError('Database error: ' . $e->getMessage(), $env);
        return 'Internal server error. Please try again later.';
    }

    return 'Registration failed. Please try again later.';
}

/**
 * Retrieves user information from the database.
 *
 * This function performs the following tasks:
 * 1. Establishes a PDO connection to the database.
 * 2. Prepares and executes a SQL query to retrieve user details (username, email, role, and profile).
 * 3. Returns an associative array of user information or null if the user does not exist or an error occurs.
 *
 * @param int $userId The ID of the user whose information is to be retrieved.
 * @return array|null An associative array of user information or null if the user does not exist or an error occurs.
 */
function getUserInfo($userId)
{
    $pdo = getPDOConnection(); // Step 1: Establish PDO connection to the database
    if (!$pdo) {
        return null; // Step 2: Return null if the connection fails
    }

    try {
        $query = "SELECT u.user_id, u.username, u.email, u.role, 
                         up.first_name, up.last_name, up.phone, up.address, up.city, up.country, up.profile_image_filename
                  FROM users u
                  LEFT JOIN user_profiles up ON u.user_id = up.user_id
                  WHERE u.user_id = :user_id";
        $stmt = $pdo->prepare($query); // Step 3: Prepare the SQL query
        $stmt->execute(['user_id' => $userId]); // Step 4: Execute the query with the provided user ID
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC); // Step 5: Fetch the user information

        return $userInfo ?: null; // Step 6: Return user information or null if not found
    } catch (PDOException $e) {
        handleError('Error: ' . $e->getMessage(), 'live'); // Step 7: Handle error by logging it
        return null;
    }
}

/**
 * Returns the URL of the user's profile image.
 *
 * This function performs the following tasks:
 * 1. Retrieves the environment configuration for the base URL.
 * 2. Constructs the URL for the user's profile image or returns the default profile image URL if no filename is provided.
 *
 * @param string|null $imageFilename The filename of the user's profile image. If null or empty, the default image is returned.
 * 
 * @return string The URL of the user's profile image or the default profile image if no filename is provided.
 */
function default_profile_image($imageFilename)
{
    $pdo = getPDOConnection(); // Step 1: Establish PDO connection to the database
    if (!$pdo) {
        return null; // Step 2: Return null if the connection fails
    }

    try {
        $config = getEnvironmentConfig(); // Step 3: Get the environment configuration

        $baseUrl = $config['BASE_URL'] . '/uploads/user_images/'; // Step 4: Define the base URL for user images

        if ($_SERVER['HTTP_HOST'] !== 'localhost') { // Step 5: Modify the base URL for live environment
            $baseUrl = dirname($baseUrl); // Step 6: Go one folder up for live environments
        }

        if (empty($imageFilename)) { // Step 7: Check if the image filename is empty
            return $baseUrl . 'default-profile.svg'; // Step 8: Return the default profile image URL
        }

        return $baseUrl . $imageFilename; // Step 9: Return the constructed URL for the user's profile image
    } catch (PDOException $e) {
        handleError('Error: ' . $e->getMessage(), 'live'); // Step 10: Handle error by logging it
        return null;
    }
}

/**
 * Activate a user account using the activation code.
 *
 * This function performs the following tasks:
 * 1. Validates the activation code format.
 * 2. Loads environment configuration and determines the environment (local or live).
 * 3. Establishes a database connection.
 * 4. Updates the user's activation status in the database.
 * 5. Handles errors and logs them based on the environment.
 * 6. Returns a message indicating the result of the activation process.
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

    // Step 1: Load environment configuration and determine the environment
    $config = getEnvironmentConfig();
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    // Step 2: Sanitize and validate the activation code format
    $activationCode = sanitize_input($activationCode);
    if (strlen($activationCode) !== 64 || !ctype_xdigit($activationCode)) {
        handleError('Invalid activation code format: ' . $activationCode, $env);
        return INVALID_ACTIVATION_CODE;
    }

    // Step 3: Establish a database connection
    $pdo = getPDOConnection();
    if (!$pdo) {
        handleError(DB_CONNECTION_FAILED, $env);
        return DB_CONNECTION_FAILED;
    }

    try {
        // Step 4: Begin a database transaction
        $pdo->beginTransaction();

        // Step 5: Update the user's activation status
        $query = "UPDATE users SET isactive = 1 WHERE activation_code = :activation_code";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['activation_code' => $activationCode]);

        // Step 6: Check if the activation was successful
        if ($stmt->rowCount() === 0) {
            handleError('No rows affected, invalid activation code: ' . $activationCode, $env);
            $pdo->rollBack();
            return INVALID_ACTIVATION_CODE;
        }

        // Step 7: Commit the transaction if activation is successful
        $pdo->commit();
        return ACCOUNT_ACTIVATED_SUCCESS;
    } catch (PDOException $e) {
        // Step 8: Rollback the transaction and log the database error
        $pdo->rollBack();
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
        handleError('Invalid CSRF token or reCAPTCHA.', $env); // Handle error if validation fails
        return ['status' => 'error', 'message' => 'Invalid CSRF token or reCAPTCHA.']; // Return error response
    }

    // Check if the provided input is an email or username and validate accordingly
    $isEmail = filter_var($email_or_username, FILTER_VALIDATE_EMAIL); // Check if input is a valid email
    $violations = $isEmail ? validateEmail($email_or_username) : validateUsername($email_or_username); // Validate either email or username

    if (count($violations) > 0) {
        $errorMessages = [];
        foreach ($violations as $violation) {
            $errorMessages[] = $violation->getMessage(); // Collect validation error messages
        }
        handleError(implode('<br>', $errorMessages), $env); // Handle error if validation fails
        return ['status' => 'error', 'message' => implode('<br>', $errorMessages)]; // Return error response
    }

    // Establish a database connection
    $pdo = getPDOConnection();
    if (!$pdo) {
        handleError('Database connection error.', $env); // Handle error if database connection fails
        return ['status' => 'error', 'message' => 'Database connection error.']; // Return error response
    }

    // Check if the email or username exists in the database
    $stmt = $pdo->prepare("SELECT user_id, email FROM users WHERE email = :input OR username = :input");
    $stmt->execute(['input' => $email_or_username]); // Execute the query to find the user
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user data

    if (!$user) {
        handleError('Email or username not found.', $env); // Handle error if user is not found
        return ['status' => 'error', 'message' => 'Email or username not found.']; // Return error response
    }

    // Generate a unique hash for the password reset token
    $userId = $user['user_id']; // Get the user ID
    $userEmail = $user['email']; // Get the user email
    $resetHash = generateActivationCode($userEmail); // Generate a unique hash for password reset
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Set expiration time for reset token

    // Clear expired reset tokens for the user
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id OR expires_at <= NOW()");
    $stmt->execute(['user_id' => $userId]); // Delete expired reset tokens from the database

    // Save the new reset token in the database
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, hash, expires_at) VALUES (:user_id, :hash, :expires_at)");
    if (!$stmt->execute(['user_id' => $userId, 'hash' => $resetHash, 'expires_at' => $expiresAt])) {
        $errorMessage = "Failed to save reset token to database for user ID: $userId"; // Error message if insertion fails
        handleError($errorMessage, $env); // Handle the error
        return ['status' => 'error', 'message' => 'Failed to process your request. Please try again later.']; // Return error response
    }

    // Generate the password reset link
    $resetLink = generateResetPasswordLink($resetHash); // Create the reset password link

    // Send the password reset email
    $emailSent = sendResetPasswordEmail($userEmail, $resetLink); // Send the email with the reset link

    if ($emailSent) {
        return ['status' => 'success', 'message' => 'Password reset instructions have been sent to your email.']; // Return success if email is sent
    } else {
        $errorMessage = "Failed to send reset password email for user ID: $userId"; // Error message if email fails to send
        handleError($errorMessage, $env); // Handle the error
        return ['status' => 'error', 'message' => 'Failed to send password reset email.']; // Return error response
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

function validateResetToken($token, $pdo)
{
    $sql = "SELECT pr.user_id, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.user_id
            WHERE pr.hash = :hash 
              AND pr.completed = 0 
              AND pr.expires_at > NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hash' => $token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateUserPassword($user_id, $hashed_password, $pdo)
{
    $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['password' => $hashed_password, 'user_id' => $user_id]);
}

function markTokenAsUsed($token, $pdo)
{
    $sql = "UPDATE password_resets 
            SET completed = 1, completed_at = NOW() 
            WHERE hash = :hash";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hash' => $token]);
}

/**
 * Change the user's email address.
 *
 * @param int $userId The user ID.
 * @param string $newEmail The new email address to set.
 * @return string A message indicating the result of the operation.
 */
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
 * This function performs the following tasks:
 * 1. Starts the session if not already started.
 * 2. Checks if the user is logged in by verifying the session.
 * 3. Redirects the user to the homepage based on the environment configuration if they are logged in.
 *
 * @return void
 */
function redirect_if_logged_in()
{
    // Step 1: Start the session if not already started
    startSession();

    // Step 2: Check if the user is already logged in
    if (is_useronline()) {
        // Step 3: Get the environment configuration
        $config = getEnvironmentConfig();

        // Step 4: Get the appropriate base URL for the environment
        $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);

        // Step 5: Redirect to the homepage based on the environment's base URL
        header("Location: {$baseUrl}");
        exit();
    }
}