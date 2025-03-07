<?php
// validate.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../database/database-config.php';

// Memuat konfigurasi lingkungan
$config = getEnvironmentConfig();
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';
$pdo = getPDOConnection($config, $env);

/**
 * Loads environment variables from a .env file.
 * 
 * @return void
 */
$rootDir = __DIR__ . '/../../';
$dotenvFile = $rootDir . '.env';
if (getenv('ENV_LOADED')) {
    error_log('.env file already loaded, skipping...');
} else {
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
    if (!file_exists($dotenvFile) || !$dotenv->load()) {
        error_log('.env file not found or failed to load');
        exit;
    } else {
        putenv('ENV_LOADED=true');
        error_log('.env file loaded successfully');
    }
}

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints\Choice;
use Brick\Money\Money;
use Brick\Money\Currency;

/**
 * Validates the username based on a set of constraints.
 *
 * @param string $username The username to be validated.
 * @return \Symfony\Component\Validator\ConstraintViolationList The list of validation violations.
 */
function validateUsername($username)
{
    $validator = Validation::createValidator(); // Create a new validator instance
    $usernameConstraint = new Assert\Collection([ // Define validation constraints
        'fields' => [
            'username' => [
                new Assert\NotBlank(['message' => 'Username cannot be blank.']), // Ensure username is not blank
                new Assert\Length([
                    'min' => 3,
                    'max' => 20,
                    'minMessage' => 'Username must be at least {{ limit }} characters long.',
                    'maxMessage' => 'Username can be a maximum of {{ limit }} characters long.',
                ]), // Check the length of the username
                new Assert\Regex([
                    'pattern' => '/^[a-zA-Z0-9-]+$/', // Only alphanumeric characters and hyphens
                    'message' => 'Username can only contain letters, numbers, and hyphens.',
                ]), // Ensure the username only contains valid characters
                new Assert\Regex([
                    'pattern' => '/^[^-\s].*[^-\s]$/', // Ensure username does not start or end with hyphen or space
                    'message' => 'Username cannot start or end with a hyphen or space.',
                ]), // Prevent hyphen or space at the start or end
            ]
        ]
    ]);
    $violations = $validator->validate(['username' => $username], $usernameConstraint); // Validate the username
    return $violations; // Return validation violations if any
}

/**
 * Validates the password based on a set of constraints.
 *
 * This function ensures that the password is not blank, is between 6 and 20 characters in length,
 * contains at least one uppercase letter, one lowercase letter, and one number.
 *
 * @param string $password The password to be validated.
 * @return \Symfony\Component\Validator\ConstraintViolationList The list of validation violations.
 */
function validatePassword($password)
{
    $validator = Validation::createValidator(); // Create a new validator instance

    // Define validation constraints for the password
    $passwordConstraint = new Assert\Collection([
        'fields' => [
            'password' => [
                new Assert\NotBlank(['message' => 'Password cannot be blank.']), // Ensure password is not blank
                new Assert\Length([
                    'min' => 6,
                    'max' => 20, // Minimum length changed to 6 characters
                    'minMessage' => 'Password must be at least {{ limit }} characters long.',
                    'maxMessage' => 'Password can be a maximum of {{ limit }} characters long.',
                ]), // Validate password length
                new Assert\Regex(['pattern' => '/[A-Z]/', 'message' => 'Password must contain at least one uppercase letter.']), // Uppercase check
                new Assert\Regex(['pattern' => '/[a-z]/', 'message' => 'Password must contain at least one lowercase letter.']), // Lowercase check
                new Assert\Regex(['pattern' => '/\d/', 'message' => 'Password must contain at least one number.']), // Number check
            ]
        ]
    ]);

    $violations = $validator->validate(['password' => $password], $passwordConstraint); // Validate password
    return $violations; // Return validation violations
}

/**
 * Validates the email address based on a set of constraints.
 *
 * @param string $email The email address to be validated.
 * @return \Symfony\Component\Validator\ConstraintViolationList The list of validation violations.
 */
function validateEmail($email)
{
    $validator = Validation::createValidator(); // Create a new validator instance
    $emailConstraint = new Assert\Collection([ // Define validation constraints
        'fields' => [
            'email' => [
                new Assert\NotBlank(['message' => 'Email cannot be blank.']), // Ensure email is not blank
                new Assert\Email(['message' => 'Invalid email format.']), // Validate email format
            ]
        ]
    ]);
    $violations = $validator->validate(['email' => $email], $emailConstraint); // Validate email
    return $violations; // Return validation violations if any
}

