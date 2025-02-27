<?php
// admin_functions.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/database-config.php';

// Memuat konfigurasi lingkungan
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

/**
 * Logs an administrative action into the `admin_activity_log` table.
 *
 * This function records administrative actions such as creating, updating, or deleting records.
 * It ensures input sanitization to prevent security vulnerabilities and handles errors dynamically 
 * based on the environment (local or live).
 *
 * @param int $admin_id The ID of the admin performing the action.
 * @param string $action The type of action performed (e.g., 'create_user', 'delete_record').
 * @param string|null $table_name The name of the affected database table (optional).
 * @param int|null $record_id The ID of the affected record (optional).
 * @param string|null $details Additional details about the action (optional).
 * @param array $config The configuration array containing database and environment settings.
 * @param string $env The environment setting ('local' or 'live').
 * @return void
 */
function logAdminAction($admin_id, $action, $table_name = null, $record_id = null, $details = null, $config, $env)
{
    $pdo = getPDOConnection($config, $env); // Establish database connection

    if (!$pdo) {
        handleError("Failed to establish database connection.", $env); // Handle connection error
        return;
    }

    // Sanitize input data to prevent XSS attacks
    $admin_id = sanitize_input($admin_id);
    $action = sanitize_input($action);
    $table_name = sanitize_input($table_name);
    $record_id = sanitize_input($record_id);
    $details = sanitize_input($details);

    try {
        // Prepare SQL query to insert the admin activity log
        $sql = "INSERT INTO admin_activity_log (admin_id, action, table_name, record_id, details) 
                VALUES (:admin_id, :action, :table_name, :record_id, :details)";
        $stmt = $pdo->prepare($sql);

        // Bind parameters to ensure proper data types
        $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $stmt->bindParam(':action', $action, PDO::PARAM_STR);
        $stmt->bindParam(':table_name', $table_name, PDO::PARAM_STR);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->bindParam(':details', $details, PDO::PARAM_STR);

        $stmt->execute(); // Execute the query

        echo escapeHTML("Log successfully recorded."); // Display success message (escaped to prevent XSS)
    } catch (PDOException $e) {
        handleError("SQL Error: " . $e->getMessage(), $env); // Handle SQL execution errors
    }
}

/**
 * Validates if the current user has the admin role and enforces access restrictions.
 * 
 * This function performs the following actions:
 * 1. Sets security headers to protect against common web vulnerabilities.
 * 2. Retrieves environment-specific configuration and base URL.
 * 3. Checks if the user is logged in by verifying the session.
 * 4. Retrieves user information from the database using the session user ID.
 * 5. Validates the existence of the user in the database.
 * 6. Ensures the user has the 'admin' role; otherwise, redirects to the login page.
 * 
 * @return void
 * @throws Exception If an error occurs in the local environment (for debugging purposes).
 */
function validateAdminRole()
{
    // Set security headers to prevent clickjacking, MIME type sniffing, and XSS attacks
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");

    // Retrieve environment-specific configuration and base URL
    $config = getEnvironmentConfig();
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
    $env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

    // Check if the user is logged in by verifying the session
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . $baseUrl . "login");
        exit();
    }

    // Retrieve user information from the database using the session user ID
    $userInfo = getUserInfo($_SESSION['user_id'], $config, $env);

    // Validate that the user exists in the database
    if (!$userInfo) {
        handleError("User not found in the database. Redirecting...", $env);
        exit();
    }

    // Ensure the user has the 'admin' role; otherwise, redirect to the login page
    if (!isset($userInfo['role']) || $userInfo['role'] !== 'admin') {
        handleError("Unauthorized access attempt", $env);
        header("Location: " . $baseUrl . "login");
        exit();
    }
}