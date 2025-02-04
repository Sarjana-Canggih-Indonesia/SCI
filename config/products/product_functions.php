<?php
// product_functions.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/user_actions_config.php';
require_once __DIR__ . '/../auth/validate.php';

use voku\helper\AntiXSS;

/**
 * Sanitizes product data to prevent XSS attacks.
 *
 * This function sanitizes the name, description, and slug fields
 * by removing harmful HTML and special characters using AntiXSS.
 *
 * @param array $data The product data to sanitize.
 * @return array The sanitized product data.
 */
function sanitizeProductData($data)
{
    $antiXSS = new AntiXSS();

    // Sanitize the relevant fields
    $data['name'] = $antiXSS->xss_clean(trim($data['name']));
    $data['description'] = $antiXSS->xss_clean(trim($data['description']));
    $data['slug'] = strtolower(trim($data['slug']));  // Convert slug to lowercase

    return $data;
}

/**
 * Retrieves all products from the database.
 *
 * This function establishes a connection to the database using PDO,
 * executes a query to fetch all products from the 'products' table,
 * and returns the result as an associative array.
 *
 * @return array Returns an associative array containing all products.
 */
function getProducts()
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->query("SELECT * FROM products");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
    }
}

/**
 * Retrieves a single product by its ID from the database.
 *
 * This function establishes a connection to the database using PDO,
 * prepares and executes a query to fetch a product by its ID,
 * and returns the product data as an associative array.
 * If no product is found, it returns null.
 *
 * @param int $id The ID of the product to retrieve.
 * @return array|null Returns an associative array containing the product data, or null if no product is found.
 */
function getProductById($id)
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
    }
}

/**
 * Adds a new product to the database.
 *
 * This function establishes a connection to the database using PDO,
 * prepares an SQL query to insert a new product into the 'products' table,
 * and executes the query with the provided product data.
 * It returns true if the insertion is successful, otherwise false.
 *
 * @param array $data An associative array containing the product data (name, price, description, image_path, slug).
 * @return bool Returns true on successful insertion, otherwise false.
 */
function addProduct($data)
{
    // Validate product data
    $violations = validateProductData($data);

    // If there are any violations, handle the error
    if (count($violations) > 0) {
        // You can either throw an exception or return an error message
        handleError("Validation failed: " . implode(", ", array_map(fn($v) => $v->getMessage(), $violations)), getEnvironmentConfig()['local']);
        return false;
    }

    // Sanitize data using the sanitizeProductData function
    $data = sanitizeProductData($data);

    // Validate price
    try {
        $price = validatePrice($data['price']);
    } catch (\InvalidArgumentException $e) {
        handleError("Invalid price format: " . $e->getMessage(), getEnvironmentConfig()['local']);
        return false;
    }

    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("INSERT INTO products (product_name, price, description, image_path, slug) VALUES (:name, :price, :description, :image_path, :slug)");

        // Save the amount as an integer (cents) for storage
        return $stmt->execute([
            'name' => $data['name'],
            'price' => $price->getAmount(), // Store the amount in cents
            'description' => $data['description'],
            'image_path' => $data['image_path'],
            'slug' => $data['slug'],
        ]);
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
    }
}

/**
* Updates an existing product in the database by its ID.
*
* This function establishes a connection to the database using PDO,
* prepares an SQL query to update a product in the 'products' table,
* and executes the query with the provided product data and ID.
* It returns true if the update is successful, otherwise false.
*
* @param int $id The ID of the product to update.
* @param array $data An associative array containing the updated product data (name, price, description, image_path,
slug).
* @return bool Returns true on successful update, otherwise false.
*/
function updateProduct($id, $data)
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("UPDATE products SET product_name = :name, price = :price, description = :description, image_path
= :image_path, slug = :slug WHERE product_id = :id");
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'price' => $data['price'],
            'description' => $data['description'],
            'image_path' => $data['image_path'],
            'slug' => $data['slug'],
        ]);
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
    }
}

/**
 * Deletes a product from the database based on its ID.
 *
 * This function prepares and executes an SQL DELETE statement to remove a product from the 'products' table
 * using the provided product ID. It returns true if the deletion is successful, and false otherwise.
 *
 * @param int $id The ID of the product to delete.
 * @return bool True if the deletion is successful, false if the deletion fails.
 */
function deleteProduct($id)
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = :id");
        return $stmt->execute(['id' => $id]);
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
    }
}