<?php
// product_functions.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../user_actions_config.php';
require_once __DIR__ . '/../auth/validate.php';

use voku\helper\AntiXSS;
use Brick\Money\Money;
use Brick\Money\Currency;
use Brick\Money\Context\CustomContext;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Math\RoundingMode;

/**
 * Sanitizes product data to ensure it is safe for storage and processing.
 *
 * This function performs the following sanitization steps:
 * - Removes leading and trailing spaces from all string inputs.
 * - Prevents XSS attacks by cleaning the `name` and `description` fields.
 * - Converts `price_amount` to an integer to ensure numerical consistency.
 * - Converts `currency` to uppercase for standardization.
 * - Converts `slug` to lowercase to maintain URL consistency.
 *
 * @param array $data The raw product data to be sanitized.
 * @return array The sanitized product data.
 */
function sanitizeProductData($data)
{
    $antiXSS = new AntiXSS(); // Initialize the XSS protection library.

    // Sanitize the name field by trimming spaces and removing potential XSS content.
    $data['name'] = $antiXSS->xss_clean(trim($data['name']));
    // Ensure price_amount is stored as an integer for consistency.
    $data['price_amount'] = (int) $data['price_amount'];
    // Standardize currency format by converting to uppercase.
    $data['currency'] = strtoupper(trim($data['currency']));
    // Sanitize the description field by trimming spaces and removing potential XSS content.
    $data['description'] = $antiXSS->xss_clean(trim($data['description']));
    // Standardize the slug by converting it to lowercase.
    $data['slug'] = strtolower(trim($data['slug']));

    return $data; // Return the sanitized product data.
}

/**
 * Generates a URL-friendly slug from a product name.
 *
 * This function converts a given product name into a slug format by:
 * - Trimming unnecessary whitespaces
 * - Transliterating non-ASCII characters to ASCII equivalents
 * - Converting to lowercase
 * - Replacing non-alphanumeric characters with hyphens
 * - Removing duplicate and trailing hyphens
 * - Ensuring the slug is not empty, defaulting to "untitled" if needed
 * - Escaping the output for safe use in HTML
 *
 * @param string $productName The original product name to be converted.
 * @return string The generated slug.
 */
function generateSlug(string $productName): string
{
    $productName = trim($productName);
    if ($productName === '') {
        return 'untitled';
    }

    // Transliterate non-ASCII characters to ASCII
    $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII;', $productName);

    // Convert to lowercase using multibyte function
    $slug = mb_strtolower($transliterated, 'UTF-8');

    // Replace any non-alphanumeric characters (except hyphens) with hyphens
    $slug = preg_replace('/[^a-z0-9-]/u', '-', $slug);

    // Remove duplicate hyphens
    $slug = preg_replace('/-+/', '-', $slug);

    // Trim hyphens from the start and end
    $slug = trim($slug, '-');

    // Ensure a valid slug and escape output
    return $slug === '' ? 'untitled' : htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
}

/**
 * Retrieves all products from the database.
 *
 * This function establishes a connection to the database using PDO,
 * executes a query to fetch all products from the 'products' table,
 * and returns the result as an associative array. If an error occurs,
 * it returns an array with an error message.
 *
 * @return array Returns an associative array containing all products on success,
 *               or an array with an error message on failure.
 */
function getProducts()
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->query("SELECT * FROM products");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log the error for debugging purposes
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local');

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Terjadi kesalahan saat mengambil data produk. Silakan hubungi admin.'
        ];
    }
}

/**
 * Retrieves a single product by its ID from the database.
 *
 * This function establishes a connection to the database using PDO,
 * prepares and executes a query to fetch a product by its ID,
 * and returns the product data as an associative array.
 * If no product is found, it returns null.
 *
 * @param int $id The ID of the product to retrieve.
 * @return array|null Returns an associative array containing the product data, or null if no product is found.
 */
function getProductById($id)
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local');
    }
}

/**
 * Retrieves all product categories from the database.
 * 
 * This function connects to the database and fetches all records from the `product_categories` table.
 * If an error occurs during the database query, it catches the exception and handles it appropriately.
 * The result is returned as an associative array.
 * 
 * @return array An associative array containing the product categories data.
 */
function getProductCategories()
{
    try {
        $pdo = getPDOConnection(); // Get the PDO database connection
        $stmt = $pdo->query("SELECT * FROM product_categories"); // Execute the query to fetch all product categories
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Return the fetched data as an associative array
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Handle errors if the query fails
        return []; // Return an empty array in case of an error
    }
}

