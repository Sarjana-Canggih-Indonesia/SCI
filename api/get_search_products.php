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
    $pdo = getPDOConnection();

    // Prepare the SQL query to search for products by name
    $stmt = $pdo->prepare("
        SELECT 
            p.product_id, 
            p.product_name, 
            p.description, 
            p.created_at, 
            p.updated_at, 
            p.image_path, 
            p.slug, 
            p.deleted_at, 
            p.price_amount, 
            p.currency,
            GROUP_CONCAT(pc.category_name SEPARATOR ', ') AS categories
        FROM products p
        LEFT JOIN product_category_mapping pcm ON p.product_id = pcm.product_id
        LEFT JOIN product_categories pc ON pcm.category_id = pc.category_id
        WHERE p.product_name LIKE :keyword
        GROUP BY p.product_id
    ");

    // Bind the keyword parameter to the query
    $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the matching products
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the search results as JSON
    echo json_encode(["success" => true, "products" => $products]);

} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}