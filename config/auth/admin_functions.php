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
        logAdminAction($admin_id, 'delete_user', 'users', $user_id, "Deleted user with ID $user_id");

        echo escapeHTML("User successfully deleted."); // Display success message (escaped to prevent XSS)
    } catch (PDOException $e) {
        handleError("SQL Error: " . $e->getMessage(), $env); // Handle SQL execution errors
    }
}

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