/**
 * Validates product data using Symfony Validator constraints.
 *
 * This function ensures that the product data meets the required validation rules:
 * - `name`: Must not be blank.
 * - `price_amount`: Must not be blank, must be an integer, and must be positive or zero.
 * - `currency`: Must not be blank.
 * - `description`: Must not be blank.
 * - `slug`: Must match the pattern `/^[a-z0-9-]+$/`.
 *
 * @param array $data The product data to be validated.
 * @return array An array of constraint violations. If empty, the data is valid.
 */
function validateProductData($data)
{
    $validator = Validation::createValidator(); // Initialize the validator instance.

    // Define validation constraints for product data fields.
    $constraints = [
        'name' => new Assert\NotBlank(['message' => 'Product name cannot be blank']),
        'price_amount' => [
            new Assert\NotBlank(['message' => 'Price amount cannot be blank']),
            new Assert\Type(['type' => 'integer', 'message' => 'Price amount must be an integer']),
            new Assert\PositiveOrZero(['message' => 'Price amount must be a positive number or zero']),
        ],
        'currency' => new Assert\NotBlank(['message' => 'Currency cannot be blank']),
        'description' => new Assert\NotBlank(['message' => 'Description cannot be blank']),
        'slug' => new Assert\Regex([
            'pattern' => '/^[a-z0-9-]+$/',
            'message' => 'Slug can only contain lowercase letters, numbers, and dashes'
        ])
    ];

    $violations = []; // Store validation errors if any.

    // Validate each field and merge the validation violations into the array.
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['name'], $constraints['name'])));
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['price_amount'], $constraints['price_amount'])));
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['currency'], $constraints['currency'])));
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['description'], $constraints['description'])));
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['slug'], $constraints['slug'])));

    return $violations; // Return an array of violations, empty if validation passes.
}

/**
 * Validates the price using Brick Money.
 *
 * This function validates the price to ensure it's a valid money format.
 * It returns the validated Money object or throws an exception if invalid.
 *
 * @param float|string $price The price to validate.
 * @param string $currency The currency code (e.g., 'IDR', 'USD').
 * @return Money Returns a validated Money object.
 * @throws \InvalidArgumentException if the price or currency is invalid.
 */
function validatePrice($price, $currency)
{
    try {
        // Validate currency
        $currencyObject = Currency::of($currency);

        // Validate price using Brick Money
        return Money::of($price, $currencyObject);
    } catch (\InvalidArgumentException $e) {
        throw new \InvalidArgumentException("Invalid price or currency format: " . $e->getMessage());
    }
}

/**
 * Validates the number of uploaded files against a specified range.
 *
 * This function checks whether the given file count falls within the acceptable range 
 * defined by the minimum and maximum file limits. If the file count is out of range, 
 * an error response is returned. Otherwise, it indicates a successful validation.
 *
 * @param int $fileCount The number of uploaded files.
 * @param int $minFiles The minimum number of files allowed (default: 1).
 * @param int $maxFiles The maximum number of files allowed (default: 10).
 * 
 * @return array An associative array with an 'error' key indicating success or failure, 
 *               and a 'message' key providing details in case of an error.
 */
function validateFileCount($fileCount, $minFiles = 1, $maxFiles = 10)
{
    // Check if the number of files is outside the allowed range
    if ($fileCount < $minFiles || $fileCount > $maxFiles) {
        return ['error' => true, 'message' => "Number of files must be between {$minFiles} and {$maxFiles}."]; // Return error response if out of range
    }
    return ['error' => false]; // Return success response if within range
}

/**
 * Validates a single uploaded image file based on size, format, MIME type, and dimensions.
 *
 * This function checks whether the uploaded file meets the required constraints, including:
 * - No upload errors
 * - File size within the allowed limit
 * - Allowed file extensions (e.g., JPG, PNG, WEBP)
 * - Valid MIME type
 * - Valid image dimensions within the specified maximum width and height
 *
 * If any validation fails, an error response is returned. Otherwise, the function provides 
 * details about the uploaded file.
 *
 * @param array $file The uploaded file from `$_FILES`.
 * @param array $allowedExtensions An array of allowed file extensions.
 * @param array $allowedMimeTypes An array of allowed MIME types.
 * @param int $maxSize Maximum allowed file size in bytes.
 * @param int $maxWidth Maximum allowed image width in pixels.
 * @param int $maxHeight Maximum allowed image height in pixels.
 * 
 * @return array An associative array containing 'error' (boolean) and 'message' (string).
 *               If successful, it also returns 'data' containing file details.
 */
