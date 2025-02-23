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
 * Sanitizes product data to prevent XSS attacks.
 *
 * This function sanitizes the name, description, and slug fields
 * by removing harmful HTML and special characters using AntiXSS.
 *
 * @param array $data The product data to sanitize.
 * @return array The sanitized product data.
 */
function sanitizeProductData($data)
{
    $antiXSS = new AntiXSS();

    // Sanitize the relevant fields
    $data['name'] = $antiXSS->xss_clean(trim($data['name']));
    $data['price_amount'] = (int) $data['price_amount'];
    $data['currency'] = strtoupper(trim($data['currency']));
    $data['description'] = $antiXSS->xss_clean(trim($data['description']));
    $data['slug'] = strtolower(trim($data['slug']));

    return $data;
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']);

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
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']); // Handle errors if the query fails
        return []; // Return an empty array in case of an error
    }
}

/**
 * Retrieves all products along with their associated categories and tags.
 * 
 * This function fetches all products from the database and includes their associated categories and tags. 
 * The data is structured as an associative array, where each product contains a concatenated list of its categories and tags.
 * 
 * - The function uses LEFT JOIN to connect the products table with the category and tag tables.
 * - The GROUP_CONCAT function is used to merge multiple categories and tags into a single string per product.
 * - If an error occurs, it logs the error and returns an empty array.
 * 
 * @return array An array of products, each containing product details along with concatenated category and tag names.
 */
function getAllProductsWithCategoriesAndTags()
{
    try {
        $pdo = getPDOConnection();
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.slug,
                    p.description,
                    p.price_amount,
                    p.currency,
                    p.image_path,
                    p.created_at,
                    p.updated_at,
                    GROUP_CONCAT(DISTINCT pc.category_name ORDER BY pc.category_name SEPARATOR ', ') AS categories,
                    GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM 
                    products p
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
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
        return [];
    }
}

/**
 * Retrieves product details along with associated categories and tags.
 *
 * This function fetches product information based on the given product ID. It retrieves details such as the product name, description, price, currency, image path, creation and update timestamps, 
 * along with associated categories and tags. The categories and tags are concatenated as comma-separated strings.
 *
 * @param int $productId The ID of the product to retrieve.
 * @return array|null Returns an associative array of product details if found, otherwise null.
 */
function getProductWithDetails($productId)
{
    try {
        $pdo = getPDOConnection();

        // SQL query to fetch product details along with categories and tags
        $sql = "SELECT p.product_id, p.product_name, p.description, p.price_amount, p.currency, p.image_path, p.created_at, p.updated_at, 
                    GROUP_CONCAT(DISTINCT pc.category_name ORDER BY pc.category_name SEPARATOR ', ') AS categories, 
                    GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM products p
                LEFT JOIN product_category_mapping pcm ON p.product_id = pcm.product_id
                LEFT JOIN product_categories pc ON pcm.category_id = pc.category_id
                LEFT JOIN product_tag_mapping ptm ON p.product_id = ptm.product_id
                LEFT JOIN tags t ON ptm.tag_id = t.tag_id
                WHERE p.product_id = :product_id
                GROUP BY p.product_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['product_id' => $productId]);

        // Fetch and return the result as an associative array
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Handle exceptions and return null in case of an error
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
        return null;
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']); // Handles the error and logs it
        return null;
    }
}


/**
 * Adds a new product to the database.
 *
 * This function validates the product data, ensures an image is uploaded, 
 * sanitizes the data, and inserts the product into the database. 
 * If any step fails, it rolls back the transaction and returns an error message.
 *
 * The function performs the following steps:
 * - Validates the provided product data
 * - Checks if an image is provided
 * - Sanitizes the product data
 * - Inserts the product into the database
 * - Retrieves the last inserted product ID
 * - Links the product to the specified category
 * - Commits the transaction if successful, otherwise rolls back and returns an error
 *
 * @param array $data Product data including name, price, currency, description, image path, and category.
 * @return array Result of the operation with error status and message.
 */
