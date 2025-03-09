<?php
// get_search_products.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/products/product_functions.php';
require_once __DIR__ . '/../config/auth/validate.php';

// Start session
startSession();

// Verifies the HTTP request method and ensures it matches the allowed method
verifyHttpMethod('GET');

// Set response headers for JSON output and CORS policy
configureApiHeaders();

// Validate the presence of a search keyword
if (!isset($_GET['keyword'])) {
    echo json_encode(["success" => false, "message" => "Keyword is required"]);
    exit;
}

$keyword = '%' . $_GET['keyword'] . '%';
$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    // Establish a database connection
    $pdo = getPDOConnection($config, $env);

    // Prepare the SQL query with adjustments for the updated database structure
    $sql = "
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
            p.active, 
            GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') AS categories,
            COALESCE(GROUP_CONCAT(DISTINCT pi.image_path ORDER BY pi.image_id SEPARATOR ','), '') AS images
        FROM products p
        LEFT JOIN product_category_mapping pcm ON p.product_id = pcm.product_id
        LEFT JOIN product_categories pc ON pcm.category_id = pc.category_id
        LEFT JOIN product_images pi ON p.product_id = pi.product_id
        WHERE p.product_name LIKE :keyword
        AND (:category_id IS NULL OR pc.category_id = :category_id)
        GROUP BY p.product_id
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
    $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch the data and process images
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert image string to an array and encode the product ID
    foreach ($products as &$product) {
        $product['images'] = $product['images'] ? explode(',', $product['images']) : [];
        $product['encoded_id'] = $optimus->encode($product['product_id']);
    }
    unset($product); // Remove reference    

    // Hitung total produk untuk pagination
    $totalProducts = getTotalProductsByKeywordAndCategory($config, $env, $keyword, $categoryId);
    $totalPages = ceil($totalProducts / $limit);

    // Return the results as JSON
    echo json_encode([
        "success" => true,
        "products" => $products,
        "pagination" => [
            "total_products" => $totalProducts,
            "total_pages" => $totalPages,
            "current_page" => $page,
            "limit" => $limit,
        ],
    ]);

} catch (PDOException $e) {
    // Handle database errors
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}