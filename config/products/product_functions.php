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
 * and returns the result as an associative array. If an error occurs,
 * it returns an array with an error message.
 *
 * @return array Returns an associative array containing all products on success,
 *               or an array with an error message on failure.
 */
function getProducts()
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->query("SELECT * FROM products");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log the error for debugging purposes
        handleError($e->getMessage(), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Terjadi kesalahan saat mengambil data produk. Silakan hubungi admin.'
        ];
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
 * It returns an array with a success message or an error message.
 *
 * @param array $data An associative array containing the product data (name, price, description, image_path, slug).
 * @return array Returns an array with a success message on successful insertion,
 *               or an array with an error message on failure.
 */
function addProduct($data)
{
    // Validate product data
    $violations = validateProductData($data);

    // If there are any violations, handle the error
    if (count($violations) > 0) {
        // Log the error for debugging purposes
        handleError("Validation failed: " . implode(", ", array_map(fn($v) => $v->getMessage(), $violations)), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Data produk tidak valid. Silakan periksa kembali data yang Anda masukkan.'
        ];
    }

    // Sanitize data using the sanitizeProductData function
    $data = sanitizeProductData($data);

    // Validate price
    try {
        $price = validatePrice($data['price']);
    } catch (\InvalidArgumentException $e) {
        // Log the error for debugging purposes
        handleError("Invalid price format: " . $e->getMessage(), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Format harga tidak valid. Harap masukkan harga yang benar.'
        ];
    }

    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("INSERT INTO products (product_name, price, description, image_path, slug) VALUES (:name, :price, :description, :image_path, :slug)");

        // Execute the query
        $success = $stmt->execute([
            'name' => $data['name'],
            'price' => $price->getAmount(), // Store the amount in cents
            'description' => $data['description'],
            'image_path' => $data['image_path'],
            'slug' => $data['slug'],
        ]);

        if ($success) {
            return [
                'error' => false,
                'message' => 'Produk berhasil ditambahkan.'
            ];
        } else {
            return [
                'error' => true,
                'message' => 'Gagal menambahkan produk. Silakan coba lagi nanti.'
            ];
        }
    } catch (Exception $e) {
        // Log the error for debugging purposes
        handleError($e->getMessage(), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Terjadi kesalahan saat menambahkan produk. Silakan hubungi admin.'
        ];
    }
}

/**
 * Updates an existing product in the database by its ID.
 *
 * This function establishes a connection to the database using PDO,
 * prepares an SQL query to update a product in the 'products' table,
 * and executes the query with the provided product data and ID.
 * It returns an array with a success message or an error message.
 *
 * @param int $id The ID of the product to update.
 * @param array $data An associative array containing the updated product data (name, price, description, image_path, slug).
 * @return array Returns an array with a success message on successful update,
 *               or an array with an error message on failure.
 */
function updateProduct($id, $data)
{
    // Validate product data
    $violations = validateProductData($data);

    // If there are any violations, handle the error
    if (count($violations) > 0) {
        // Log the error for debugging purposes
        handleError("Validation failed: " . implode(", ", array_map(fn($v) => $v->getMessage(), $violations)), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Data produk tidak valid. Silakan periksa kembali data yang Anda masukkan.'
        ];
    }

    // Sanitize data using the sanitizeProductData function
    $data = sanitizeProductData($data);

    // Validate price
    try {
        $price = validatePrice($data['price']);
    } catch (\InvalidArgumentException $e) {
        // Log the error for debugging purposes
        handleError("Invalid price format: " . $e->getMessage(), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Format harga tidak valid. Harap masukkan harga yang benar.'
        ];
    }

    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("UPDATE products SET product_name = :name, price = :price, description = :description, image_path = :image_path, slug = :slug WHERE product_id = :id");

        // Execute the query
        $success = $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'price' => $price->getAmount(), // Store the amount in cents
            'description' => $data['description'],
            'image_path' => $data['image_path'],
            'slug' => $data['slug'],
        ]);

        if ($success) {
            return [
                'error' => false,
                'message' => 'Produk berhasil diperbarui.'
            ];
        } else {
            return [
                'error' => true,
                'message' => 'Gagal memperbarui produk. Silakan coba lagi nanti.'
            ];
        }
    } catch (Exception $e) {
        // Log the error for debugging purposes
        handleError($e->getMessage(), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Terjadi kesalahan saat memperbarui produk. Silakan hubungi admin.'
        ];
    }
}

/**
 * Deletes a product from the database based on its ID.
 *
 * This function prepares and executes an SQL DELETE statement to remove a product from the 'products' table
 * using the provided product ID. It returns an array with a success message or an error message.
 *
 * @param int $id The ID of the product to delete.
 * @return array Returns an array with a success message on successful deletion,
 *               or an array with an error message on failure.
 */
function deleteProduct($id)
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = :id");

        // Execute the query
        $success = $stmt->execute(['id' => $id]);

        if ($success) {
            return [
                'error' => false,
                'message' => 'Produk berhasil dihapus.'
            ];
        } else {
            return [
                'error' => true,
                'message' => 'Gagal menghapus produk. Produk tidak ditemukan atau sudah dihapus.'
            ];
        }
    } catch (Exception $e) {
        // Log the error for debugging purposes
        handleError($e->getMessage(), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Terjadi kesalahan saat menghapus produk. Silakan hubungi admin.'
        ];
    }
}