function addProduct($data)
{
    $violations = validateProductData($data); // Validate product data
    if (count($violations) > 0) {
        handleError("Validation failed: " . implode(", ", array_map(fn($v) => $v->getMessage(), $violations)), getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'Invalid product data. Please check your input.'];
    }

    if (empty($data['image_path'])) { // Ensure an image is uploaded
        handleError("Product image required", getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'Product image is required.'];
    }

    $data = sanitizeProductData($data); // Sanitize input data

    try {
        $pdo = getPDOConnection();
        $pdo->beginTransaction(); // Start transaction

        // Insert product into database
        $stmt = $pdo->prepare("INSERT INTO products (product_name,price_amount,currency,description,image_path,slug) VALUES (:name,:price_amount,:currency,:description,:image_path,:slug)");
        $success = $stmt->execute([
            'name' => $data['name'],
            'price_amount' => $data['price_amount'],
            'currency' => $data['currency'],
            'description' => $data['description'],
            'image_path' => $data['image_path'],
            'slug' => $data['slug']
        ]);

        if (!$success || $stmt->rowCount() === 0) { // Check if product insertion was successful
            $pdo->rollBack();
            return ['error' => true, 'message' => 'Failed to save product to database.'];
        }

        $product_id = $pdo->lastInsertId(); // Retrieve last inserted product ID

        // Link product to category
        $stmt = $pdo->prepare("INSERT INTO product_category_mapping (product_id,category_id) VALUES (:product_id,:category_id)");
        $success = $stmt->execute(['product_id' => $product_id, 'category_id' => $data['category']]);

        if (!$success) { // Rollback if linking fails
            $pdo->rollBack();
            return ['error' => true, 'message' => 'Failed to link product to category.'];
        }

        $pdo->commit(); // Commit transaction
        return ['error' => false, 'message' => 'Product successfully added.'];

    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback on error
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'A system error occurred. Please try again.'];
    }
}

/**
 * Handles the product addition form submission with proper session management.
 *
 * This function processes the form submission for adding a new product. It performs the following tasks:
 * - Validates the CSRF token to ensure request authenticity.
 * - Extracts and validates form input data.
 * - Processes the price input using the Money library.
 * - Generates a slug from the product name.
 * - Handles image upload and validation.
 * - Inserts the product into the database.
 * - Manages error handling, logging, and session storage.
 * - Redirects the user based on success or failure.
 */
function handleAddProductForm()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) { // Check if the request is a POST request and contains a CSRF token
        try {
            validateCSRFToken($_POST['csrf_token']); // Validate CSRF token to prevent cross-site request forgery

            // Initialize product data with default values
            $productData = [
                'name' => $_POST['productName'] ?? '', // Get product name or default to empty string
                'category' => (int) ($_POST['productCategory'] ?? 0), // Convert category to integer, default is 0
                'price_amount' => 0, // Default price amount
                'currency' => 'IDR', // Default currency
                'description' => $_POST['productDescription'] ?? '', // Get product description or default to empty string
                'slug' => '', // Slug will be generated later
                'image_path' => '' // Image path will be updated after upload
            ];

            // Validate and process product price
            if (!isset($_POST['productPriceAmount']) || !is_numeric($_POST['productPriceAmount']))
                throw new Exception("Invalid price format");

            // Convert price amount using Money library
            $money = Money::of($_POST['productPriceAmount'], $_POST['productCurrency'], null, RoundingMode::DOWN);
            $productData['price_amount'] = $money->getAmount()->toInt(); // Convert price to integer
            $productData['currency'] = $money->getCurrency()->getCurrencyCode(); // Get currency code

            // Generate product slug from the product name
            $productData['slug'] = generateSlug($_POST['productName']); // Updated to use generateSlug

            // Handle image upload and store the image path
            $productData['image_path'] = handleProductImageUpload();
            if (empty($productData['image_path']))
                throw new Exception("Image upload failed. Please check file requirements.");

            // Insert product into the database
            $result = addProduct($productData);
            if ($result['error']) {
                // If product insertion fails, delete the uploaded image to prevent orphaned files
                if (!empty($productData['image_path'])) {
                    $absPath = __DIR__ . '/../../public_html' . $productData['image_path'];
                    if (file_exists($absPath))
                        @unlink($absPath);
                }
                throw new Exception($result['message']);
            }

            // Set success message in session and clear old input data
            $_SESSION['success_message'] = 'Produk berhasil ditambahkan!';
            $_SESSION['form_success'] = true;
            $_SESSION['old_input'] = [];
            session_write_close();

            // Redirect to manage_products page
            $config = getEnvironmentConfig();
            header("Location: " . getBaseUrl($config, $_ENV['LIVE_URL']) . "manage_products");
            exit();
        } catch (Exception $e) {
            // Log error message and store error in session
            error_log("Gagal menambahkan produk: " . $e->getMessage());

            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['form_success'] = false;
            $_SESSION['old_input'] = $_POST;
            session_write_close();

            // Redirect back to manage_products page with error message
            $config = getEnvironmentConfig();
            header("Location: " . getBaseUrl($config, $_ENV['LIVE_URL']) . "manage_products");
            exit();
        }
    } else {
        // Log invalid access attempt
        error_log("Invalid access method to product form");
        $_SESSION['error_message'] = 'Permintaan tidak valid';
        $_SESSION['form_success'] = false;
        session_write_close();
    }
}

