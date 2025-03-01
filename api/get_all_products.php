<?php
// get_all_products.php

// Include necessary configuration and product functions
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/products/product_functions.php';

// Set response headers for JSON output and CORS policy
header('Content-Type: application/json');
$allowedOrigin = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'http://localhost/SCI/' : 'https://sarjanacanggihindonesia.com';
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Ambil parameter pagination
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Fetch all products along with their categories and tags
    $products = getAllProductsWithCategoriesAndTags($config, $env, $limit, $offset);

    // Hitung total produk untuk pagination
    $totalProducts = getTotalProducts($config, $env);
    $totalPages = ceil($totalProducts / $limit);

    // Check if an error occurred while fetching data
    if (isset($products['error']) && $products['error']) {
        // Return an error response
        echo json_encode(['success' => false, 'message' => $products['message']]);
    } else {
        // Return the product data in JSON format
        echo json_encode([
            'success' => true,
            'products' => $products,
            'pagination' => [
                'total_products' => $totalProducts,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'limit' => $limit,
            ],
        ]);
    }
} catch (Exception $e) {
    // Handle exceptions and return a server error response
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
}