/**
 * Retrieves all products with their associated categories, tags, and images.
 *
 * This function performs a database query to fetch all products along with:
 * - Their associated images (concatenated as a string).
 * - Their associated categories (concatenated as a string).
 * - Their associated tags (concatenated as a string).
 * 
 * The data is retrieved using LEFT JOINs to ensure products without images, categories, or tags are still included.
 * The results are grouped by product ID to avoid duplicate entries.
 *
 * @return array Returns an array of products, each containing product details, categories, tags, and images.
 */
function getAllProductsWithCategoriesAndTags()
{
    try {
        $pdo = getPDOConnection(); // Establish a database connection.

        // SQL query to fetch products with categories, tags, and images.
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.slug,
                    p.description,
                    p.price_amount,
                    p.currency,
                    GROUP_CONCAT(DISTINCT pi.image_path ORDER BY pi.image_id SEPARATOR ', ') AS images,
                    p.created_at,
                    p.updated_at,
                    GROUP_CONCAT(DISTINCT pc.category_name ORDER BY pc.category_name SEPARATOR ', ') AS categories,
                    GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM 
                    products p
                LEFT JOIN 
                    product_images pi ON p.product_id = pi.product_id
                LEFT JOIN 
                    product_category_mapping pcm ON p.product_id = pcm.product_id
                LEFT JOIN 
                    product_categories pc ON pcm.category_id = pc.category_id
                LEFT JOIN 
                    product_tag_mapping ptm ON p.product_id = ptm.product_id
                LEFT JOIN 
                    tags t ON ptm.tag_id = t.tag_id
                GROUP BY 
                    p.product_id";

        $stmt = $pdo->query($sql); // Execute the query.
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch and return results as an associative array.

    } catch (Exception $e) {
        // Handle errors by logging them and returning an empty array.
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local');
        return [];
    }
}

/**
 * Retrieves detailed information of a product, including images, categories, and tags.
 *
 * This function queries the database to fetch a product's details and aggregates related images, 
 * categories, and tags using GROUP_CONCAT. Additionally, it converts the tags field from a 
 * comma-separated string to an array for easier processing.
 *
 * @param int $productId The unique identifier of the product.
 * @return array|null Returns an associative array containing product details or null if an error occurs.
 */
function getProductWithDetails($productId)
{
    try {
        $pdo = getPDOConnection(); // Establish a PDO connection

        $sql = "SELECT 
                    p.product_id, 
                    p.product_name, 
                    p.description, 
                    p.price_amount, 
                    p.currency, 
                    GROUP_CONCAT(DISTINCT pi.image_path ORDER BY pi.image_id SEPARATOR ', ') AS images, 
                    p.created_at, 
                    p.updated_at, 
                    GROUP_CONCAT(DISTINCT pc.category_id ORDER BY pc.category_id SEPARATOR ', ') AS category_ids, 
                    GROUP_CONCAT(DISTINCT pc.category_name ORDER BY pc.category_name SEPARATOR ', ') AS categories, 
                    GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags 
                FROM products p 
                LEFT JOIN product_images pi ON p.product_id = pi.product_id 
                LEFT JOIN product_category_mapping pcm ON p.product_id = pcm.product_id 
                LEFT JOIN product_categories pc ON pcm.category_id = pc.category_id 
                LEFT JOIN product_tag_mapping ptm ON p.product_id = ptm.product_id 
                LEFT JOIN tags t ON ptm.tag_id = t.tag_id 
                WHERE p.product_id = :product_id 
                GROUP BY p.product_id";

        $stmt = $pdo->prepare($sql); // Prepare the SQL statement
        $stmt->execute(['product_id' => $productId]); // Execute the query with productId parameter
        $product = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch product data

        if ($product && !empty($product['tags'])) {
            $product['tags'] = explode(', ', $product['tags']); // Convert tags from a string to an array
        } else {
            $product['tags'] = []; // Ensure tags are always returned as an array
        }

        return $product; // Return the product data

    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Handle errors based on environment
        return null; // Return null in case of an error
    }
}

/**
 * Retrieves a product by its slug and encoded Optimus ID.
 *
 * This function decodes the Optimus ID to obtain the original product ID
 * and then verifies if the provided slug matches the retrieved product.
 * If the product is found, it returns the product data; otherwise, it
 * throws an exception and handles the error.
 *
 * @param string $slug The slug of the product.
 * @param int $encodedId The encoded product ID using Optimus.
 * @return array|null The product data as an associative array or null if not found.
 */
