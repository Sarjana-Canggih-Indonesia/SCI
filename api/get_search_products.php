<?php
// get_search_products.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/products/product_functions.php';

header('Content-Type: application/json');
$allowedOrigin = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'http://localhost/SCI/' : 'https://sarjanacanggihindonesia.com';
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Ambil keyword dari query string
    $keyword = $_GET['keyword'] ?? '';

    if (empty($keyword)) {
        echo json_encode(['success' => false, 'message' => 'Keyword is required']);
        exit;
    }

    // Panggil fungsi searchProducts
    $results = searchProducts($keyword);

    // Kembalikan hasil pencarian
    echo json_encode(['success' => true, 'products' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}