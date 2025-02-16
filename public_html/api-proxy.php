<?php
// api-proxy.php

// Izinkan request dari domain yang sama (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Ambil query string dari request (misalnya, ?category_id=1)
$queryString = $_SERVER['QUERY_STRING'];

// Path ke file API di luar public_html
$apiFilePath = __DIR__ . '/../api/get_products_by_category.php';

// Pastikan file API ada
if (!file_exists($apiFilePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'API file not found']);
    exit;
}

// Teruskan query string ke file API
require_once $apiFilePath;