/**
 * Handles the upload of a product image with comprehensive validation.
 * 
 * Validates the image file, generates a unique filename, and stores it 
 * in the designated directory. Returns the relative file path for web access.
 * 
 * @return string Relative file path if successful, empty string on failure.
 */
function handleProductImageUpload()
{
    if (!isset($_FILES['productImage'])) {
        return '';
    }

    // 1. Lakukan validasi gambar
    $validationResult = validateProductImage($_FILES['productImage']);

    // 2. Handle jika validasi gagal
    if ($validationResult['error']) {
        handleError(
            'Image validation failed: ' . $validationResult['message'],
            getEnvironmentConfig()['local']
        );
        return '';
    }

    // 3. Siapkan direktori upload
    $uploadDir = __DIR__ . '/../../public_html/uploads/products/';

    // 4. Buat direktori jika belum ada
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 5. Generate nama file unik dengan ekstensi asli
    $filename = uniqid('product_', true) . '.' . $validationResult['data']['extension'];
    $destinationPath = $uploadDir . $filename;

    // 6. Pindahkan file yang valid
    if (
        move_uploaded_file(
            $validationResult['data']['tmp_path'],
            $destinationPath
        )
    ) {
        // 7. Return path relatif untuk penggunaan web
        return '/uploads/products/' . $filename;
    }

    handleError('Failed to move uploaded file', getEnvironmentConfig()['local']);
    return '';
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']);

        // Return a user-friendly error message
        return [
            'error' => true,
            'message' => 'Terjadi kesalahan saat memperbarui produk. Silakan hubungi admin.'
        ];
    }
}

/**
 * Deletes a product from the database and removes its associated image file.
 *
 * This function validates the product ID, establishes a database connection,
 * retrieves the image path, deletes the product and its category mappings,
 * and removes the associated image file from the filesystem. It handles
 * database transactions and errors gracefully.
 *
 * @param int $id The ID of the product to delete.
 * @return array Returns an associative array with 'error' (boolean) and 'message' (string).
 */
function deleteProduct($id)
{
    if (!is_numeric($id) || $id <= 0) {
        return ['error' => true, 'message' => 'ID produk tidak valid.'];
    }

    try {
        $config = getEnvironmentConfig();
        $pdo = getPDOConnection();
        if (!$pdo) {
            return ['error' => true, 'message' => 'Koneksi database gagal.'];
        }

        $pdo->beginTransaction();

        // Retrieve the image path from the database
        $stmtImage = $pdo->prepare("SELECT image_path FROM products WHERE product_id=:id");
        $stmtImage->execute(['id' => $id]);
        $imagePath = $stmtImage->fetchColumn();

        // Delete product category mappings
        $stmtMapping = $pdo->prepare("DELETE FROM product_category_mapping WHERE product_id=:id");
        $stmtMapping->execute(['id' => $id]);

        // Delete the product from the database
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id=:id");
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            // Delete the associated image file if it exists
            if ($imagePath) {
                $absolutePath = __DIR__ . '/../../public_html' . $imagePath;
                if (file_exists($absolutePath)) {
                    unlink($absolutePath);
                }
            }

            $pdo->commit();
            return ['error' => false, 'message' => 'Produk dan gambar berhasil dihapus.'];
        } else {
            $pdo->rollBack();
            return ['error' => true, 'message' => 'Produk tidak ditemukan atau sudah dihapus.'];
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        handleError("Database Error: " . $e->getMessage(), $config['is_live'] ? 'live' : 'local');
        return ['error' => true, 'message' => 'Terjadi kesalahan pada database.'];
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']); // Handle errors based on environment
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']); // Handle errors based on environment
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']); // Handle errors based on environment
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']); // Handle errors based on environment
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
        handleError($e->getMessage(), getEnvironmentConfig()['local']); // Handle any potential error
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