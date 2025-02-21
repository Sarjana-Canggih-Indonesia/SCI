<?php
// delete_selected_products.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/products/product_functions.php';

header("Content-Type: application/json");

$config = getEnvironmentConfig();
$env = $config['is_live'] ? 'live' : 'local';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError('Metode request tidak diizinkan.', $env);
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    handleError('Gagal membaca data JSON.', $env);
}

if (!isset($data['product_ids']) || !is_array($data['product_ids'])) {
    handleError('Data produk tidak valid.', $env);
}

$deletedProducts = [];
$failedProducts = [];

foreach ($data['product_ids'] as $id) {
    $id = intval($id); // Pastikan ID adalah integer
    $result = deleteProduct($id);
    if ($result['error']) {
        $failedProducts[] = [
            'id' => $id,
            'message' => $result['message']
        ];
    } else {
        $deletedProducts[] = $id;
    }
}

$response = [
    'error' => !empty($failedProducts),
    'deleted_products' => $deletedProducts,
    'failed_products' => $failedProducts
];

echo json_encode($response);
exit;