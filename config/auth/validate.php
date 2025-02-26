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
 * Validates uploaded product images based on size, extension, MIME type, and dimensions.
 *
 * This function ensures that uploaded images meet specific validation criteria, including:
 * - Allowed file extensions (JPG, JPEG, PNG, WEBP)
 * - Allowed MIME types
 * - Maximum file size of 2MB
 * - Maximum image dimensions (2000x2000 pixels by default)
 * - The number of uploaded images (between 1 and 10)
 *
 * If an image fails validation, an error message is returned. Valid images return their temporary path, 
 * file extension, MIME type, and dimensions.
 *
 * @param array $files The array of uploaded file data from $_FILES.
 * @param int $maxWidth The maximum allowed width of the image (default: 2000px).
 * @param int $maxHeight The maximum allowed height of the image (default: 2000px).
 * @return array Validation results, including error status, message, and image details if valid.
 */
function validateProductImages($files, $maxWidth = 2000, $maxHeight = 2000)
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp']; // Allowed file extensions.
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp']; // Allowed MIME types.
    $maxFiles = 10; // Maximum number of images.
    $minFiles = 1; // Minimum number of images.
    $fileCount = count($files['name']); // Count the number of uploaded files.

    if ($fileCount < $minFiles || $fileCount > $maxFiles)
        return ['error' => true, 'message' => "Number of files must be between {$minFiles} and {$maxFiles}."];

    $results = [];

    for ($i = 0; $i < $fileCount; $i++) {
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) { // Check if an upload error occurred.
            $results[] = ['error' => true, 'message' => 'Upload error occurred. Error code: ' . $file['error']];
            continue;
        }

        if ($file['size'] > 2 * 1024 * 1024) { // Validate file size (2MB limit).
            $results[] = ['error' => true, 'message' => 'File size exceeds the 2MB limit'];
            continue;
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // Extract file extension.
        if (!in_array($fileExtension, $allowedExtensions)) { // Validate file extension.
            $results[] = ['error' => true, 'message' => 'Invalid file format. Allowed: JPG, JPEG, PNG, WEBP'];
            continue;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE); // Open file info resource.
        $detectedMimeType = finfo_file($finfo, $file['tmp_name']); // Get the actual MIME type.
        finfo_close($finfo);
        if (!in_array($detectedMimeType, $allowedMimeTypes)) { // Validate MIME type.
            $results[] = ['error' => true, 'message' => 'Invalid MIME type detected: ' . $detectedMimeType];
            continue;
        }

        $imageInfo = getimagesize($file['tmp_name']); // Get image dimensions and type.
        if (!$imageInfo) {
            $results[] = ['error' => true, 'message' => 'Invalid image file'];
            continue;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        if ($width > $maxWidth || $height > $maxHeight) { // Check image dimensions.
            $results[] = ['error' => true, 'message' => "Image dimensions exceed the maximum allowed size of {$maxWidth}x{$maxHeight}px"];
            continue;
        }

        if (!in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) { // Validate image type.
            $results[] = ['error' => true, 'message' => 'Invalid image format'];
            continue;
        }

        $results[] = [
            'error' => false,
            'message' => 'Valid image',
            'data' => [
                'tmp_path' => $file['tmp_name'],
                'extension' => $fileExtension,
                'mime_type' => $detectedMimeType,
                'dimensions' => ['width' => $width, 'height' => $height]
            ]
        ];
    }

    return $results;
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