function getProductBySlugAndOptimus($slug, $encodedId)
{
    try {
        $pdo = getPDOConnection(); // Establishes a database connection
        global $optimus;
        $productId = $optimus->decode($encodedId); // Decodes the Optimus ID to get the real product ID
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :id AND slug = :slug");
        $stmt->execute(['id' => $productId, 'slug' => $slug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product)
            throw new Exception("Invalid product URL");
        return $product;
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Handles the error and logs it
        return null;
    }
}


/**
 * Adds a new product to the database.
 *
 * This function performs the following steps:
 * - Validates required keys in the provided data.
 * - Checks for validation errors in the product data.
 * - Ensures that at least one image is provided and the count does not exceed 10.
 * - Sanitizes the input data.
 * - Inserts product details into the `products` table.
 * - Associates images with the product in the `product_images` table.
 * - Links the product to a category in the `product_category_mapping` table.
 * - Rolls back the transaction and deletes uploaded images if an error occurs.
 *
 * @param array $data The product data including name, price, currency, description, slug, images, and category.
 * @return array An associative array indicating success or failure with an error message if applicable.
 */
function addProduct($data)
{
    $requiredKeys = ['name', 'price_amount', 'currency', 'description', 'slug', 'images', 'category'];
    foreach ($requiredKeys as $key) {
        if (!isset($data[$key])) {
            return ['error' => true, 'message' => "Key '$key' not found in product data."];
        }
    }

    // Validate product data
    $violations = validateProductData($data);
    if (count($violations) > 0) {
        handleError("Validation failed: " . implode(", ", array_map(fn($v) => $v->getMessage(), $violations)), getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'Invalid product data. Please check your input.'];
    }

    // Ensure at least one image is provided and does not exceed 10
    if (empty($data['images']) || !is_array($data['images'])) {
        handleError("Product images required", getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'Product images are required.'];
    }

    $imageCount = count($data['images']);
    if ($imageCount < 1 || $imageCount > 10) {
        handleError("Number of images must be between 1 and 10", getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'Number of images must be between 1 and 10.'];
    }

    // Sanitize product data
    $data = sanitizeProductData($data);

    try {
        $pdo = getPDOConnection();
        $pdo->beginTransaction();

        // Insert product details
        $stmt = $pdo->prepare("INSERT INTO products (product_name, price_amount, currency, description, slug) VALUES (:name, :price_amount, :currency, :description, :slug)");
        $success = $stmt->execute([
            'name' => $data['name'],
            'price_amount' => $data['price_amount'],
            'currency' => $data['currency'],
            'description' => $data['description'],
            'slug' => $data['slug']
        ]);

        if (!$success || $stmt->rowCount() === 0) {
            $pdo->rollBack();
            return ['error' => true, 'message' => 'Failed to save product to database.'];
        }

        $product_id = $pdo->lastInsertId();

        // Insert product images
        $stmtImages = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
        foreach ($data['images'] as $imagePath) {
            $stmtImages->execute([$product_id, $imagePath]);
        }

        // Link product to category
        $stmt = $pdo->prepare("INSERT INTO product_category_mapping (product_id, category_id) VALUES (:product_id, :category_id)");
        $success = $stmt->execute(['product_id' => $product_id, 'category_id' => $data['category']]);

        if (!$success) {
            $pdo->rollBack();
            return ['error' => true, 'message' => 'Failed to link product to category.'];
        }

        $pdo->commit();
        return ['error' => false, 'message' => 'Product successfully added.'];

    } catch (Exception $e) {
        // Delete uploaded images if an error occurs
        foreach ($data['images'] as $imagePath) {
            $absPath = __DIR__ . '/../../public_html' . $imagePath;
            if (file_exists($absPath))
                @unlink($absPath);
        }

        $pdo->rollBack();
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local');
        return ['error' => true, 'message' => 'A system error occurred. Please try again.'];
    }
}

/**
 * Handles the submission of the "Add Product" form.
 *
 * This function processes the form data when a product is being added. It performs the following steps:
 * - Validates the CSRF token for security.
 * - Extracts and sanitizes product data from the form input.
 * - Ensures that the price is valid and formatted correctly.
 * - Generates a slug for the product name.
 * - Handles product image uploads and validates their count.
 * - Calls `addProduct()` to insert the product into the database.
 * - If successful, redirects to the product management page.
 * - If an error occurs, deletes uploaded images and stores the error message in the session.
 *
 * @return void Redirects to the manage products page with success or error messages.
 */
function handleAddProductForm()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
        try {
            validateCSRFToken($_POST['csrf_token']); // Validate CSRF token to prevent cross-site request forgery

            // Prepare product data from form input
            $productData = [
                'name' => $_POST['productName'] ?? '',
                'category' => (int) ($_POST['productCategory'] ?? 0),
                'price_amount' => 0,
                'currency' => 'IDR',
                'description' => $_POST['productDescription'] ?? '',
                'slug' => '',
                'images' => []
            ];

            // Validate product price
            if (!isset($_POST['productPriceAmount']) || !is_numeric($_POST['productPriceAmount'])) {
                throw new Exception("Invalid price format");
            }

            // Convert price to appropriate format using Money library
            $money = Money::of($_POST['productPriceAmount'], $_POST['productCurrency'], null, RoundingMode::DOWN);
            $productData['price_amount'] = $money->getAmount()->toInt();
            $productData['currency'] = $money->getCurrency()->getCurrencyCode();

            // Generate slug for the product based on its name
            $productData['slug'] = generateSlug($_POST['productName']);

            // Handle product image uploads
            $productData['images'] = handleProductImagesUpload();
            if (empty($productData['images'])) {
                throw new Exception("Minimum 1 image required, maximum 10 images allowed");
            }

            // Add product to the database
            $result = addProduct($productData);
            if ($result['error']) {
                // Delete uploaded images if product addition fails
                foreach ($productData['images'] as $imagePath) {
                    $absPath = __DIR__ . '/../../public_html' . $imagePath;
                    if (file_exists($absPath))
                        @unlink($absPath);
                }
                throw new Exception($result['message']);
            }

            // Store success message and reset form input
            $_SESSION['success_message'] = 'Produk berhasil ditambahkan!';
            $_SESSION['form_success'] = true;
            $_SESSION['old_input'] = [];
            session_write_close();

            // Redirect to the manage products page
            $config = getEnvironmentConfig();
            header("Location: " . getBaseUrl($config, $_ENV['LIVE_URL']) . "manage_products");
            exit();

        } catch (Throwable $e) {
            // Delete uploaded images if an error occurs
            if (!empty($productData['images'])) {
                foreach ($productData['images'] as $imagePath) {
                    $absPath = __DIR__ . '/../../public_html' . $imagePath;
                    if (file_exists($absPath))
                        @unlink($absPath);
                }
            }

            // Store error message and retain form input data
            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['form_success'] = false;
            $_SESSION['old_input'] = $_POST;
            session_write_close();

            // Redirect to the manage products page
            $config = getEnvironmentConfig();
            header("Location: " . getBaseUrl($config, $_ENV['LIVE_URL']) . "manage_products");
            exit();
        }
    } else {
        // Handle invalid requests
        $_SESSION['error_message'] = 'Permintaan tidak valid';
        $_SESSION['form_success'] = false;
        session_write_close();
    }
}

