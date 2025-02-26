<?php
// database-config.php

require_once __DIR__ . '/../config.php';

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
 * @param array $config Konfigurasi database.
 * @param string $env Environment (local/live).
 * @return PDO|null Returns a PDO instance if the connection is successful; otherwise, returns null.
 */
function getPDOConnection($config, $env)
{
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
        handleError("Database Error: " . $e->getMessage(), $env);

        echo 'Database Error: An error occurred. Please try again later.';
        return null;
    }
}

/**
 * Closes the PDO connection by setting it to null.
 * 
 * @param PDO|null $pdo The PDO object to be closed.
 */
function closeConnection($pdo = null)
{
    $pdo = null;
}

/**
 * Executes a prepared SQL query with bound parameters.
 * 
 * @param PDO $pdo The PDO database connection.
 * @param string $sql The SQL query to execute.
 * @param array $params An associative array of parameters to bind.
 * @param string $env Environment (local/live).
 * @return PDOStatement|null Returns the executed statement or null if an error occurs.
 */
function executeQuery($pdo, $sql, $params = [], $env)
{
    try {
        $stmt = $pdo->prepare($sql); // Prepare SQL query
        $stmt->execute($params); // Execute with bound parameters
        return $stmt; // Return executed statement
    } catch (PDOException $e) {
        handleError("Query Error: " . $e->getMessage(), $env); // Handle error using custom function
        return null; // Return null on failure
    }
}

/**
 * Checks if the database connection is active by running a simple query.
 * 
 * @param PDO $pdo The PDO database connection.
 * @param string $env Environment (local/live).
 * @return bool Returns true if the connection is active, otherwise false.
 */
function isDatabaseConnected($pdo, $env)
{
    try {
        $pdo->query("SELECT 1"); // Execute a simple query
        return true; // Connection is active
    } catch (PDOException $e) {
        handleError("Database Connection Check Error: " . $e->getMessage(), $env); // Handle connection error
        return false; // Connection is not active
    }
}

/**
 * Logs executed SQL queries for debugging purposes.
 * 
 * @param string $query The SQL query that was executed.
 * @param array $params The parameters bound to the query.
 */
function logQuery($query, $params = [])
{
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Query: $query | Params: " . json_encode($params); // Format log message
    file_put_contents('database.log', $logMessage . PHP_EOL, FILE_APPEND); // Append log to file
}

/**
 * Starts a new database transaction.
 * 
 * @param PDO $pdo The PDO database connection.
 * @param string $env Environment (local/live).
 * @return bool Returns true if the transaction starts successfully, otherwise false.
 */
function beginTransaction($pdo, $env)
{
    try {
        $pdo->beginTransaction(); // Start transaction
        return true; // Transaction started successfully
    } catch (PDOException $e) {
        handleError("Begin Transaction Error: " . $e->getMessage(), $env); // Handle transaction error
        return false; // Failed to start transaction
    }
}

/**
 * Commits the current database transaction.
 * 
 * @param PDO $pdo The PDO database connection.
 * @param string $env Environment (local/live).
 * @return bool Returns true if the commit is successful, otherwise false.
 */
function commitTransaction($pdo, $env)
{
    try {
        $pdo->commit(); // Commit transaction
        return true; // Transaction committed successfully
    } catch (PDOException $e) {
        handleError("Commit Transaction Error: " . $e->getMessage(), $env); // Handle commit error
        return false; // Failed to commit transaction
    }
}

/**
 * Fetches the list of unique user roles from the database.
 *
 * This function retrieves distinct role values from the `users` table, 
 * ensuring that only unique roles are returned. If an error occurs during 
 * database interaction, it logs the error and returns an empty array.
 *
 * @param PDO $pdo The active PDO database connection.
 * @param string $env The current environment ('local' or 'live'), used for error handling.
 * @return array An array containing distinct user roles from the database.
 */
function getAllowedRolesFromDB(PDO $pdo, string $env)
{
    try {
        // Prepare and execute the query to fetch distinct roles
        $stmt = $pdo->query("SELECT DISTINCT role FROM users");

        // Fetch and return the result as a single-column array
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Handle any database errors by logging and returning an empty array
        handleError("Database Error: " . $e->getMessage(), $env);
        return [];
    }
}
