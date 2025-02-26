<?php
// api/get_product_details.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/products/product_functions.php';

header('Content-Type: application/json');

try {
    // Ambil CSRF token dari header
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    validateCSRFToken($csrfToken);

    // Validasi product_id
    $productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);

    if (!$productId || $productId <= 0) {
        throw new Exception('Invalid Product ID');
    }

    $product = getProductWithDetails($productId, $config, $env);

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Format gambar dari string ke array
    $images = !empty($product['images']) ? explode(', ', $product['images']) : [];

    echo json_encode([
        'success' => true,
        'product' => [
            'name' => $product['product_name'],
            'description' => $product['description'],
            'price' => $product['price_amount'],
            'currency' => $product['currency'],
            'images' => $images,
            'categories' => $product['categories'] ?? 'Uncategorized',
            'tags' => $product['tags'] ?? 'No tags',
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $_ENV['ENVIRONMENT'] === 'local' ? $e->getTrace() : null
    ]);
}