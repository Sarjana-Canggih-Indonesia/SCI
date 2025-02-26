<?php
// promo_functions.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../user_actions_config.php';
require_once __DIR__ . '/../auth/validate.php';

// Load variables
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';

/**
 * Retrieves all promos from the database.
 *
 * This function establishes a connection to the database using PDO,
 * executes a query to fetch all promos from the 'promos' table,
 * and returns the result as an associative array. If an error occurs,
 * it returns an array with an error message.
 *
 * @return array Returns an associative array containing all promos on success,
 *               or an array with an error message on failure.
 */
function getPromos($config, $env)
{
    try {
        $pdo = getPDOConnection($config, $env);
        $stmt = $pdo->query("SELECT * FROM promos");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log the error for debugging purposes
        handleError($e->getMessage(), $env);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Terjadi kesalahan saat mengambil data produk. Silakan hubungi admin.'
        ];
    }
}

/**
 * Retrieves a single promo by its ID from the database.
 *
 * This function establishes a connection to the database using PDO,
 * prepares and executes a query to fetch a promo by its ID,
 * and returns the promo data as an associative array.
 * If no promo is found, it returns null.
 *
 * @param int $id The ID of the promo to retrieve.
 * @return array|null Returns an associative array containing the promo data, or null if no promo is found.
 */
function getPromoById($id, $config, $env)
{
    try {
        $pdo = getPDOConnection($config, $env);
        $stmt = $pdo->prepare("SELECT * FROM promos WHERE promo_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        handleError($e->getMessage(), $env);
    }
}

/**
 * Retrieves all promo categories from the database.
 * 
 * This function connects to the database and fetches all records from the `promo_categories` table.
 * If an error occurs during the database query, it catches the exception and handles it appropriately.
 * The result is returned as an associative array.
 * 
 * @return array An associative array containing the promo categories data.
 */
function getPromoCategories($config, $env)
{
    try {
        $pdo = getPDOConnection($config, $env);
        $stmt = $pdo->query("SELECT * FROM promo_categories"); // Execute the query to fetch all promo categories
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Return the fetched data as an associative array
    } catch (Exception $e) {
        handleError($e->getMessage(), $env); // Handle errors if the query fails
        return []; // Return an empty array in case of an error
    }
}
