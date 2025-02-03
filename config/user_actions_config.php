<?php
// user_actions_config.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/validate.php';

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\ConstraintViolationList;
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
 * Establishes a PDO database connection using environment-specific settings.
 *
 * This function retrieves database configuration details from the environment, 
 * connects to a MySQL database using PDO, sets the error mode to exceptions, 
 * and adjusts the MySQL session timezone.
 *
 * @return PDO|null Returns a PDO instance if the connection is successful; otherwise, returns null.
 */
function getPDOConnection()
{
    $config = getEnvironmentConfig();

    try {
        // Create a PDO connection using the database credentials
        $pdo = new PDO(
            "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}",
            $config['DB_USER'],
            $config['DB_PASS']
        );

        // Enable exception mode for better error handling
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Set the MySQL session timezone to Asia/Jakarta (+07:00)
        $pdo->exec("SET time_zone = '+07:00';");

        return $pdo;
    } catch (PDOException $e) {
        // Log the error and display a user-friendly message
        handleError(
            "Database Error: " . $e->getMessage(),
            ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'
        );

        echo 'Database Error: An error occurred. Please try again later.';
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
 * @return array Returns an array with:
 *   - 'status' (string): 'success', 'account_not_activated', 'invalid_credentials', or 'error'.
 *   - 'message' (string, optional): Additional information in case of an error.
 *   - 'user' (array, optional): User data if authentication is successful.
 */
function loginUser($login_id, $password)
{
    $pdo = getPDOConnection(); // Establish database connection
    if (!$pdo) {
        return ['status' => 'error', 'message' => 'Database error']; // Return error if the connection fails
    }

    try {
        $query = "SELECT user_id,username,password,isactive FROM users WHERE username=:login_id OR email=:login_id";
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
 * @return string Returns 'Login successful.' if authentication succeeds; otherwise, returns an error message.
 */
function processLogin($login_id, $password)
{
    $login_result = loginUser($login_id, $password); // Authenticate the user

    if ($login_result['status'] === 'success') {
        $_SESSION['user_logged_in'] = true; // Set session flag indicating successful login
        $_SESSION['username'] = $login_result['user']['username']; // Store the username in the session
        return 'Login successful.';
    } else {
        return $login_result['status']; // Return the error status if login fails
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
 * @return void
 */
function rememberMe($user_id)
{
    $token = bin2hex(random_bytes(32)); // Generate a cryptographically secure token.

    $hashedToken = password_hash($token, PASSWORD_BCRYPT); // Hash the token before storing it in the database.

    $expiryTime = Carbon::now()->addDays(30); // Set expiry time (30 days) using Carbon.

    $pdo = getPDOConnection(); // Get database connection.
    try {
        // Insert the hashed token and expiration time into the database.
        $stmt = $pdo->prepare("
            INSERT INTO remember_me_tokens 
            (user_id, token_hash, expires_at) 
            VALUES (:user_id, :token_hash, :expires_at)
        ");
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
    $cookieData = json_encode([
        'user_id' => $user_id,
        'token' => $token
    ]);

    // Set the "remember me" cookie with maximum security.
    setcookie(
        'remember_me',
        $cookieData,
        [
            'expires' => $expiryTime->timestamp, // Use Carbon timestamp for cookie expiry.
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => true, // Ensures the cookie is sent over HTTPS.
            'httponly' => true, // Ensures the cookie is accessible only via HTTP and not JavaScript.
            'samesite' => 'Lax' // Limits cross-site cookie transmission to prevent CSRF attacks.
        ]
    );
}

/**
 * Attempts to log in the user automatically using the "remember me" cookie.
 * 
 * If a valid "remember_me" cookie is found, this function retrieves the stored token, 
 * verifies it against the database, and logs the user in by setting the session. 
 * If successful, it also refreshes the token for security and redirects the user.
 * 
 * @return string|null Returns an error message if the login attempt fails, or null if successful.
 */
function autoLogin()
{
    if (isset($_COOKIE['remember_me'])) {
        $cookieData = json_decode($_COOKIE['remember_me'], true);

        // Validate cookie structure to ensure it contains the required fields.
        if (!isset($cookieData['user_id']) || !isset($cookieData['token'])) {
            return 'Invalid cookie structure.';
        }

        $user_id = $cookieData['user_id'];
        $token = $cookieData['token'];

        $pdo = getPDOConnection(); // Get database connection.
        try {
            // Fetch the stored hashed token for the user from the database.
            $stmt = $pdo->prepare("
                SELECT users.user_id,users.username,remember_me_tokens.token_hash 
                FROM remember_me_tokens
                JOIN users ON remember_me_tokens.user_id=users.user_id
                WHERE remember_me_tokens.user_id=:user_id 
                AND remember_me_tokens.expires_at>:now
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

            rememberMe($user_id); // Refresh the "remember me" token for security.

            header("Location: index.php"); // Redirect to the main page.
            exit();
        } else {
            return 'Invalid or expired token.';
        }
    }
    return null; // No "remember_me" cookie found.
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
 * Sends an account activation email to the user.
 *
 * This function retrieves user data, generates or updates the activation code expiration,
 * constructs the activation link, and sends an email with the activation instructions.
 *
 * @param string $userEmail The recipient's email address.
 * @param string $activationCode The activation code to be included in the email.
 * @param string|null $username Optional. The username of the user, used to fetch additional user data if provided.
 * @return mixed Returns true if the email is sent successfully, otherwise an error message.
 */
function sendActivationEmail($userEmail, $activationCode, $username = null)
{
    $config = getEnvironmentConfig(); // Load environment configuration
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']); // Get base URL for activation link
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'; // Determine environment

    $pdo = getPDOConnection(); // Establish database connection
    if (!$pdo) {
        handleError("Database connection failed while sending activation email.", $env);
        return 'Database connection failed';
    }

    try {
        $user = null;
        if ($username) {
            $query = "SELECT isactive,activation_expires_at FROM users WHERE username=:username";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $query = "SELECT isactive,activation_expires_at FROM users WHERE email=:email";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['email' => $userEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if (!$user) {
            handleError("User does not exist.", $env);
            return 'User does not exist.';
        }
        if ($user['isactive'] == 1)
            return 'User is already active.'; // Check if user is already activated

        $newActivationExpires = Carbon::now()->addHours(2); // Set new activation expiration time

        if ($username) {
            $updateQuery = "UPDATE users SET activation_expires_at=:activationExpires WHERE username=:identifier";
            $updateParams = ['activationExpires' => $newActivationExpires->toDateTimeString(), 'identifier' => $username];
        } else {
            $updateQuery = "UPDATE users SET activation_expires_at=:activationExpires WHERE email=:identifier";
            $updateParams = ['activationExpires' => $newActivationExpires->toDateTimeString(), 'identifier' => $userEmail];
        }
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute($updateParams);

        $activationExpires = $newActivationExpires; // Update expiration variable
        $activationLink = rtrim($baseUrl, '/') . "/auth/activate.php?code=$activationCode"; // Construct activation URL

        $mail = getMailer(); // Initialize mailer
        $mail->setFrom($config['MAIL_USERNAME'], 'Sarjana Canggih Indonesia');
        $mail->addAddress($userEmail);
        $mail->Subject = 'Aktivasi Akun Anda - Sarjana Canggih Indonesia';
        $mail->isHTML(true);

        $mail->Body = '
        <div style="font-family:Helvetica,Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">
            <div style="text-align:center;margin-bottom:30px;">
                <img src="https://sarjanacanggihindonesia.com/assets/images/logoscblue.png" alt="Logo" style="max-width:90px;height:auto;">
            </div>
            <div style="background-color:#f8f9fa;padding:30px;border-radius:10px;">
                <h2 style="color:#2c3e50;margin-top:0;">Selamat Datang di Sarjana Canggih Indonesia</h2>
                <p style="color:#4a5568;">Halo,</p>
                <p style="color:#4a5568;">Silakan klik tombol di bawah ini untuk mengaktifkan akun Anda.</p>
                <div style="text-align:center;margin:30px 0;">
                    <a href="' . $activationLink . '" style="background-color:#3182ce;color:white;padding:12px 25px;border-radius:5px;text-decoration:none;display:inline-block;font-weight:bold;">Aktifkan Akun</a>
                </div>
                <p style="color:#4a5568;">Jika tombol tidak berfungsi, salin tautan ini ke browser Anda:</p>
                <p style="word-break:break-all;color:#3182ce;">' . $activationLink . '</p>
                <p style="color:#e53e3e;margin-top:15px;border-left:4px solid #e53e3e;padding-left:10px;">
                    <strong>Penting:</strong> Jika Anda tidak mendaftar, abaikan email ini.
                </p>
                <p style="color:#4a5568;margin-top:25px;">
                    Untuk bantuan, hubungi <a href="mailto:admin@sarjanacanggihindonesia.com" style="color:#3182ce;">admin@sarjanacanggihindonesia.com</a>
                </p>
            </div>
            <div style="text-align:center;margin-top:30px;color:#718096;font-size:12px;">
                <p>Email ini dikirim ke ' . htmlspecialchars($userEmail) . '</p>
            </div>
        </div>';

        $mail->AltBody = "Aktivasi Akun Anda - Sarjana Canggih Indonesia

        Halo,

        Terima kasih telah bergabung. Klik tautan berikut untuk mengaktifkan akun Anda:

        $activationLink        

        Jika tombol tidak berfungsi, salin tautan di atas ke browser Anda.

        **Penting:** Jika Anda tidak melakukan registrasi, abaikan email ini.         

        Untuk bantuan, hubungi: admin@sarjanacanggihindonesia.com

        Email ini dikirim ke: " . $userEmail;

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

    $pdo = getPDOConnection();
    if (!$pdo) {
        handleError("Database connection failed when trying to resend activation email.", $env);
        return 'An error occurred. Please try again later.';
    }

    try {
        // Determine if the identifier is an email or username
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

        // Retrieve existing activation code or generate a new one
        $activationCode = generateActivationCode($user['email']);
        $activationExpires = Carbon::now()->addHours(2);
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

        $mail = getMailer();
        $mail->setFrom($config['MAIL_USERNAME'], 'Sarjana Canggih Indonesia');
        $mail->addAddress($user['email']);
        $mail->isHTML(true);
        $mail->Subject = 'Aktivasi Akun Anda - Sarjana Canggih Indonesia';
        $mail->Body = '
        <div style="font-family: Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="https://sarjanacanggihindonesia.com/assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia" style="max-width: 90px; height: auto;">
            </div>
            <div style="background-color: #f8f9fa; padding: 30px; border-radius: 10px;">
                <h2 style="color: #2c3e50; margin-top: 0;">Aktifkan Akun Anda</h2>
                <p style="color: #4a5568;">Halo,</p>
                <p style="color: #4a5568;">Anda menerima email ini karena Anda meminta link aktivasi baru untuk akun Sarjana Canggih Indonesia.</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $activationLink . '" style="background-color: #3182ce; color: white; padding: 12px 25px; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold;">
                        Aktifkan Akun
                    </a>
                </div>
                <p style="color: #4a5568;">Jika tombol tidak berfungsi, salin dan tempel link ini di browser Anda:</p>
                <p style="word-break: break-all; color: #3182ce;">' . $activationLink . '</p>
                <p style="color: #4a5568; margin-top: 25px;">
                    Butuh bantuan? Hubungi tim support kami di <a href="mailto:admin@sarjanacanggihindonesia.com" style="color: #3182ce;">admin@sarjanacanggihindonesia.com</a>
                </p>
            </div>
            <div style="text-align: center; margin-top: 30px; color: #718096; font-size: 12px;">
                <p>Email ini dikirim ke ' . htmlspecialchars($user['email']) . '</p>
            </div>
        </div>';

        $mail->AltBody = "Aktivasi Akun Anda - Sarjana Canggih Indonesia

        Halo,

        Anda menerima email ini karena meminta link aktivasi baru untuk akun Sarjana Canggih Indonesia.

        Silakan klik link berikut untuk mengaktifkan akun Anda:
        $activationLink

        Jika tidak bisa mengklik link, salin dan tempel ke address bar browser Anda.

        Butuh bantuan? Hubungi tim support kami di admin@sarjanacanggihindonesia.com

        Email ini dikirim ke " . $user['email'];

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
    $pdo = getPDOConnection();
    if (!$pdo) {
        handleError('Database connection failed.', $env);
        return 'Internal server error. Please try again later.';
    }

    try {
        // Validasi input
        $usernameViolations = validateUsername($username);
        if (count($usernameViolations) > 0)
            return $usernameViolations[0]->getMessage();

        $emailViolations = validateEmail($email);
        if (count($emailViolations) > 0)
            return $emailViolations[0]->getMessage();

        $passwordViolations = validatePassword($password);
        if (count($passwordViolations) > 0)
            return $passwordViolations[0]->getMessage();

        // Periksa apakah username atau email sudah ada
        $checkQuery = "SELECT 1 FROM users WHERE username = :username OR email = :email";
        $stmt = $pdo->prepare($checkQuery);
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch())
            return 'Username or email already exists.';

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        if ($hashedPassword === false) {
            handleError('Password hashing failed.', $env);
            return 'Internal server error. Please try again later.';
        }

        // Generate activation code
        $activationCode = generateActivationCode($email);

        // Gunakan Carbon untuk mencatat waktu
        $createdAt = Carbon::now()->toDateTimeString();

        // Insert user ke database
        $insertQuery = "INSERT INTO users (username, email, password, isactive, activation_code, created_at) 
                        VALUES (:username, :email, :password, 0, :activation_code, :created_at)";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'activation_code' => $activationCode,
            'created_at' => $createdAt
        ]);

        if ($stmt->rowCount() > 0) {
            return 'Registration successful. Please activate your account via email. Activation Code: ' . $activationCode;
        }

        return 'Registration failed. Please try again later.';
    } catch (PDOException $e) {
        handleError('Database error: ' . $e->getMessage(), $env);
        return 'Internal server error. Please try again later.';
    }
}

/**
 * Validates and processes the login form submission.
 * 
 * This function checks for honeypot field, validates CSRF token and reCAPTCHA response,
 * sanitizes and validates username/email and password, and processes the login.
 * 
 * @param string $env The environment configuration.
 * @param string $baseUrl The base URL for redirection after successful login.
 * @return void
 */
function processLoginForm($env, $baseUrl)
{
    // Honeypot check
    if (!empty($_POST['honeypot'])) {
        $_SESSION['error_message'] = 'Bot detected. Submission rejected.';
        header("Location: " . $baseUrl . "auth/login.php");
        exit();
    }

    // Validate CSRF and reCAPTCHA
    $client = HttpClient::create();
    $error_message = validateCsrfAndRecaptcha($_POST, $client);

    if ($error_message !== true) {
        $_SESSION['error_message'] = $error_message; // Error reCAPTCHA/CSRF
        header("Location: " . $baseUrl . "auth/login.php");
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
        header("Location: " . $baseUrl . "auth/login.php");
        exit();
    }

    // Validate password
    $passwordViolations = validatePassword($password);
    if (count($passwordViolations) > 0) {
        $_SESSION['error_message'] = 'Password tidak valid.';
        header("Location: " . $baseUrl . "auth/login.php");
        exit();
    }

    // Process login
    $login_result = processLogin($login_id, $password);

    // Handle login result
    if ($login_result === 'Login successful.') {
        // Set remember me cookie if checked
        if (isset($_POST['rememberMe'])) {
            rememberMe($login_result['user']['user_id']); // Gunakan user_id dari database
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
        header("Location: " . $baseUrl . "auth/login.php");
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
 * @return array|null An associative array containing user details, or null if the user is not found or an error occurs.
 */
function getUserInfo($userId)
{
    $pdo = getPDOConnection(); // Establish PDO connection to the database
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
        handleError('Database Error: ' . $e->getMessage(), 'live'); // Log error if query fails
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
 * @return string|null The URL of the profile image or null in case of an error.
 */
function default_profile_image($imageFilename)
{
    $pdo = getPDOConnection(); // Establish PDO connection to the database
    if (!$pdo)
        return null; // Return null if PDO connection fails

    try {
        $config = getEnvironmentConfig(); // Get environment configuration

        if ($_SERVER['HTTP_HOST'] === 'localhost') { // Check if the environment is local
            $baseUrl = rtrim($config['BASE_URL'], '/') . '/public_html/uploads/user_images/'; // Set base URL for local environment
        } else {
            $baseUrl = rtrim($config['BASE_URL'], '/') . '/uploads/user_images/'; // Set base URL for live environment
        }

        if (empty($imageFilename))
            return $baseUrl . 'default-profile.svg'; // Return default profile image URL if no image filename is provided

        return $baseUrl . $imageFilename; // Return the URL for the provided profile image filename
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
 * @return string A message indicating the result of the activation process.
 */
function activateAccount($activationCode)
{
    define('DB_CONNECTION_FAILED', 'Database connection failed'); // Response message for DB connection failure
    define('ACCOUNT_ACTIVATED_SUCCESS', 'Account activated successfully.'); // Response message for successful activation
    define('INVALID_ACTIVATION_CODE', 'Invalid activation code.'); // Response message for invalid activation code
    define('ACTIVATION_CODE_EXPIRED', 'Activation failed: activation code has expired.'); // Response message when activation code expired
    define('ALREADY_ACTIVATED', 'Account is already activated.'); // Response message when account is already active
    define('ERROR_OCCURRED', 'Error: '); // General error message

    $config = getEnvironmentConfig(); // Load environment configuration
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'; // Determine environment

    $activationCode = sanitize_input($activationCode); // Sanitize the activation code input
    if (strlen($activationCode) !== 64 || !ctype_xdigit($activationCode)) { // Validate activation code format
        handleError('Invalid activation code format: ' . $activationCode, $env); // Error handling for invalid format
        return INVALID_ACTIVATION_CODE;
    }
    $pdo = getPDOConnection(); // Establish database connection
    if (!$pdo) {
        handleError(DB_CONNECTION_FAILED, $env);
        return DB_CONNECTION_FAILED;
    } // Check DB connection

    try {
        $pdo->beginTransaction(); // Begin database transaction
        // Retrieve activation_expires_at and isactive values for the provided activation code (locking the row)
        $selectQuery = "SELECT activation_expires_at,isactive FROM users WHERE activation_code = :activation_code FOR UPDATE";
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
 * @return array Returns an array with 'status' (success/error) and 'message'.
 */
function processPasswordResetRequest($email_or_username, $recaptcha_response, $csrf_token, HttpClientInterface $httpClient, $config, $baseUrl)
{
    // Set the default timezone to Asia/Jakarta using Carbon
    Carbon::setToStringFormat('Y-m-d H:i:s');
    Carbon::setTestNow(Carbon::now('Asia/Jakarta'));

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

/**
 * Updates the email address of a user in the database.
 *
 * @param int $userId The ID of the user whose email is to be updated.
 * @param string $newEmail The new email address to set.
 * @return string Returns a success message or an error message if the update fails.
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