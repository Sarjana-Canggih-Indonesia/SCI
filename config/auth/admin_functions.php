<?php
// admin_functions.php

require_once __DIR__ . '/../config.php';

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
 * 
 * @return void
 * 
 * @throws Exception If an error occurs during the database operation or if the role is invalid.
 */
function changeUserRole($admin_id, $user_id, $new_role)
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

    // Sanitize inputs to prevent XSS attacks
    $admin_id = sanitize_input($admin_id);
    $user_id = sanitize_input($user_id);
    $new_role = sanitize_input($new_role);

    // Escape inputs to prevent SQL injection
    $admin_id = $conn->real_escape_string($admin_id);
    $user_id = $conn->real_escape_string($user_id);
    $new_role = $conn->real_escape_string($new_role);

    // Step 1: Validate the new role
    $allowed_roles = ['admin', 'customer']; // Allowed roles based on the `role` enum in the `users` table
    if (!in_array($new_role, $allowed_roles)) {
        $errorMessage = "Invalid role: $new_role. Allowed roles are: " . implode(", ", $allowed_roles);
        handleError($errorMessage, isLive() ? 'live' : 'local');
    }

    // Step 2: Update the user's role in the database
    $sql = "UPDATE users SET role = '$new_role' WHERE user_id = '$user_id'";

    if ($conn->query($sql) === TRUE) {
        // Log the admin action
        logAdminAction($admin_id, 'change_role', 'users', $user_id, "Changed user role to $new_role for user ID $user_id");

        // Output a success message (escaped to prevent XSS)
        echo escapeHTML("User role successfully updated to $new_role.");
    } else {
        // Handle query execution errors
        $errorMessage = "SQL Error: " . $sql . "<br>" . $conn->error;
        handleError($errorMessage, isLive() ? 'live' : 'local');
    }

    // Close the database connection
    $conn->close();
}

/**
 * Deletes a user from the database and logs the action in the admin activity log.
 * 
 * This function removes a user from the `users` table based on the provided user ID. 
 * It also records the action in the `admin_activity_log` table for auditing purposes.
 * The function first retrieves the environment-specific database configuration, establishes a connection, 
 * and then executes the deletion query. If the operation is successful, it logs the action; 
 * otherwise, it handles errors accordingly.
 * 
 * @param int $admin_id The ID of the admin performing the action.
 * @param int $user_id The ID of the user to be deleted.
 * @return void
 * @throws Exception If an error occurs during the database operation.
 */
function deleteUser($admin_id, $user_id)
{
    $config = getEnvironmentConfig(); // Get database configuration based on the environment

    $conn = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_NAME']); // Establish database connection

    if ($conn->connect_error) { // Check if the connection failed
        $errorMessage = "Database connection failed: " . $conn->connect_error;
        handleError($errorMessage, isLive() ? 'live' : 'local'); // Handle connection error
    }

    $admin_id = $conn->real_escape_string(sanitize_input($admin_id)); // Sanitize and escape admin ID input
    $user_id = $conn->real_escape_string(sanitize_input($user_id)); // Sanitize and escape user ID input

    $sql = "DELETE FROM users WHERE user_id = '$user_id'"; // SQL query to delete the user

    if ($conn->query($sql) === TRUE) { // Execute query and check if successful
        logAdminAction($admin_id, 'delete_user', 'users', $user_id, "Deleted user with ID $user_id"); // Log the deletion action

        echo escapeHTML("User successfully deleted."); // Display success message
    } else {
        $errorMessage = "Deletion failed: " . $conn->error;
        handleError($errorMessage, isLive() ? 'live' : 'local'); // Handle SQL execution error
    }

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

