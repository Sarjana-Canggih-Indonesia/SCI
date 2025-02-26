<?php
// admin_functions.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/database-config.php';

// Memuat konfigurasi lingkungan
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

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
    if (!validateUserRole($new_role)) {
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
        logAdminAction($admin_id, 'change_role', 'users', $user_id, "Changed user role to $new_role for user ID $user_id");

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
 * This function removes a specific user from the `users` table based on the provided user ID.
 * It also records the deletion in the `admin_activity_log` table for tracking purposes.
 * The function establishes a database connection, sanitizes the input data, executes 
 * the deletion query, and logs the action. If an error occurs during the process, 
 * an exception is thrown.
 * 
 * @param int $admin_id The ID of the admin performing the action.
 * @param int $user_id The ID of the user to be deleted.
 * @return void
 * @throws Exception If a database connection error or query failure occurs.
 */
function deleteUser($admin_id, $user_id)
{
    $config = getEnvironmentConfig(); // Retrieve environment-specific database configuration

    $conn = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_NAME']); // Establish database connection

    if ($conn->connect_error)
        throw new Exception("Database connection failed: " . $conn->connect_error); // Handle database connection error

    $admin_id = $conn->real_escape_string(sanitize_input($admin_id)); // Sanitize and escape admin ID input
    $user_id = $conn->real_escape_string(sanitize_input($user_id)); // Sanitize and escape user ID input

    $sql = "DELETE FROM users WHERE user_id = '$user_id'"; // SQL query to delete the user

    if (!$conn->query($sql)) { // Execute deletion query and check if successful
        $error = $conn->error;
        $conn->close();
        throw new Exception("Failed to delete user: " . $error); // Handle query failure
    }

    logAdminAction($admin_id, 'delete_user', 'users', $user_id, "Deleted user with ID $user_id"); // Log the deletion action

    $conn->close(); // Close database connection
}

/**
 * Logs an administrative action into the `admin_activity_log` table in the database.
 *
 * This function records administrative actions such as creating, updating, or deleting records.
 * It ensures that all inputs are sanitized to prevent XSS attacks and escaped to prevent SQL injection.
 * Additionally, it handles errors dynamically based on the environment (local or live).
 *
 * @param int $admin_id The ID of the admin performing the action.
 * @param string $action The type of action being performed (e.g., 'create_user', 'delete_record').
 * @param string|null $table_name The name of the database table affected by the action (optional).
 * @param int|null $record_id The ID of the record affected by the action (optional).
 * @param string|null $details Additional details about the action (optional).
 * 
 * @return void
 * 
 * @throws Exception If an error occurs and the environment is local, a detailed exception is thrown.
 *                  If the environment is live, the error is logged, and the script terminates.
 */
function logAdminAction($admin_id, $action, $table_name = null, $record_id = null, $details = null)
{
    // Retrieve environment-specific configuration (e.g., database credentials)
    $config = getEnvironmentConfig();

    // Establish a database connection using environment-specific credentials
    $servername = $config['DB_HOST'];
    $username = $config['DB_USER'];
    $password = $config['DB_PASS'];
    $dbname = $config['DB_NAME'];

    // Create a new MySQLi connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        $errorMessage = "Database connection failed: " . $conn->connect_error;
        handleError($errorMessage, isLive() ? 'live' : 'local');
    }

    // Sanitize all inputs to prevent XSS attacks
    $admin_id = sanitize_input($admin_id);
    $action = sanitize_input($action);
    $table_name = sanitize_input($table_name);
    $record_id = sanitize_input($record_id);
    $details = sanitize_input($details);

    // Escape inputs to prevent SQL injection
    $admin_id = $conn->real_escape_string($admin_id);
    $action = $conn->real_escape_string($action);
    $table_name = $conn->real_escape_string($table_name);
    $record_id = $conn->real_escape_string($record_id);
    $details = $conn->real_escape_string($details);

    // Construct the SQL query to insert the log into the `admin_activity_log` table
    $sql = "INSERT INTO admin_activity_log (admin_id, action, table_name, record_id, details) 
            VALUES ('$admin_id', '$action', '$table_name', '$record_id', '$details')";

    // Execute the SQL query
    if ($conn->query($sql) === TRUE) {
        // Output a success message (escaped to prevent XSS)
        echo escapeHTML("Log successfully recorded.");
    } else {
        // Handle query execution errors
        $errorMessage = "SQL Error: " . $sql . "<br>" . $conn->error;
        handleError($errorMessage, isLive() ? 'live' : 'local');
    }

    // Close the database connection
    $conn->close();
}

