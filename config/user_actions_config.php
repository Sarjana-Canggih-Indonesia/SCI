<?php
// user_actions_config.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/validate.php';
require_once __DIR__ . '/database/database-config.php';
require_once __DIR__ . '/auth/admin_functions.php';

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Carbon\Carbon;

date_default_timezone_set('Asia/Jakarta');

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
 * Get a configured instance of PHPMailer.
 * Initializes and configures PHPMailer using SMTP settings from environment variables.
 * Returns the configured PHPMailer instance for sending emails.
 * @return PHPMailer Configured PHPMailer instance.
 * @throws Exception If PHPMailer encounters an error during setup.
 */
function getMailer()
{
    $config = getEnvironmentConfig(); // Ambil config sesuai environment

    $mail = new PHPMailer(true);
    $mail->isSMTP();

    // Gunakan config dari environment yang sesuai
    $mail->Host = $config['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['MAIL_USERNAME'];
    $mail->Password = $config['MAIL_PASSWORD'];
    $mail->SMTPSecure = $config['MAIL_ENCRYPTION'];
    $mail->Port = $config['MAIL_PORT'];

    return $mail;
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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        session_regenerate_id(true);
    }
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
 * Logs out the current user by destroying the session and clearing related cookies.
 *
 * This function starts the session, clears session data, and removes cookies associated with the session 
 * and "remember me" functionality. It also handles legacy cookies like 'username' and 'password'.
 * 
 * @return string Message indicating the result of logout.
 */
function logoutUser()
{
    startSession();
    $config = getEnvironmentConfig();
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);

    $parsedUrl = parse_url($baseUrl); // Extract path from base URL
    $cookiePath = $parsedUrl['path'] ?? '/'; // Default path if not available

    $_SESSION = []; // Clear all session data

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params(); // Get current session cookie parameters
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy(); // Destroy the session data

    if (isset($_COOKIE['remember_me'])) {
        setcookie(
            'remember_me',
            '',
            [
                'expires' => Carbon::now()->subYears(5)->timestamp, // Set an expiration far in the past to delete the cookie
                'path' => $cookiePath,
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    $legacyCookies = ['username', 'password'];
    foreach ($legacyCookies as $cookie) {
        if (isset($_COOKIE[$cookie])) {
            setcookie(
                $cookie,
                '',
                [
                    'expires' => Carbon::now()->subYears(5)->timestamp, // Set expiration for legacy cookies
                    'path' => $cookiePath,
                    'domain' => $_SERVER['HTTP_HOST'],
                    'secure' => true,
                    'httponly' => true
                ]
            );
        }
    }

    return 'Logged out successfully.';
}

/**
 * Authenticates a user using their username or email and password.
 *
 * This function checks the user's credentials against the database.
 * If authentication is successful, it returns user data; otherwise, it returns an error status.
 *
 * @param string $login_id The username or email provided by the user.
 * @param string $password The password provided by the user.
 * @param array $config Database configuration.
 * @param string $env Environment (local/live).
 * @return array Returns an array with:
 *   - 'status' (string): 'success', 'account_not_activated', 'invalid_credentials', or 'error'.
 *   - 'message' (string, optional): Additional information in case of an error.
 *   - 'user' (array, optional): User data if authentication is successful.
 */
function loginUser($login_id, $password, $config, $env)
{
    $pdo = getPDOConnection($config, $env); // Establish database connection
    if (!$pdo) {
        return ['status' => 'error', 'message' => 'Database error']; // Return error if the connection fails
    }

    try {
        $query = "SELECT user_id, username, password, isactive FROM users WHERE username=:login_id OR email=:login_id";
        $stmt = $pdo->prepare($query); // Prepare SQL query
        $stmt->execute(['login_id' => $login_id]); // Execute query with user input
        $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch user data

        if ($user && password_verify($password, $user['password'])) { // Verify password
            if ($user['isactive'] == 1) {
                return ['status' => 'success', 'user' => $user]; // User is active, return success
            } else {
                return ['status' => 'account_not_activated']; // User account is not activated
            }
        }
        return ['status' => 'invalid_credentials']; // Return invalid credentials if authentication fails
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Internal error']; // Handle database error
    }
}

/**
 * Handles the user login process.
 *
 * This function attempts to authenticate the user using the provided login credentials.
 * If authentication is successful, it initializes the session (if not already started),
 * stores user information in session variables, and returns a success message.
 * Otherwise, it returns the corresponding error status.
 *
 * @param string $login_id The user's login identifier (username or email).
 * @param string $password The user's password.
 * @param array $config Database configuration.
 * @param string $env Environment (local/live).
 * @return string Returns 'Login successful.' if authentication succeeds; otherwise, returns an error message.
 */
function processLogin($login_id, $password, $config, $env)
{
    // Authenticate the user using the updated loginUser function
    $login_result = loginUser($login_id, $password, $config, $env);

    if ($login_result['status'] === 'success') {
        // Initialize the session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set session variables
        $_SESSION['user_logged_in'] = true; // Set session flag indicating successful login
        $_SESSION['username'] = $login_result['user']['username']; // Store the username in the session
        $_SESSION['user_id'] = $login_result['user']['user_id']; // Store the user ID in the session

        return 'Login successful.';
    } else {
        // Return the error status if login fails
        return $login_result['status'];
    }
}

/**
 * Sets a secure "Remember Me" token-based cookie for user authentication.
 * 
 * This function generates a cryptographically secure token, hashes it using bcrypt, 
 * stores the hash in the database along with an expiration timestamp, and then 
 * sets a secure HTTP-only cookie in the user's browser.
 * 
 * @param int $user_id The unique identifier of the user.
 * @param array $config Database configuration.
 * @param string $env Environment (local/live).
 * @return void
 */
function rememberMe($user_id, $config, $env)
{
    $token = bin2hex(random_bytes(32)); // Generate a cryptographically secure token.
    $hashedToken = password_hash($token, PASSWORD_BCRYPT); // Hash the token before storing it in the database.
    $expiryTime = Carbon::now()->addDays(30); // Set expiry time (30 days) using Carbon.
    $pdo = getPDOConnection($config, $env); // Get database connection using provided config and env.
    if (!$pdo)
        return; // Exit if the database connection fails.

    try {
        $stmt = $pdo->prepare("INSERT INTO remember_me_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':token_hash' => $hashedToken,
            ':expires_at' => $expiryTime->toDateTimeString() // Convert Carbon object to datetime string.
        ]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage()); // Log any database errors.
        return;
    }

    // Encode the user_id and token to store in the cookie.
    $cookieData = json_encode(['user_id' => $user_id, 'token' => $token]);

    // Set the "remember me" cookie with maximum security.
    setcookie('remember_me', $cookieData, [
        'expires' => $expiryTime->timestamp, // Use Carbon timestamp for cookie expiry.
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => true, // Ensures the cookie is sent over HTTPS.
        'httponly' => true, // Ensures the cookie is accessible only via HTTP and not JavaScript.
        'samesite' => 'Lax' // Limits cross-site cookie transmission to prevent CSRF attacks.
    ]);
}

/**
 * Attempts to log in the user automatically using the "remember me" cookie.
 * 
 * If a valid "remember_me" cookie is found, this function retrieves the stored token, 
 * verifies it against the database, and logs the user in by setting the session. 
 * If successful, it also refreshes the token for security and redirects the user.
 * 
 * @param array $config Database configuration.
 * @param string $env Environment (local/live).
 * @return string|null Returns an error message if the login attempt fails, or null if successful.
 */
function autoLogin($config, $env)
{
    if (!isset($_COOKIE['remember_me']))
        return null; // No "remember_me" cookie found.

    $cookieData = json_decode($_COOKIE['remember_me'], true);

    // Validate cookie structure to ensure it contains the required fields.
    if (!isset($cookieData['user_id']) || !isset($cookieData['token']))
        return 'Invalid cookie structure.';

    $user_id = $cookieData['user_id'];
    $token = $cookieData['token'];
    $pdo = getPDOConnection($config, $env); // Get database connection using provided config and env.
    if (!$pdo)
        return 'Database connection failed.';

    try {
        $stmt = $pdo->prepare("
            SELECT users.user_id, users.username, remember_me_tokens.token_hash 
            FROM remember_me_tokens
            JOIN users ON remember_me_tokens.user_id = users.user_id
            WHERE remember_me_tokens.user_id = :user_id 
            AND remember_me_tokens.expires_at > :now
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':now' => Carbon::now()->toDateTimeString() // Get current timestamp using Carbon.
        ]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage()); // Log database errors for debugging.
        return 'Database error during auto-login.';
    }

    // Verify the token by comparing the stored hash with the provided token.
    if ($tokenData && password_verify($token, $tokenData['token_hash'])) {
        $_SESSION['user_logged_in'] = true; // Set session variable to mark user as logged in.
        $_SESSION['username'] = $tokenData['username']; // Store username in session.

        rememberMe($user_id, $config, $env); // Refresh the "remember me" token for security.

        header("Location: index.php"); // Redirect to the main page.
        exit();
    }

    return 'Invalid or expired token.';
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
 * Sends an account activation email to a user.
 *
 * This function retrieves user information from the database, checks if the account is already active, 
 * updates the activation code expiration, constructs an activation link, and sends an activation email.
 *
 * @param string $userEmail The recipient's email address.
 * @param string $activationCode The activation code to be included in the email.
 * @param string|null $username Optional. The username of the user, used to fetch additional user data if provided.
 * @return mixed Returns true if the email is sent successfully, otherwise an error message.
 */
function sendActivationEmail($userEmail, $activationCode, $username = null)
{
    // Load configuration and determine the environment
    $config = getEnvironmentConfig();
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    // Establish a database connection
    $pdo = getPDOConnection($config, $env);
    if (!$pdo) {
        handleError("Database connection failed while sending activation email.", $env);
        return 'Database connection failed';
    }

    try {
        // Retrieve user data from the database based on email or username
        $query = "SELECT isactive, activation_expires_at FROM users WHERE " . ($username ? "username=:identifier" : "email=:identifier");
        $stmt = $pdo->prepare($query);
        $stmt->execute(['identifier' => $username ?? $userEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            handleError("User does not exist.", $env);
            return 'User does not exist.';
        }

        if ($user['isactive'] == 1) {
            return 'User is already active.';
        }

        // Update the activation expiration time
        $newActivationExpires = Carbon::now()->addHours(2);
        $updateQuery = "UPDATE users SET activation_expires_at=:activationExpires WHERE " . ($username ? "username" : "email") . "=:identifier";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([
            'activationExpires' => $newActivationExpires->toDateTimeString(),
            'identifier' => $username ?? $userEmail
        ]);

        // Construct the activation link
        $activationLink = rtrim($baseUrl, '/') . "/auth/activate.php?code=$activationCode";

        // Initialize the mailer
        $mail = getMailer();
        $mail->setFrom($config['MAIL_USERNAME'], 'Sarjana Canggih Indonesia');
        $mail->addAddress($userEmail);
        $mail->Subject = 'Aktivasi Akun Anda - Sarjana Canggih Indonesia';
        $mail->isHTML(true);

        // Email body in HTML format
        $mail->Body = '
        <div style="font-family:Helvetica,Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">
            <div style="text-align:center;margin-bottom:30px;">
                <img src="https://sarjanacanggihindonesia.com/assets/images/logoscblue.png" alt="Logo" style="max-width:90px;height:auto;">
            </div>
            <div style="background-color:#f8f9fa;padding:30px;border-radius:10px;">
                <h2 style="color:#2c3e50;margin-top:0;">Welcome to Sarjana Canggih Indonesia</h2>
                <p style="color:#4a5568;">Hello,</p>
                <p style="color:#4a5568;">Please click the button below to activate your account.</p>
                <div style="text-align:center;margin:30px 0;">
                    <a href="' . $activationLink . '" style="background-color:#3182ce;color:white;padding:12px 25px;border-radius:5px;text-decoration:none;display:inline-block;font-weight:bold;">Activate Account</a>
                </div>
                <p style="color:#4a5568;">If the button does not work, copy and paste this link into your browser:</p>
                <p style="word-break:break-all;color:#3182ce;">' . $activationLink . '</p>
                <p style="color:#e53e3e;margin-top:15px;border-left:4px solid #e53e3e;padding-left:10px;">
                    <strong>Important:</strong> If you did not register, please ignore this email.
                </p>
                <p style="color:#4a5568;margin-top:25px;">
                    For assistance, contact <a href="mailto:admin@sarjanacanggihindonesia.com" style="color:#3182ce;">admin@sarjanacanggihindonesia.com</a>
                </p>
            </div>
            <div style="text-align:center;margin-top:30px;color:#718096;font-size:12px;">
                <p>This email was sent to ' . htmlspecialchars($userEmail) . '</p>
            </div>
        </div>';

        // Plain text email alternative
        $mail->AltBody = "Activate Your Account - Sarjana Canggih Indonesia

        Hello,

        Thank you for joining us. Click the link below to activate your account:

        $activationLink        

        If the button does not work, copy and paste the link into your browser.

        **Important:** If you did not register, please ignore this email.         

        For assistance, contact: admin@sarjanacanggihindonesia.com

        This email was sent to: " . $userEmail;

        // Send the email
        if (!$mail->send()) {
            handleError('Mailer Error: ' . $mail->ErrorInfo, $env);
            return 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }
        return true;
    } catch (Exception $e) {
        handleError("Mailer Exception: " . $e->getMessage(), $env);
        return 'Mailer Error: ' . $e->getMessage();
    } catch (PDOException $e) {
        handleError("PDOException: " . $e->getMessage(), $env);
        return 'Database Error: ' . $e->getMessage();
    }
}

/**
 * Resends an activation email to a user based on their email or username.
 * 
 * This function retrieves the user's details from the database, generates or 
 * retrieves an activation code, constructs an activation link, and sends 
 * the activation email. It ensures that the activation code exists and updates 
 * the expiration time if necessary.
 * 
 * @param string $identifier The email or username of the user requesting activation.
 * @return string Message indicating the outcome of the process.
 */
function resendActivationEmail($identifier)
{
    $config = getEnvironmentConfig();
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    // Establish database connection
    $pdo = getPDOConnection($config, $env);
    if (!$pdo) {
        return 'An error occurred. Please try again later.';
    }

    try {
        // Determine if the identifier is an email or username and fetch user data
        $query = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? "SELECT username, email, activation_code, activation_expires_at, isactive FROM users WHERE email = :identifier"
            : "SELECT username, email, activation_code, activation_expires_at, isactive FROM users WHERE username = :identifier";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return 'User does not exist.';
        }

        if ($user['isactive'] == 1) {
            return 'User is already active.';
        }

        // Generate or retrieve activation code
        $activationCode = generateActivationCode($user['email']);
        $activationExpires = Carbon::now()->addHours(2);

        // Update activation code and expiration time in the database
        $updateQuery = "UPDATE users SET activation_code=:activation_code, activation_expires_at=:activation_expires_at WHERE " .
            (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? "email" : "username") . "=:identifier";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([
            'activation_code' => $activationCode,
            'activation_expires_at' => $activationExpires->format('Y-m-d H:i:s'),
            'identifier' => $identifier
        ]);

        // Construct activation link
        $activationLink = rtrim($baseUrl, '/') . "/auth/activate.php?code=$activationCode";

        // Prepare and send activation email
        $mail = getMailer();
        $mail->setFrom($config['MAIL_USERNAME'], 'Sarjana Canggih Indonesia');
        $mail->addAddress($user['email']);
        $mail->isHTML(true);
        $mail->Subject = 'Activate Your Account - Sarjana Canggih Indonesia';
        $mail->Body = '
        <div style="font-family: Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="https://sarjanacanggihindonesia.com/assets/images/logoscblue.png" alt="Sarjana Canggih Indonesia Logo" style="max-width: 90px; height: auto;">
            </div>
            <div style="background-color: #f8f9fa; padding: 30px; border-radius: 10px;">
                <h2 style="color: #2c3e50; margin-top: 0;">Activate Your Account</h2>
                <p style="color: #4a5568;">Hello,</p>
                <p style="color: #4a5568;">You are receiving this email because you requested a new activation link for your Sarjana Canggih Indonesia account.</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $activationLink . '" style="background-color: #3182ce; color: white; padding: 12px 25px; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold;">
                        Activate Account
                    </a>
                </div>
                <p style="color: #4a5568;">If the button does not work, copy and paste this link into your browser:</p>
                <p style="word-break: break-all; color: #3182ce;">' . $activationLink . '</p>
                <p style="color: #4a5568; margin-top: 25px;">
                    Need help? Contact our support team at <a href="mailto:admin@sarjanacanggihindonesia.com" style="color: #3182ce;">admin@sarjanacanggihindonesia.com</a>
                </p>
            </div>
            <div style="text-align: center; margin-top: 30px; color: #718096; font-size: 12px;">
                <p>This email was sent to ' . htmlspecialchars($user['email']) . '</p>
            </div>
        </div>';

        $mail->AltBody = "Activate Your Account - Sarjana Canggih Indonesia

        Hello,

        You are receiving this email because you requested a new activation link for your Sarjana Canggih Indonesia account.

        Please click the following link to activate your account:
        $activationLink

        If you cannot click the link, copy and paste it into your browser.

        Need help? Contact our support team at admin@sarjanacanggihindonesia.com

        This email was sent to " . $user['email'];

        if (!$mail->send()) {
            handleError('Mailer Error: ' . $mail->ErrorInfo, $env);
            return 'An error occurred while sending the email. Please try again later.';
        }

        return 'Activation email has been resent. Please check your inbox.';
    } catch (PDOException $e) {
        handleError("Database error: " . $e->getMessage(), $env);
        return 'An error occurred. Please try again later.';
    } catch (Exception $e) {
        handleError("Unexpected error: " . $e->getMessage(), $env);
        return 'An error occurred. Please try again later.';
    }
}

/**
 * Registers a new user in the system by validating input, checking for duplicates, hashing the password, 
 * generating an activation code, and storing the user in the database.
 *
 * @param string $username The username provided by the user.
 * @param string $email The email address provided by the user.
 * @param string $password The raw password provided by the user.
 * @param string $env The current environment (local or live).
 * @param array $config The configuration settings for database and application.
 * @return string Registration result message or activation code.
 */
function registerUser($username, $email, $password, $env, $config)
{
    $pdo = getPDOConnection($config, $env);
    if (!$pdo) {
        handleError('Database connection failed.', $env);
        return 'Internal server error. Please try again later.';
    }

    /** Start transaction using beginTransaction function */
    if (!beginTransaction($pdo, $env)) {
        return 'Internal server error. Please try again later.';
    }

    try {
        /** Validate username */
        $usernameViolations = validateUsername($username);
        if (count($usernameViolations) > 0) {
            $pdo->rollBack();
            return $usernameViolations[0]->getMessage();
        }

        /** Validate email */
        $emailViolations = validateEmail($email);
        if (count($emailViolations) > 0) {
            $pdo->rollBack();
            return $emailViolations[0]->getMessage();
        }

        /** Validate password */
        $passwordViolations = validatePassword($password);
        if (count($passwordViolations) > 0) {
            $pdo->rollBack();
            return $passwordViolations[0]->getMessage();
        }

        /** Check if username or email already exists */
        $checkQuery = "SELECT 1 FROM users WHERE username = :username OR email = :email";
        $stmt = executeQuery($pdo, $checkQuery, ['username' => $username, 'email' => $email], $env);
        if (!$stmt || $stmt->fetch()) {
            $pdo->rollBack();
            return 'Username or email already exists.';
        }

        /** Hash password securely */
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        if ($hashedPassword === false) {
            $pdo->rollBack();
            handleError('Password hashing failed.', $env);
            return 'Internal server error. Please try again later.';
        }

        /** Generate activation code */
        $activationCode = generateActivationCode($email);

        /** Get current timestamp */
        $createdAt = Carbon::now()->toDateTimeString();

        /** Insert new user into the database */
        $insertQuery = "INSERT INTO users (username, email, password, isactive, activation_code, created_at) 
                        VALUES (:username, :email, :password, 0, :activation_code, :created_at)";
        $stmt = executeQuery($pdo, $insertQuery, [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'activation_code' => $activationCode,
            'created_at' => $createdAt
        ], $env);

        /** Check if insertion was successful */
        if (!$stmt) {
            $pdo->rollBack();
            return 'Internal server error. Please try again later.';
        }

        /** Commit the transaction using commitTransaction function */
        if (!commitTransaction($pdo, $env)) {
            return 'Internal server error. Please try again later.';
        }

        return 'Registration successful. Please activate your account via email.';
    } catch (PDOException $e) {
        /** Rollback transaction if an error occurs */
        $pdo->rollBack();
        handleError('Database error: ' . $e->getMessage(), $env);
        return 'Internal server error. Please try again later.';
    }
}

/**
 * Handles user registration by validating input, processing registration, and sending activation emails.
 *
 * @param mixed  $client  The client instance used for reCAPTCHA verification.
 * @param string $baseUrl The base URL for redirection after processing.
 * @param array  $config  The configuration array for database and email settings.
 */
function handleRegistration($client, $baseUrl, $config)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        return;

    /** Validate CSRF and reCAPTCHA */
    $validationResult = validateCsrfAndRecaptcha($_POST, $client);
    if ($validationResult !== true) {
        $_SESSION['error_message'] = is_string($validationResult) ? $validationResult : 'Invalid CSRF token or reCAPTCHA. Please try again.';
        header("Location: " . $baseUrl . "register");
        exit();
    }

    /** Honeypot Field Check - Prevents bot submissions */
    if (!empty($_POST['honeypot'])) {
        $_SESSION['error_message'] = 'Bot detected. Submission rejected.';
        header("Location: " . $baseUrl . "register");
        exit();
    }

    /** Sanitize and validate form inputs */
    $username = sanitize_input(trim($_POST['username']));
    $email = sanitize_input(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    /** Check if password confirmation matches */
    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = 'Passwords do not match.';
        header("Location: " . $baseUrl . "register");
        exit();
    }

    /** Determine environment (local or live) */
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    /** Attempt to register the user */
    $pdo = getPDOConnection($config, $env);
    if (!$pdo) {
        $_SESSION['error_message'] = 'Database connection failed.';
        header("Location: " . $baseUrl . "register");
        exit();
    }

    if (!beginTransaction($pdo, $env)) {
        $_SESSION['error_message'] = 'Internal server error. Please try again later.';
        header("Location: " . $baseUrl . "register");
        exit();
    }

    try {
        $activationCode = registerUser($username, $email, $password, $env, $config);

        if (strpos($activationCode, 'Registration successful') !== false) {
            $_SESSION['success_message'] = $activationCode;
            $pdo->commit();
            header("Location: " . $baseUrl . "register");
            exit();
        }

        /** Send activation email */
        $activationResult = sendActivationEmail($email, $activationCode, $username);
        if (!$activationResult) {
            $stmt = executeQuery($pdo, "DELETE FROM users WHERE email = :email", ['email' => $email], $env);
            if (!$stmt) {
                handleError("Failed to delete user after email sending failure.", $env);
            }
            $pdo->commit();

            $_SESSION['error_message'] = 'Failed to send activation email. Please try again.';
            header("Location: " . $baseUrl . "register");
            exit();
        }

        /** Commit transaction */
        if (!commitTransaction($pdo, $env)) {
            $_SESSION['error_message'] = 'Internal server error. Please try again later.';
            header("Location: " . $baseUrl . "register");
            exit();
        }

        $_SESSION['success_message'] = "Registration successful! Please check your email to activate your account.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        handleError('Database error: ' . $e->getMessage(), $env);
        $_SESSION['error_message'] = 'Internal server error. Please try again later.';
    }

    /** Redirect to avoid form resubmission */
    header("Location: " . $baseUrl . "register");
    exit();
}

/**
 * Validates and processes the login form submission.
 * 
 * This function checks for honeypot field, validates CSRF token and reCAPTCHA response,
 * sanitizes and validates username/email and password, and processes the login.
 * 
 * @param string $env The environment configuration.
 * @param string $baseUrl The base URL for redirection after successful login.
 * @param array $config Database configuration.
 * @return void
 */
function processLoginForm($env, $baseUrl, $config)
{
    // Honeypot check
    if (!empty($_POST['honeypot'])) {
        $_SESSION['error_message'] = 'Bot detected. Submission rejected.';
        header("Location: " . $baseUrl . "login");
        exit();
    }

    // Validate CSRF and reCAPTCHA
    $client = HttpClient::create();
    $error_message = validateCsrfAndRecaptcha($_POST, $client);

    if ($error_message !== true) {
        $_SESSION['error_message'] = $error_message; // Error reCAPTCHA/CSRF
        header("Location: " . $baseUrl . "login");
        exit();
    }

    // Sanitize input
    $login_id = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
    $password = isset($_POST['password']) ? sanitize_input($_POST['password']) : '';

    // Validate login_id (username or email)
    $isEmail = filter_var($login_id, FILTER_VALIDATE_EMAIL);
    if ($isEmail) {
        $violations = validateEmail($login_id);
        $errorType = 'Email';
    } else {
        $violations = validateUsername($login_id);
        $errorType = 'Username';
    }

    if (count($violations) > 0) {
        $_SESSION['error_message'] = $errorType . ' tidak valid.';
        header("Location: " . $baseUrl . "login");
        exit();
    }

    // Validate password
    $passwordViolations = validatePassword($password);
    if (count($passwordViolations) > 0) {
        $_SESSION['error_message'] = 'Password tidak valid.';
        header("Location: " . $baseUrl . "login");
        exit();
    }

    // Process login
    $login_result = processLogin($login_id, $password, $config, $env);

    // Handle login result
    if ($login_result === 'Login successful.') {
        // Set remember me cookie if checked
        if (isset($_POST['rememberMe']) && isset($_SESSION['user_id'])) {
            rememberMe($_SESSION['user_id'], $config, $env);
        }
        header("Location: $baseUrl");
        exit();
    } else {
        // Map login result to error messages
        $error_messages = [
            'account_not_activated' => 'Akun Anda belum diaktifkan. Silakan cek email untuk link aktivasi.',
            'invalid_credentials' => 'Username/Email atau Password tidak sesuai.',
            'error' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.'
        ];
        $_SESSION['error_message'] = $error_messages[$login_result] ?? 'Login gagal. Silakan coba lagi.';
        header("Location: " . $baseUrl . "login");
        exit();
    }
}

/**
 * Retrieves user information based on the provided user ID.
 *
 * This function fetches detailed information about a user, including their basic user details (username, email, etc.) 
 * and additional profile details (first name, last name, phone, address, etc.) from the database.
 * 
 * @param int $userId The unique identifier of the user.
 * @param array $config Database configuration settings.
 * @param string $env Environment (local/live).
 * @return array|null An associative array containing user details, or null if the user is not found or an error occurs.
 */
function getUserInfo($userId, $config, $env)
{
    $pdo = getPDOConnection($config, $env); // Establish PDO connection to the database
    if (!$pdo) {
        return null; // Return null if database connection fails
    }

    try {
        $query = "SELECT 
                    u.user_id, u.username, u.email, u.role, u.isactive, 
                    up.first_name, up.last_name, up.phone, up.address, up.city, up.country, up.profile_image_filename 
                  FROM users u
                  LEFT JOIN user_profiles up ON u.user_id = up.user_id
                  WHERE u.user_id = :user_id";

        $stmt = $pdo->prepare($query); // Prepare SQL query
        $stmt->execute(['user_id' => $userId]); // Bind and execute query with user ID
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch user details as associative array

        return $userInfo ?: null; // Return user details or null if not found
    } catch (PDOException $e) {
        handleError('Database Error: ' . $e->getMessage(), $env); // Log error if query fails
        return null; // Return null if an error occurs
    }
}

/**
 * Returns the URL of the user's profile image.
 *
 * This function constructs the URL for the user's profile image based on the provided filename.
 * If no image filename is provided, it returns the default profile image URL. The base URL is determined 
 * based on the environment (local or live).
 *
 * @param string $imageFilename The filename of the user's profile image.
 * @param string $env The environment setting (local/live).
 * @param array $config Database configuration settings.
 * @return string|null The URL of the profile image or null in case of an error.
 */
function default_profile_image($imageFilename, $env, $config)
{
    $pdo = getPDOConnection($config, $env); // Establish PDO connection to the database
    if (!$pdo)
        return null; // Return null if PDO connection fails

    try {
        // Get environment configuration (if needed)
        $envConfig = getEnvironmentConfig();

        // Determine the base URL based on the environment
        if ($_SERVER['HTTP_HOST'] === 'localhost') { // Check if the environment is local
            $baseUrl = rtrim($envConfig['BASE_URL'], '/') . '/public_html/uploads/user_images/'; // Set base URL for local environment
        } else {
            $baseUrl = rtrim($envConfig['BASE_URL'], '/') . '/uploads/user_images/'; // Set base URL for live environment
        }

        // Return default profile image URL if no image filename is provided
        if (empty($imageFilename))
            return $baseUrl . 'default-profile.svg';

        // Return the URL for the provided profile image filename
        return $baseUrl . $imageFilename;
    } catch (PDOException $e) {
        error_log('Error: ' . $e->getMessage()); // Log the error if database query fails
        return null; // Return null if there is a database error
    }
}

/**
 * Activates a user account using an activation code.
 *
 * This function validates the provided activation code, checks if the account is already active,
 * verifies that the activation code has not expired, and then updates the user's status in the database.
 *
 * @param string $activationCode The activation code sent to the user.
 * @param string $env The environment setting for error handling.
 * @param array $config Database configuration settings.
 * @return string A message indicating the result of the activation process.
 */
function activateAccount($activationCode, $env, $config)
{
    define('DB_CONNECTION_FAILED', 'Database connection failed'); // Response message for DB connection failure
    define('ACCOUNT_ACTIVATED_SUCCESS', 'Account activated successfully.'); // Response message for successful activation
    define('INVALID_ACTIVATION_CODE', 'Invalid activation code.'); // Response message for invalid activation code
    define('ACTIVATION_CODE_EXPIRED', 'Activation failed: activation code has expired.'); // Response message when activation code expired
    define('ALREADY_ACTIVATED', 'Account is already activated.'); // Response message when account is already active
    define('ERROR_OCCURRED', 'Error: '); // General error message

    $activationCode = sanitize_input($activationCode); // Sanitize the activation code input
    if (strlen($activationCode) !== 64 || !ctype_xdigit($activationCode)) { // Validate activation code format
        handleError('Invalid activation code format: ' . $activationCode, $env); // Error handling for invalid format
        return INVALID_ACTIVATION_CODE;
    }

    $pdo = getPDOConnection($config, $env); // Establish database connection
    if (!$pdo) {
        handleError(DB_CONNECTION_FAILED, $env);
        return DB_CONNECTION_FAILED;
    } // Check DB connection

    try {
        $pdo->beginTransaction(); // Begin database transaction
        // Retrieve activation_expires_at and isactive values for the provided activation code (locking the row)
        $selectQuery = "SELECT activation_expires_at, isactive FROM users WHERE activation_code = :activation_code FOR UPDATE";
        $selectStmt = $pdo->prepare($selectQuery);
        $selectStmt->execute(['activation_code' => $activationCode]);
        $user = $selectStmt->fetch(PDO::FETCH_ASSOC); // Fetch user record

        if (!$user) { // If no user found for the activation code
            handleError('Invalid activation code: ' . $activationCode, $env);
            $pdo->rollBack(); // Rollback transaction
            return INVALID_ACTIVATION_CODE;
        }
        if ($user['isactive'] == 1) { // Check if account is already activated
            $pdo->rollBack(); // Rollback transaction
            return ALREADY_ACTIVATED;
        }
        // Parse the expiration time using Carbon and check if the activation code has expired
        $activationExpires = Carbon::parse($user['activation_expires_at']);
        if (Carbon::now()->greaterThan($activationExpires)) {
            $pdo->rollBack(); // Rollback if activation code expired
            return ACTIVATION_CODE_EXPIRED;
        }
        // Update the user's activation status in the database
        $updateQuery = "UPDATE users SET isactive = 1 WHERE activation_code = :activation_code";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute(['activation_code' => $activationCode]);
        if ($updateStmt->rowCount() === 0) { // Verify that the update affected a row
            handleError('No rows affected, invalid activation code: ' . $activationCode, $env);
            $pdo->rollBack(); // Rollback transaction on failure
            return INVALID_ACTIVATION_CODE;
        }
        $pdo->commit(); // Commit the transaction after successful update
        return ACCOUNT_ACTIVATED_SUCCESS;
    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback transaction on exception
        handleError('Database error: ' . $e->getMessage(), $env); // Handle DB errors
        return ERROR_OCCURRED . $e->getMessage();
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
 * @param array $config Database configuration settings.
 * @param string $baseUrl The base URL for generating reset links.
 * @return array Returns an array with 'status' (success/error) and 'message'.
 */
function processPasswordResetRequest($email_or_username, $recaptcha_response, $csrf_token, HttpClientInterface $httpClient, $config, $baseUrl)
{
    // Set the default timezone to Asia/Jakarta using Carbon
    Carbon::setToStringFormat('Y-m-d H:i:s');
    Carbon::setTestNow(Carbon::now('Asia/Jakarta'));

    // Set environment type based on host
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
    $pdo = getPDOConnection($config, $env);
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
    $expiresAt = Carbon::now('Asia/Jakarta')->addHour();

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
    $completedAt = Carbon::now('Asia/Jakarta')->toDateTimeString();

    // SQL query to mark the token as used by setting completed to 1 and updating completed_at
    $sql = "UPDATE password_resets 
            SET completed = 1, completed_at = :completed_at 
            WHERE hash = :hash";
    $stmt = $pdo->prepare($sql); // Prepare the SQL statement
    $stmt->execute([
        'hash' => $token,
        'completed_at' => $completedAt,
    ]);
}

/**
 * Handles the password reset process.
 *
 * This function validates the reset token, processes the POST request for password reset,
 * validates CSRF token and reCAPTCHA, updates the user's password, and redirects the user
 * to the login page with a success message upon successful password reset.
 *
 * @param string $token The password reset token from the URL.
 * @param PDO $pdo The PDO database connection object.
 * @param string $baseUrl The base URL of the application (e.g., "http://localhost/project/").
 * @return void
 */
function handlePasswordReset($token, $pdo, $baseUrl)
{
    // Validate the reset token to ensure it is valid and not expired
    $user = validateResetToken($token, $pdo);

    // Check if the request method is POST (form submission)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve form data
        $token = $_POST['token'] ?? ''; // Token from the hidden form field
        $csrf_token = $_POST['csrf_token'] ?? ''; // CSRF token from the form
        $new_password = $_POST['password'] ?? ''; // New password from the form

        // Validate the CSRF token to prevent CSRF attacks
        validateCSRFToken($csrf_token);

        // Validate reCAPTCHA response to ensure the request is from a human
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        $recaptcha_secret = RECAPTCHA_SECRET_KEY; // reCAPTCHA secret key
        $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha_response";
        $recaptcha_data = json_decode(file_get_contents($recaptcha_url));

        // Check if reCAPTCHA validation failed
        if (!$recaptcha_data->success) {
            die('reCAPTCHA validation failed.'); // Terminate the script with an error message
        }

        // Check if the user is valid (token is valid and not expired)
        if ($user) {
            // Hash the new password for secure storage
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the user's password in the database
            updateUserPassword($user['user_id'], $hashed_password, $pdo);

            // Mark the reset token as used to prevent reuse
            markTokenAsUsed($token, $pdo);

            // Redirect the user to the login page with a success message
            header("Location: " . $baseUrl . "login?message=Password+reset+successfully.");
            exit(); // Terminate the script after redirect
        } else {
            // If the token is invalid or expired, terminate the script with an error message
            die('Invalid or expired token.');
        }
    }
}

/**
 * Updates the email address of a user in the database.
 *
 * @param int $userId The ID of the user whose email is to be updated.
 * @param string $newEmail The new email address to set.
 * @param array $config Database configuration settings.
 * @param string $env Environment (local/live).
 * @return string Returns a success message or an error message if the update fails.
 */
function changeEmail($userId, $newEmail, $config, $env)
{
    // Validate the new email address
    $emailViolations = validateEmail($newEmail);
    if (count($emailViolations) > 0) {
        return $emailViolations[0]->getMessage();
    }

    // Establish a database connection
    $pdo = getPDOConnection($config, $env);
    if (!$pdo) {
        handleError('Database connection failed.', $env);
        return 'Database connection failed. Please try again later.';
    }

    try {
        // Check if the new email already exists in the database
        $checkQuery = "SELECT 1 FROM users WHERE email = :email AND user_id != :user_id";
        $stmt = executeQuery($pdo, $checkQuery, ['email' => $newEmail, 'user_id' => $userId], $env);
        if (!$stmt) {
            return 'An error occurred while checking the email address.';
        }

        if ($stmt->fetch()) {
            return 'The email address is already in use by another account.';
        }

        // Update the email address
        $updateQuery = "UPDATE users SET email = :email WHERE user_id = :user_id";
        $stmt = executeQuery($pdo, $updateQuery, ['email' => $newEmail, 'user_id' => $userId], $env);
        if (!$stmt) {
            return 'An error occurred while updating the email address.';
        }

        if ($stmt->rowCount() > 0) {
            return 'Email address updated successfully.';
        } else {
            return 'No changes were made. The email address may already be set to the provided value.';
        }
    } catch (PDOException $e) {
        handleError('Database error: ' . $e->getMessage(), $env);
        return 'An error occurred while updating the email address. Please try again later.';
    } finally {
        // Close the database connection
        closeConnection($pdo);
    }
}

/**
 * Changes the role of a user to a specified role in the database.
 *
 * This function updates the `role` column in the `users` table for a specific user ID.
 * It validates the new role against the allowed roles ('admin' or 'customer') and logs the action
 * in the `admin_activity_log` table for auditing purposes.
 *
 * @param int $admin_id The ID of the admin performing the action.
 * @param int $user_id The ID of the user whose role will be changed.
 * @param string $new_role The new role to assign to the user. Must be either 'admin' or 'customer'.
 * @param array $config The configuration array containing environment settings.
 * @param string $env The environment (local/live).
 * 
 * @return void
 * 
 * @throws Exception If an error occurs during the database operation or if the role is invalid.
 */
function changeUserRole($admin_id, $user_id, $new_role, $config, $env)
{
    // Mendapatkan koneksi database dengan parameter $config dan $env
    $pdo = getPDOConnection($config, $env);

    if (!$pdo) {
        handleError("Failed to establish database connection.", $env);
        return;
    }

    // Sanitasi input untuk mencegah serangan XSS
    $admin_id = sanitize_input($admin_id);
    $user_id = sanitize_input($user_id);
    $new_role = sanitize_input($new_role);

    // Validasi role yang baru
    if (!validateUserRole($new_role, $pdo, $env)) {
        return;
    }

    try {
        // Mengupdate role pengguna dalam database
        $sql = "UPDATE users SET role = :new_role WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':new_role', $new_role, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        // Mencatat aksi admin untuk keperluan audit
        logAdminAction($admin_id, 'change_role', 'users', $user_id, "Changed user role to $new_role for user ID $user_id", $config, $env);

        // Menampilkan pesan sukses (escaped untuk mencegah XSS)
        echo escapeHTML("User role successfully updated to $new_role.");
    } catch (PDOException $e) {
        // Menangani error eksekusi query
        handleError("SQL Error: " . $e->getMessage(), $env);
    }
}

/**
 * Deletes a user from the database and logs the action performed by an admin.
 *
 * This function removes a user from the `users` table based on the provided user ID.
 * It establishes a database connection, sanitizes input data, executes the deletion query,
 * and records the action in the `admin_activity_log` table for audit purposes.
 * If the process encounters an error, it is handled accordingly.
 *
 * @param int $admin_id The ID of the admin performing the action.
 * @param int $user_id The ID of the user to be deleted.
 * @param array $config The configuration array containing database and environment settings.
 * @param string $env The environment setting ('local' or 'live').
 * @return void
 */
function deleteUser($admin_id, $user_id, $config, $env)
{
    $pdo = getPDOConnection($config, $env); // Establish database connection

    if (!$pdo) {
        handleError("Failed to establish database connection.", $env); // Handle connection error
        return;
    }

    // Sanitize input to prevent XSS attacks
    $admin_id = sanitize_input($admin_id);
    $user_id = sanitize_input($user_id);

    try {
        // Prepare SQL query to delete the user
        $sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);

        // Bind parameters to ensure correct data types
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute(); // Execute deletion query

        // Log the admin action for auditing purposes
        logAdminAction($admin_id, 'delete_user', 'users', $user_id, "Deleted user with ID $user_id", $config, $env);

        echo escapeHTML("User successfully deleted."); // Display success message (escaped to prevent XSS)
    } catch (PDOException $e) {
        handleError("SQL Error: " . $e->getMessage(), $env); // Handle SQL execution errors
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