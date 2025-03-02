<?php
// get_all_products.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/products/product_functions.php';
require_once __DIR__ . '/../config/auth/validate.php';

// Start session
startSession();

// Verifies the HTTP request method and ensures it matches the allowed method
verifyHttpMethod('GET');

// Set response headers for JSON output and CORS policy
configureApiHeaders();

try {
    // Ambil parameter pagination
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Fetch all products along with their categories and tags
    $products = getAllProductsWithCategoriesAndTags($config, $env, $limit, $offset);

    // Iterate over each product in the $products array
    foreach ($products as &$product) {
        // Encode the 'product_id' using the Optimus library and store it in 'encoded_id'
        $product['encoded_id'] = $optimus->encode($product['product_id']);
    }

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