/**
 * Handles the upload of product images.
 *
 * This function processes and uploads images submitted via the product form.
 * It performs the following steps:
 * - Checks if `$_FILES['productImages']` is set.
 * - Ensures the upload directory exists, creating it if necessary.
 * - Validates images using `validateProductImages()`.
 * - Iterates over validated images, assigns a unique filename, and moves them to the upload directory.
 * - Returns an array of successfully uploaded image paths.
 *
 * @return array List of uploaded image file paths.
 */
function handleProductImagesUpload()
{
    if (!isset($_FILES['productImages']))
        return [];

    $uploadDir = __DIR__ . '/../../public_html/uploads/products/';
    if (!file_exists($uploadDir))
        mkdir($uploadDir, 0755, true); // Ensure upload directory exists

    $validationResults = validateProductImages($_FILES['productImages']); // Validate uploaded images
    $uploadedImages = [];

    foreach ($validationResults as $index => $result) {
        if ($result['error'])
            continue; // Skip invalid images

        // Generate a unique filename for the image
        $filename = uniqid('product_', true) . '.' . $result['data']['extension'];
        $destinationPath = $uploadDir . $filename;

        // Move uploaded file to the designated directory
        if (move_uploaded_file($_FILES['productImages']['tmp_name'][$index], $destinationPath)) {
            $uploadedImages[] = '/uploads/products/' . $filename;
        }
    }

    return $uploadedImages; // Return an array of successfully uploaded image paths
}