function validateUploadedImage($file, $allowedExtensions, $allowedMimeTypes, $maxSize, $maxWidth, $maxHeight)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => true, 'message' => 'Upload error occurred. Error code: ' . $file['error']]; // Return error if upload failed
    }

    if ($file['size'] > $maxSize) {
        return ['error' => true, 'message' => 'File size exceeds the 2MB limit']; // Check if file size exceeds limit
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['error' => true, 'message' => 'Invalid file format. Allowed: JPG, JPEG, PNG, WEBP']; // Validate file extension
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($detectedMimeType, $allowedMimeTypes)) {
        return ['error' => true, 'message' => 'Invalid MIME type detected: ' . $detectedMimeType]; // Validate MIME type
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['error' => true, 'message' => 'Invalid image file']; // Check if file is a valid image
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    if ($width > $maxWidth || $height > $maxHeight) {
        return ['error' => true, 'message' => "Image dimensions exceed the maximum allowed size of {$maxWidth}x{$maxHeight}px"]; // Validate image dimensions
    }

    if (!in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
        return ['error' => true, 'message' => 'Invalid image format']; // Ensure image format is supported
    }

    return [
        'error' => false,
        'message' => 'Valid image',
        'data' => [
            'tmp_path' => $file['tmp_name'],
            'extension' => $fileExtension,
            'mime_type' => $detectedMimeType,
            'dimensions' => ['width' => $width, 'height' => $height]
        ]
    ]; // Return valid response with image details
}

/**
 * Validates multiple uploaded product images based on size, format, MIME type, and dimensions.
 *
 * This function ensures that the uploaded images meet the specified criteria:
 * - The number of files must be within the allowed range.
 * - Each file must have an allowed extension (JPG, JPEG, PNG, WEBP).
 * - Each file must have a valid MIME type.
 * - The file size must not exceed 2MB.
 * - The image dimensions must not exceed the maximum width and height.
 *
 * @param array $files The uploaded files array from `$_FILES`.
 * @param int $maxWidth Maximum allowed image width in pixels (default: 2000px).
 * @param int $maxHeight Maximum allowed image height in pixels (default: 2000px).
 * 
 * @return array An array of validation results for each image.
 */
function validateProductImages($files, $maxWidth = 2000, $maxHeight = 2000)
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp']; // Allowed file extensions
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp']; // Allowed MIME types
    $maxFiles = 10; // Maximum number of files allowed
    $minFiles = 1; // Minimum number of files required
    $maxSize = 2 * 1024 * 1024; // 2MB maximum file size

    $fileCount = count($files['name']); // Get the number of uploaded files

    // Validate the number of uploaded files
    $fileCountValidation = validateFileCount($fileCount, $minFiles, $maxFiles);
    if ($fileCountValidation['error']) {
        return [$fileCountValidation]; // Return validation error if file count is invalid
    }

    $results = [];

    for ($i = 0; $i < $fileCount; $i++) {
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ]; // Extract individual file data

        // Validate each file
        $results[] = validateUploadedImage($file, $allowedExtensions, $allowedMimeTypes, $maxSize, $maxWidth, $maxHeight);
    }

    return $results; // Return validation results for all files
}

/**
 * Validate a tag name based on length and format.
 *
 * This function checks whether the given tag name meets the specified criteria:
 * - The length must not exceed 255 characters.
 * - It must contain only letters (A-Z, a-z) and hyphens (-).
 * If the tag name is invalid, an error is logged using `handleError`, and the function returns false.
 *
 * @param string $tagName The tag name to validate.
 * @return bool Returns true if the tag name is valid, otherwise false.
 */
function validateTag(string $tagName, string $env): bool
{
    if (strlen($tagName) > 255) { // Ensure the tag name does not exceed 255 characters.
        handleError("Tag name '$tagName' cannot exceed 255 characters.", $env);
        return false;
    }

    if (!preg_match('/^[a-zA-Z-]+$/', $tagName)) { // Ensure the tag name contains only letters and hyphens.
        handleError("Tag name '$tagName' can only contain letters and hyphens.", $env);
        return false;
    }

    return true; // Return true if the tag name passes all validations.
}

