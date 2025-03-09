<?php
// get_products_by_category.php

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
    $categoryId = isset($_GET['category_id']) ? trim($_GET['category_id']) : null;
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    if ($categoryId !== null) {
        if (!ctype_digit($categoryId) || (int) $categoryId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid category_id. Must be a positive integer.'
            ]);
            exit();
        }
        $categoryId = (int) $categoryId;
    } else {
        $categoryId = null;
    }

    $pdo = getPDOConnection($config, $env);

    // Query untuk mengambil produk berdasarkan kategori dengan pagination
    $sql = "SELECT 
                p.product_id,
                p.product_name,
                p.price_amount,
                p.active,
                GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') AS categories
            FROM products p
            LEFT JOIN product_category_mapping pcm ON p.product_id = pcm.product_id
            LEFT JOIN product_categories pc ON pcm.category_id = pc.category_id
            WHERE (:category_id IS NULL OR pc.category_id = :category_id)
            GROUP BY p.product_id
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Iterate over each product in the $products array
    foreach ($products as &$product) {
        // Encode the 'product_id' using the Optimus library and store it in 'encoded_id'
        $product['encoded_id'] = $optimus->encode($product['product_id']);
    }

    // Hitung total produk untuk pagination
    $totalProducts = getTotalProductsByCategory($config, $env, $categoryId);
    $totalPages = ceil($totalProducts / $limit);

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

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}