/**
 * Updates an existing product in the database by its ID.
 *
 * This function establishes a connection to the database using PDO,
 * prepares an SQL query to update a product in the 'products' table,
 * and executes the query with the provided product data and ID.
 * It returns an array with a success message or an error message.
 *
 * @param int $id The ID of the product to update.
 * @param array $data An associative array containing the updated product data (name, price, description, image_path, slug).
 * @return array Returns an array with a success message on successful update,
 *               or an array with an error message on failure.
 */
function updateProduct($id, $data)
{
    // Validate product data
    $violations = validateProductData($data);

    // If there are any violations, handle the error
    if (count($violations) > 0) {
        // Log the error for debugging purposes
        handleError("Validation failed: " . implode(", ", array_map(fn($v) => $v->getMessage(), $violations)), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Data produk tidak valid. Silakan periksa kembali data yang Anda masukkan.'
        ];
    }

    // Sanitize data using the sanitizeProductData function
    $data = sanitizeProductData($data);

    // Validate price
    try {
        $price = validatePrice($data['price_amount'], $data['currency']);
    } catch (\InvalidArgumentException $e) {
        // Log the error for debugging purposes
        handleError("Invalid price format: " . $e->getMessage(), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Format harga tidak valid. Harap masukkan harga yang benar.'
        ];
    }

    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("UPDATE products SET product_name = :name, price = :price, description = :description, image_path = :image_path, slug = :slug WHERE product_id = :id");

        // Execute the query
        $success = $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'price' => $price->getAmount(), // Store the amount in cents
            'description' => $data['description'],
            'image_path' => $data['image_path'],
            'slug' => $data['slug'],
        ]);

        if ($success) {
            return [
                'error' => false,
                'message' => 'Produk berhasil diperbarui.'
            ];
        } else {
            return [
                'error' => true,
                'message' => 'Gagal memperbarui produk. Silakan coba lagi nanti.'
            ];
        }
    } catch (Exception $e) {
        // Log the error for debugging purposes
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local');

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Terjadi kesalahan saat memperbarui produk. Silakan hubungi admin.'
        ];
    }
}

/**
 * Deletes a product from the database and removes associated image files.
 *
 * This function performs the following steps:
 * - Validates the product ID to ensure it is a valid numeric value.
 * - Retrieves image paths from the `product_images` table.
 * - Deletes product-category mappings from `product_category_mapping`.
 * - Deletes associated images from `product_images`.
 * - Deletes the product from the `products` table.
 * - Removes image files from the server if they exist.
 * - Uses database transactions to ensure consistency.
 *
 * @param int $id The ID of the product to be deleted.
 * @return array Returns an associative array with 'error' (boolean) and 'message' (string).
 */
function deleteProduct($id)
{
    if (!is_numeric($id) || $id <= 0) {
        return ['error' => true, 'message' => 'Invalid product ID.'];
    }

    try {
        $config = getEnvironmentConfig(); // Load environment configuration.
        $pdo = getPDOConnection(); // Establish a PDO connection.
        if (!$pdo) {
            return ['error' => true, 'message' => 'Database connection failed.'];
        }

        $pdo->beginTransaction(); // Begin database transaction.

        // Retrieve all image paths related to the product.
        $stmtImage = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = :id");
        $stmtImage->execute(['id' => $id]);
        $imagePaths = $stmtImage->fetchAll(PDO::FETCH_COLUMN, 0);

        // Delete product-category mappings.
        $stmtMapping = $pdo->prepare("DELETE FROM product_category_mapping WHERE product_id = :id");
        $stmtMapping->execute(['id' => $id]);

        // Delete product images from the database.
        $stmtImagesDelete = $pdo->prepare("DELETE FROM product_images WHERE product_id = :id");
        $stmtImagesDelete->execute(['id' => $id]);

        // Delete the product from the database.
        $stmtProduct = $pdo->prepare("DELETE FROM products WHERE product_id = :id");
        $stmtProduct->execute(['id' => $id]);

        if ($stmtProduct->rowCount() > 0) {
            // Delete image files from the server.
            if (!empty($imagePaths)) {
                foreach ($imagePaths as $imagePath) {
                    if (!empty($imagePath)) {
                        $absolutePath = __DIR__ . '/../../public_html' . $imagePath;
                        if (file_exists($absolutePath)) {
                            unlink($absolutePath);
                        }
                    }
                }
            }

            $pdo->commit(); // Commit the transaction if successful.
            return ['error' => false, 'message' => 'Product, category, and images successfully deleted.'];
        } else {
            $pdo->rollBack(); // Rollback the transaction if no product was found.
            return ['error' => true, 'message' => 'Product not found or already deleted.'];
        }
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack(); // Rollback the transaction in case of an error.
        }

        handleError("Database Error: " . $e->getMessage(), $config['is_live'] ? 'live' : 'local');
        return ['error' => true, 'message' => 'A database error occurred.'];
    }
}

