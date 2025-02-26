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

// Validate the presence of a search keyword
if (!isset($_GET['keyword'])) {
    echo json_encode(["success" => false, "message" => "Keyword is required"]);
    exit;
}

$keyword = '%' . $_GET['keyword'] . '%';

try {
    // Establish a database connection
    $pdo = getPDOConnection($config, $env);

    // Prepare the SQL query with adjustments for the updated database structure
    $stmt = $pdo->prepare("
        SELECT 
            p.product_id, 
            p.product_name, 
            p.description, 
            p.created_at, 
            p.updated_at, 
            p.slug, 
            p.deleted_at, 
            p.price_amount, 
            p.currency,
            GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') AS categories,
            COALESCE(GROUP_CONCAT(DISTINCT pi.image_path ORDER BY pi.image_id SEPARATOR ','), '') AS images
        FROM products p
        LEFT JOIN product_category_mapping pcm ON p.product_id = pcm.product_id
        LEFT JOIN product_categories pc ON pcm.category_id = pc.category_id
        LEFT JOIN product_images pi ON p.product_id = pi.product_id
        WHERE p.product_name LIKE :keyword
        GROUP BY p.product_id
    ");

    // Bind the search parameter with wildcards for partial matching
    $searchKeyword = "%$keyword%";
    $stmt->bindParam(':keyword', $searchKeyword, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the data and process images
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert image string to an array
    foreach ($products as &$product) {
        $product['images'] = $product['images'] ? explode(',', $product['images']) : [];
    }
    unset($product); // Remove reference

    // Return the results as JSON
    echo json_encode([
        "success" => true,
        "products" => $products
    ]);

} catch (PDOException $e) {
    // Handle database errors
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}