/**
 * Validate an array of tag names.
 *
 * This function ensures that at least one tag name is provided and that all tag names
 * meet the validation criteria defined in the `validateTag` function.
 * If any tag name is invalid, the function returns false.
 *
 * @param array $tagNames An array of tag names to validate.
 * @return bool Returns true if all tag names are valid, otherwise false.
 */
function validateTags(array $tagNames, string $env): bool
{
    if (empty($tagNames)) { // Ensure at least one tag is provided.
        handleError("At least one tag must be provided.", $env);
        return false;
    }

    foreach ($tagNames as $tagName) { // Loop through each tag and validate it.
        if (!validateTag($tagName, $env))
            return false; // Stop validation immediately if any tag is invalid.
    }

    return true; // Return true if all tags pass validation.
}

/**
 * Validates the user role against allowed values from the database.
 *
 * @param string $role The role to validate.
 * @param PDO $pdo The active PDO database connection.
 * @param string $env The environment (local/live).
 * @return bool Returns true if valid, otherwise false.
 */
function validateUserRole($role, PDO $pdo, string $env)
{
    $allowedRoles = getAllowedRolesFromDB($pdo, $env);

    if (empty($allowedRoles)) {
        handleError("Failed to fetch allowed roles from database.", $env);
        return false;
    }

    $validator = Validation::createValidator();
    $constraint = new Choice([
        'choices' => $allowedRoles,
        'message' => 'Invalid role: {{ value }}. Allowed roles are: ' . implode(", ", $allowedRoles),
    ]);

    $violations = $validator->validate($role, $constraint);

    if (count($violations) > 0) {
        handleError($violations[0]->getMessage(), $env);
        return false;
    }

    return true;
}

/**
 * Verifies the HTTP request method and ensures it matches the allowed method.
 *
 * This function checks the request method used in the HTTP request. If it does not match
 * the expected method, it sends an HTTP 405 (Method Not Allowed) response, returns a JSON-encoded
 * error message, and terminates script execution.
 *
 * @param string $allowedMethod The HTTP method that is allowed (e.g., 'GET', 'POST').
 */
function verifyHttpMethod($allowedMethod)
{
    if ($_SERVER['REQUEST_METHOD'] !== $allowedMethod) {
        http_response_code(405); // Set HTTP response status to 405 Method Not Allowed
        echo json_encode([
            'success' => false,
            'message' => "Only $allowedMethod requests are allowed." // Inform the client of the allowed HTTP method
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // Prevent escaping of slashes and Unicode characters
        exit(); // Stop script execution to prevent further processing
    }
}

/**
 * Validates product image count constraints
 * 
 * @param PDO $pdo Koneksi database
 * @param int $product_id ID produk
 * @param array $images_to_delete Gambar yang akan dihapus
 * @param array $new_images Gambar baru
 * @return array Array of ValidationViolation objects
 */
function validateProductImageCount(PDO $pdo, $product_id, $images_to_delete, $new_images)
{
    $violations = [];

    try {
        $currentImageCount = getCurrentImageCount($pdo, $product_id);
        $remainingAfterDelete = $currentImageCount - count($images_to_delete);
        $totalAfterUpdate = $remainingAfterDelete + count($new_images);

        if ($totalAfterUpdate < 1) {
            $violations[] = new Symfony\Component\Validator\ConstraintViolation(
                'Produk harus memiliki minimal 1 gambar',
                '',
                [],
                '',
                '',
                ''
            );
        }
    } catch (RuntimeException $e) {
        $violations[] = new Symfony\Component\Validator\ConstraintViolation(
            'Gagal memvalidasi gambar: ' . $e->getMessage(),
            '',
            [],
            '',
            '',
            ''
        );
    }

    return $violations;
}

/**
 * Retrieves the current number of images associated with a product
 * 
 * @param PDO $pdo Koneksi database yang valid
 * @param int $product_id ID produk yang akan dicek
 * @return int Jumlah gambar saat ini
 * @throws RuntimeException Jika terjadi error database
 */
function getCurrentImageCount(PDO $pdo, $product_id)
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        throw new RuntimeException("Gagal mengambil jumlah gambar: " . $e->getMessage());
    }
}