/**
 * Searches for products based on a given keyword.
 *
 * This function queries the database to find products whose names or descriptions 
 * match the provided keyword. The search is performed using a partial match (`LIKE`).
 *
 * @param string $keyword The search keyword used to filter products.
 *                        - The keyword is matched against `product_name` and `description`.
 * 
 * @return array Returns an array of matching products. If an error occurs, an empty array is returned.
 */
function searchProducts($keyword)
{
    try {
        $pdo = getPDOConnection(); // Establish database connection
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_name LIKE :keyword OR description LIKE :keyword"); // Prepare SQL query
        $stmt->execute(['keyword' => "%$keyword%"]); // Bind keyword and execute query
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch and return results as an associative array
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Handle errors based on environment
        return []; // Return an empty array in case of failure
    }
}

/**
 * Performs an advanced product search based on multiple filters.
 *
 * This function allows filtering products using various criteria such as keyword, category, 
 * and price range. The SQL query is dynamically constructed based on provided filters.
 *
 * @param array $filters An associative array containing the following optional keys:
 *                       - `keyword` (string): Searches within product names and descriptions.
 *                       - `category` (int): Filters by category ID.
 *                       - `min_price` (float): Sets the minimum price limit.
 *                       - `max_price` (float): Sets the maximum price limit.
 * 
 * @return array Returns an array of filtered products. Returns an empty array if an error occurs.
 */
function advancedProductSearch($filters)
{
    try {
        $pdo = getPDOConnection(); // Establish database connection
        $query = "SELECT * FROM products WHERE 1=1"; // Base query, ensures dynamic conditions can be appended
        $params = [];

        if (!empty($filters['keyword'])) { // Apply keyword filter (search in name and description)
            $query .= " AND (product_name LIKE :keyword OR description LIKE :keyword)";
            $params['keyword'] = "%" . $filters['keyword'] . "%";
        }
        if (!empty($filters['category'])) { // Apply category filter
            $query .= " AND category_id = :category";
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['min_price'])) { // Apply minimum price filter
            $query .= " AND price >= :min_price";
            $params['min_price'] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) { // Apply maximum price filter
            $query .= " AND price <= :max_price";
            $params['max_price'] = $filters['max_price'];
        }

        $stmt = $pdo->prepare($query); // Prepare the query
        $stmt->execute($params); // Execute query with bound parameters
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch and return filtered results
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Handle errors based on environment
        return []; // Return an empty array in case of failure
    }
}

/**
 * Retrieves search suggestions for autocomplete functionality.
 *
 * This function queries the database for product names that match the provided 
 * keyword, returning up to five suggestions. It is useful for real-time search 
 * suggestions in user interfaces.
 *
 * @param string $keyword The search keyword used to find matching product names.
 *                        - The keyword is matched against the beginning of `product_name`.
 * 
 * @return array Returns an array of suggested product names. Returns an empty array on failure.
 */
function getSearchSuggestions($keyword)
{
    try {
        $pdo = getPDOConnection(); // Establish database connection
        $stmt = $pdo->prepare("SELECT product_name FROM products WHERE product_name LIKE :keyword LIMIT 5"); // Prepare SQL query
        $stmt->execute(['keyword' => "$keyword%"]); // Bind keyword and execute query
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Fetch only product names as an array
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Handle errors based on environment
        return []; // Return an empty array in case of failure
    }
}

/**
 * Performs a fuzzy search for products, allowing for minor spelling mistakes.
 *
 * This function compares the product names with the provided keyword using the SOUNDEX 
 * function, which enables matching even if there are minor typos or phonetic variations 
 * in the search term.
 *
 * @param string $keyword The search keyword that may contain minor spelling errors.
 * 
 * @return array Returns an array of matching products. If no products are found, an empty array is returned.
 */
function fuzzySearchProducts($keyword)
{
    try {
        $pdo = getPDOConnection(); // Establish database connection
        $stmt = $pdo->prepare("SELECT * FROM products WHERE SOUNDEX(product_name) = SOUNDEX(:keyword)"); // Prepare SQL query with SOUNDEX for fuzzy matching
        $stmt->execute(['keyword' => $keyword]); // Bind the keyword and execute the query
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch and return all matching products as an associative array
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Handle errors based on environment
        return []; // Return an empty array if an error occurs
    }
}

