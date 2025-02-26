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