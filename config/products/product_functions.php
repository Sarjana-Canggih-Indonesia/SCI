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
 * Adds a new product to the database.
 * 
 * This function validates the product data, sanitizes inputs, and ensures that 
 * the price is correctly formatted. It then inserts the product into the `products` table 
 * and maps the product to a category in the `product_category_mapping` table.
 * 
 * If any step fails, the function rolls back the transaction to maintain data integrity.
 * 
 * @param array $data Associative array containing product details:
 *                    - name (string): Product name.
 *                    - price_amount (int): Price amount in smallest currency unit.
 *                    - currency (string): Currency code (e.g., "USD").
 *                    - description (string): Product description.
 *                    - image_path (string): Path to the uploaded product image.
 *                    - slug (string): URL-friendly slug for the product.
 *                    - category (int): Category ID of the product.
 * 
 * @return array An associative array with:
 *               - 'error' (bool): Whether an error occurred.
 *               - 'message' (string): Success or error message.
 */
function addProduct($data)
{
    $violations = validateProductData($data);
    if (count($violations) > 0) {
        handleError("Validation failed: " . implode(", ", array_map(fn($v) => $v->getMessage(), $violations)), getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'Invalid product data. Please check your input.'];
    }

    $data = sanitizeProductData($data);

    try {
        // Validate price format and currency
        $price = validatePrice($data['price_amount'], $data['currency']);
    } catch (\InvalidArgumentException $e) {
        handleError("Invalid price format: " . $e->getMessage(), getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'Invalid price format. Please enter a correct value.'];
    }

    try {
        $pdo = getPDOConnection();
        $pdo->beginTransaction(); // Start transaction

        // Insert product into the `products` table
        $stmt = $pdo->prepare("INSERT INTO products (product_name, price_amount, currency, description, image_path, slug) VALUES (:name, :price_amount, :currency, :description, :image_path, :slug)");
        $success = $stmt->execute([
            'name' => $data['name'],
            'price_amount' => $data['price_amount'],
            'currency' => $data['currency'],
            'description' => $data['description'],
            'image_path' => $data['image_path'],
            'slug' => $data['slug'],
        ]);

        if (!$success) {
            $pdo->rollBack(); // Rollback on failure
            return ['error' => true, 'message' => 'Failed to add product. Please try again later.'];
        }

        // Retrieve the last inserted product ID
        $product_id = $pdo->lastInsertId();

        // Map the product to a category in `product_category_mapping`
        $stmt = $pdo->prepare("INSERT INTO product_category_mapping (product_id, category_id) VALUES (:product_id, :category_id)");
        $success = $stmt->execute([
            'product_id' => $product_id,
            'category_id' => $data['category'],
        ]);

        if (!$success) {
            $pdo->rollBack(); // Rollback if category mapping fails
            return ['error' => true, 'message' => 'Failed to map product to category.'];
        }

        $pdo->commit(); // Commit transaction if all operations succeed
        return ['error' => false, 'message' => 'Product added successfully.'];

    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback transaction on error
        handleError($e->getMessage(), getEnvironmentConfig()['local']);
        return ['error' => true, 'message' => 'An error occurred while adding the product. Please contact the administrator.'];
    }
}

/**
 * Handles the addition of a new product from the form submission.
 * 
 * This function processes the submitted product data, validates the CSRF token,
 * ensures that all required fields are set, and attempts to add the product to the database.
 * It also handles image uploads and error logging.
 * 
 * @return void
 */
function handleAddProductForm()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
        validateCSRFToken($_POST['csrf_token']);

        try {
            $priceAmount = $_POST['productPriceAmount'];
            $currencyCode = $_POST['productCurrency'];

            // Convert price input into a Money object and extract its integer amount and currency code
            $money = Money::of($priceAmount, $currencyCode, null, RoundingMode::DOWN);
            $amount = $money->getAmount()->toInt();
            $currency = $money->getCurrency()->getCurrencyCode();

            // Ensure that the product category is set
            if (!isset($_POST['productCategory']) || empty($_POST['productCategory'])) {
                throw new Exception("Product category is required.");
            }

            // Prepare product data for insertion
            $productData = [
                'name' => $_POST['productName'],
                'category' => $_POST['productCategory'],
                'tags' => $_POST['productTags'],
                'price_amount' => $amount,
                'currency' => $currency,
                'description' => $_POST['productDescription'],
                'image_path' => '',
                'slug' => slugify($_POST['productName'])
            ];

            // Handle product image upload and update image path
            $productData['image_path'] = handleProductImageUpload();

            // Insert product into the database
            $result = addProduct($productData);

            // Handle potential errors from the database insertion process
            if ($result['error']) {
                throw new Exception($result['message']);
            }

        } catch (Exception $e) {
            $errorMessage = "Failed to add product: " . $e->getMessage();
            error_log("Error: " . $errorMessage);
            handleError($errorMessage, $_ENV['ENVIRONMENT']);
        }
    } else {
        error_log("Invalid request method or CSRF token not set.");
    }
}

/**
 * Handles the upload of a product image.
 * 
 * This function checks if a product image file is uploaded, validates the upload,
 * and moves it to the designated directory. If the upload is successful, it returns
 * the file path; otherwise, it logs an error and returns an empty string.
 * 
 * @return string The uploaded file path if successful, otherwise an empty string.
 */
function handleProductImageUpload()
{
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/products/'; // Define the upload directory
        $uploadFile = $uploadDir . basename($_FILES['productImage']['name']); // Set the destination path
        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadFile)) {
            return $uploadFile; // Return file path if upload is successful
        } else {
            handleError("Failed to upload image.", $_ENV['ENVIRONMENT']); // Log error if upload fails
        }
    }
    return ''; // Return an empty string if no valid file is uploaded
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
 * Deletes a product from the database, including its related category mappings.
 *
 * This function first validates the product ID, ensures a database connection,
 * and then removes the product from the `products` table along with its associated
 * category mappings in `product_category_mapping`. If the product is successfully
 * deleted, it commits the transaction; otherwise, it rolls back and returns an error message.
 *
 * @param int $id The ID of the product to be deleted.
 * @return array An associative array containing 'error' (boolean) and 'message' (string).
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

        $stmtMapping = $pdo->prepare("DELETE FROM product_category_mapping WHERE product_id=:id");
        $stmtMapping->execute(['id' => $id]);

        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id=:id");
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            return ['error' => false, 'message' => 'Produk berhasil dihapus.'];
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

/**
 * Converts a given string into a URL-friendly slug.
 * 
 * This function performs the following transformations:
 * 1. Replaces non-letter or non-digit characters with hyphens.
 * 2. Transliterates characters into ASCII characters.
 * 3. Removes unwanted characters (such as punctuation marks).
 * 4. Trims leading and trailing hyphens.
 * 5. Replaces multiple consecutive hyphens with a single hyphen.
 * 6. Converts the string to lowercase.
 * 
 * If the resulting string is empty, it returns 'untitled-product' as a fallback.
 *
 * @param string $text The input string to be converted.
 * @return string The generated URL-friendly slug.
 */
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text); // Replaces non-letter and non-digit characters with hyphens
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text); // Transliterates characters to ASCII
    $text = preg_replace('~[^-\w]+~', '', $text); // Removes unwanted characters (like punctuation)
    $text = trim($text, '-'); // Trims leading and trailing hyphens
    $text = preg_replace('~-+~', '-', $text); // Replaces multiple consecutive hyphens with one
    $text = strtolower($text); // Converts the string to lowercase

    return $text ?: 'untitled-product'; // Returns 'untitled-product' if the result is empty
}