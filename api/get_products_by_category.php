<?php
// fetch_products.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/products/product_functions.php';

// Start session sebelum header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
    exit();
}

header('Content-Type: application/json');
$allowedOrigin = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'http://localhost/SCI/' : 'https://sarjanacanggihindonesia.com';
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

try {
    $categoryId = isset($_GET['category_id']) ? trim($_GET['category_id']) : null;

    if ($categoryId !== null) {
        if (!ctype_digit($categoryId) || (int) $categoryId <= 0) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'success' => false,
                'message' => 'Invalid category_id. Must be a positive integer.'
            ]);
            exit();
        }
        $categoryId = (int) $categoryId; // Konversi setelah validasi sukses
    } else {
        $categoryId = null;
    }

    $pdo = getPDOConnection();

    $sql = "SELECT 
                p.product_id,
                p.product_name,
                p.price_amount,
                GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') AS categories
            FROM products p
            LEFT JOIN product_category_mapping pcm ON p.product_id = pcm.product_id
            LEFT JOIN product_categories pc ON pcm.category_id = pc.category_id
            WHERE (:category_id IS NULL OR pc.category_id = :category_id)
            GROUP BY p.product_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['category_id' => $categoryId]);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}