/**
 * Logs a search query for analytics and tracking purposes.
 *
 * This function inserts a record into the `search_logs` table to track the keyword search 
 * made by a user, including the user ID and the search date. This data is useful for 
 * analyzing search trends and user behavior.
 *
 * @param string $keyword The search keyword entered by the user.
 * @param int|null $userId The ID of the user performing the search. Pass `null` for guests.
 * 
 * @return void No value is returned. If an error occurs, it is handled internally.
 */
function logSearchQuery($keyword, $userId = null)
{
    try {
        $pdo = getPDOConnection(); // Establish a database connection
        $stmt = $pdo->prepare("INSERT INTO search_logs (user_id, keyword, search_date) VALUES (:user_id, :keyword, NOW())"); // Prepare the insert query
        $stmt->execute([ // Bind values and execute the insert
            'user_id' => $userId,
            'keyword' => $keyword,
        ]);
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Handle any potential error
    }
}

/**
 * Deletes multiple products from the database.
 * 
 * This function removes multiple products based on their IDs using 
 * the `DELETE FROM` SQL statement with placeholders for secure 
 * parameter binding.
 * 
 * If an error occurs during the deletion process, the function logs 
 * the error and displays an error message to the user.
 * 
 * @param array $ids An array of product IDs to be deleted.
 * @return void
 */
function batchDeleteProducts($ids)
{
    $pdo = getPDOConnection();
    if (!$pdo)
        return; // Stop execution if database connection fails

    try {
        // Create placeholders for parameter binding (?, ?, ?)
        $placeholders = rtrim(str_repeat('?,', count($ids)), ',');

        // SQL query to delete products with the specified IDs
        $sql = "DELETE FROM products WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);

        // Execute the query with the array of IDs
        $stmt->execute($ids);

        // Display the number of products deleted
        echo "Deleted " . $stmt->rowCount() . " products successfully";
    } catch (PDOException $e) {
        // Handle database errors and log them appropriately
        handleError(
            "Delete Error: " . $e->getMessage(),
            ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'
        );
        echo 'Error deleting products';
    } finally {
        $pdo = null; // Close database connection
    }
}

/**
 * Soft deletes a product by updating its `deleted_at` column.
 * 
 * This function marks a product as deleted by setting the `deleted_at` 
 * field to the current timestamp instead of permanently removing 
 * the product from the database. 
 * 
 * If the product ID is not found, it returns a message stating that no 
 * product was found with the given ID.
 * 
 * If an error occurs during the process, the function logs the error 
 * and displays an error message to the user.
 * 
 * @param int $id The ID of the product to be soft deleted.
 * @return void
 */
function softDeleteProduct($id)
{
    $pdo = getPDOConnection();
    if (!$pdo)
        return; // Stop execution if database connection fails

    try {
        // SQL query to update the `deleted_at` column with the current timestamp
        $sql = "UPDATE products SET deleted_at = NOW() WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        // Bind product ID to the query as an integer
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute(); // Execute the query

        // Check if any rows were affected (product found and updated)
        echo $stmt->rowCount() > 0
            ? "Product soft deleted successfully"
            : "No product found with ID $id";
    } catch (PDOException $e) {
        // Handle database errors and log them appropriately
        handleError(
            "Soft Delete Error: " . $e->getMessage(),
            ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'
        );
        echo 'Error soft deleting product';
    } finally {
        $pdo = null; // Close database connection
    }
}

/**
 * Exports products to a CSV file.
 * 
 * This function retrieves all products that are not deleted from the database 
 * and exports them to a CSV file with the format `products_export_YYYYMMDD.csv`.
 * If no products are found, it displays a message indicating that no products 
 * are available for export.
 * 
 * The CSV file contains the following columns: 
 * - ID: The unique identifier of the product.
 * - Name: The name of the product.
 * - Price: The price of the product.
 * - Description: A brief description of the product.
 * 
 * If an error occurs during the export process, the function logs the error 
 * and displays an error message to the user.
 * 
 * @return void
 */
