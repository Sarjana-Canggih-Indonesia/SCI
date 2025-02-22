<?php
// api-proxy.php

// Izinkan request dari domain yang sama (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Ambil parameter 'action' untuk menentukan API mana yang akan dipanggil
$action = $_GET['action'] ?? '';

$allowedActions = [
    'get_all_products' => '../api/get_all_products.php',
    'get_search_products' => '../api/get_search_products.php',
    'get_products_by_category' => '../api/get_products_by_category.php',
    'delete_selected_products' => '../api/delete_selected_products.php',
    'get_product_details' => '../api/get_product_details.php',
];

// Periksa apakah action valid
if (!isset($allowedActions[$action])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Path ke file API yang sesuai
$apiFilePath = $allowedActions[$action];

// Pastikan file API ada
if (!file_exists($apiFilePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'API file not found']);
    exit;
}

// Teruskan query string ke file API
require_once $apiFilePath;