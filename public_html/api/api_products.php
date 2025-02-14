<?php
// api/products.php

// 1. Include file yang diperlukan
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/products/product_functions.php';
require_once __DIR__ . '/../../config/products/tag_functions.php';

// 2. Header untuk Response JSON
header('Content-Type: application/json');

// 3. Ambil data produk
try {
    $products = getAllProductsWithCategoriesAndTags(); // Panggil fungsi Anda

    // 4. Tampilkan Hasil dalam Format JSON
    echo json_encode([
        'success' => true,
        'data' => $products
    ]);

} catch (Exception $e) {
    // 5. Handle Error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data produk: ' . $e->getMessage()
    ]);
}