function exportProductsToCSV()
{
    $pdo = getPDOConnection();
    if (!$pdo)
        return; // Stop execution if the database connection fails

    try {
        // Query to fetch all active products (excluding deleted ones)
        $sql = "SELECT * FROM products WHERE deleted_at IS NULL";
        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($products) > 0) {
            // Generate CSV filename with the current date
            $filename = "products_export_" . date('Ymd') . ".csv";
            $file = fopen($filename, 'w'); // Open file for writing

            // Define CSV headers
            $headers = array('ID', 'Name', 'Price', 'Description');
            fputcsv($file, $headers); // Write headers to the CSV file

            // Write product data to the CSV file
            foreach ($products as $product) {
                fputcsv($file, $product);
            }

            fclose($file); // Close the file after writing
            echo "Exported " . count($products) . " products to $filename";
        } else {
            echo "No products found to export"; // Message if no products are available
        }
    } catch (PDOException $e) {
        // Handle database errors and log them appropriately
        handleError(
            "Export Error: " . $e->getMessage(),
            ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live'
        );
        echo 'Error exporting products';
    } finally {
        $pdo = null; // Close database connection
    }
}

/**
 * Generates a URL-friendly slug from a product name.
 * 
 * This function converts the given product name into a lowercase, 
 * hyphen-separated string that can be used as a URL slug.
 * 
 * The process includes:
 * - Converting all characters to lowercase.
 * - Replacing non-alphanumeric characters with hyphens.
 * - Trimming unnecessary hyphens from the start and end of the string.
 * 
 * @param string $name The product name to be converted into a slug.
 * @return string The generated slug.
 */
function generateProductSlug($name)
{
    $slug = strtolower($name); // Convert to lowercase
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug); // Replace non-alphanumeric characters with hyphens
    return trim($slug, '-'); // Trim hyphens from the beginning and end
}

/**
 * Generates pagination links for navigating between pages.
 * 
 * This function creates an array of pagination links, including:
 * - A "Previous" button if the current page is greater than 1.
 * - Numbered page links, with the current page highlighted.
 * - A "Next" button if the current page is less than the total pages.
 * 
 * The links are returned as an HTML string with each link separated by a space.
 * 
 * @param int $currentPage The current active page number.
 * @param int $totalPages The total number of pages available.
 * @param string $urlPattern The URL pattern for pagination links, default is '?page='.
 * @return string The generated HTML pagination links.
 */
function generatePaginationLinks($currentPage, $totalPages, $urlPattern = '?page=')
{
    $links = [];
    if ($currentPage > 1)
        $links[] = "<a href='{$urlPattern}" . ($currentPage - 1) . "'>Previous</a>"; // Previous button
    for ($i = 1; $i <= $totalPages; $i++) {
        $links[] = ($i == $currentPage) ? "<strong>$i</strong>" : "<a href='{$urlPattern}$i'>$i</a>"; // Page links
    }
    if ($currentPage < $totalPages)
        $links[] = "<a href='{$urlPattern}" . ($currentPage + 1) . "'>Next</a>"; // Next button
    return implode(" ", $links);
}

/**
 * Highlights a keyword within a given text using the `<mark>` HTML tag.
 * 
 * This function searches for the specified keyword in the provided text and 
 * wraps it with a `<mark>` tag for highlighting. It performs a case-insensitive 
 * search and ensures that only whole words are matched.
 * 
 * If the keyword is empty, the function returns the original text without modification.
 * 
 * @param string $text The input text where the keyword should be highlighted.
 * @param string $keyword The word to be highlighted within the text.
 * @return string The modified text with the keyword wrapped in a `<mark>` tag.
 */
function highlightKeyword($text, $keyword)
{
    if (empty($keyword))
        return $text; // Return original text if keyword is empty
    return preg_replace("/\b($keyword)\b/i", '<mark>$1</mark>', $text); // Wrap matched keyword with <mark> tag
}

/**
 * Formats a given amount as a currency string according to the specified locale.
 * 
 * This function creates a Money object with the provided amount and currency code, 
 * then formats it according to the given locale. If an unknown currency is provided, 
 * it catches the exception and returns an error message.
 * 
 * @param float|int $amount The monetary amount to be formatted.
 * @param string $currencyCode The currency code (e.g., 'IDR' for Indonesian Rupiah).
 * @param string $locale The locale used for formatting (e.g., 'id_ID' for Indonesian format).
 * @return string The formatted currency string or an error message if the currency is invalid.
 */
function formatPrice($amount, $currencyCode = 'IDR', $locale = 'id_ID')
{
    try {
        $money = Money::of($amount, $currencyCode); // Create a Money object with amount and currency
        return $money->formatTo($locale); // Format the currency based on the specified locale
    } catch (UnknownCurrencyException $e) {
        return "Error: Currency code '$currencyCode' is not valid."; // Handle invalid currency codes
    }
}