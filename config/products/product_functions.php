<?php
// product_functions.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/user_actions_config.php';

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
    // Establish a PDO connection to the database
    $pdo = getPDOConnection();

    // Execute a query to select all products from the 'products' table
    $stmt = $pdo->query("SELECT * FROM products");

    // Fetch and return all results as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    // Establish a PDO connection to the database
    $pdo = getPDOConnection();

    // Prepare a SQL query to select a product by its ID
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :id");

    // Execute the query with the provided product ID
    $stmt->execute(['id' => $id]);

    // Fetch and return the product data as an associative array
    return $stmt->fetch(PDO::FETCH_ASSOC);
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
    // Establish a PDO connection to the database
    $pdo = getPDOConnection();

    // Prepare an SQL query to insert a new product into the 'products' table
    $stmt = $pdo->prepare("INSERT INTO products (product_name, price, description, image_path, slug) VALUES (:name, :price, :description, :image_path, :slug)");

    // Execute the query with the provided product data and return the result
    return $stmt->execute([
        'name' => $data['name'], // Product name
        'price' => $data['price'], // Product price
        'description' => $data['description'], // Product description
        'image_path' => $data['image_path'], // Path to the product image
        'slug' => $data['slug'], // Product slug for SEO-friendly URLs
    ]);
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
 * @param array $data An associative array containing the updated product data (name, price, description, image_path, slug).
 * @return bool Returns true on successful update, otherwise false.
 */
function updateProduct($id, $data)
{
    // Establish a PDO connection to the database
    $pdo = getPDOConnection();

    // Prepare an SQL query to update the product in the 'products' table
    $stmt = $pdo->prepare("UPDATE products SET product_name = :name, price = :price, description = :description, image_path = :image_path, slug = :slug WHERE product_id = :id");

    // Execute the query with the provided product ID and updated data
    return $stmt->execute([
        'id' => $id, // Product ID to identify the record to update
        'name' => $data['name'], // Updated product name
        'price' => $data['price'], // Updated product price
        'description' => $data['description'], // Updated product description
        'image_path' => $data['image_path'], // Updated path to the product image
        'slug' => $data['slug'], // Updated product slug for SEO-friendly URLs
    ]);
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
    $pdo = getPDOConnection(); // Establish a connection to the database
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = :id"); // Prepare the DELETE query
    return $stmt->execute(['id' => $id]); // Execute the query and return whether it was successful
}
