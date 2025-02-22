<?php
require_once __DIR__ . '/../../vendor/autoload.php';

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
 * Validates product data.
 *
 * This function validates the product name, price, description, and slug.
 * It returns an array of violations or an empty array if the data is valid.
 *
 * @param array $data An associative array containing the product data (name, price_amount, currency, description, image_path, slug).
 * @return array Returns an array of validation violations or an empty array if the data is valid.
 */
function validateProductData($data)
{
    // Create a validator
    $validator = Validation::createValidator();

    // Define constraints for the data
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

    $violations = [];

    // Validate each field
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['name'], $constraints['name'])));
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['price_amount'], $constraints['price_amount'])));
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['currency'], $constraints['currency'])));
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['description'], $constraints['description'])));
    $violations = array_merge($violations, iterator_to_array($validator->validate($data['slug'], $constraints['slug'])));

    return $violations;
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
 * Validates an uploaded product image based on size, extension, MIME type, and dimensions.
 *
 * @param array $file The uploaded file data from $_FILES.
 * @param int $maxWidth Maximum allowed width of the image (default: 2000px).
 * @param int $maxHeight Maximum allowed height of the image (default: 2000px).
 * @return array Validation result with error status, message, and image details if valid.
 */
function validateProductImage($file, $maxWidth = 2000, $maxHeight = 2000)
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

    // Check if there is an upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => true, 'message' => 'Upload error occurred. Error code: ' . $file['error']];
    }

    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['error' => true, 'message' => 'File size exceeds the 2MB limit'];
    }

    // Validate file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['error' => true, 'message' => 'Invalid file format. Allowed: JPG, JPEG, PNG, WEBP'];
    }

    // Validate MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($detectedMimeType, $allowedMimeTypes)) {
        return ['error' => true, 'message' => 'Invalid MIME type detected: ' . $detectedMimeType];
    }

    // Validate image dimensions
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['error' => true, 'message' => 'Invalid image file'];
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    if ($width > $maxWidth || $height > $maxHeight) {
        return ['error' => true, 'message' => "Image dimensions exceed the maximum allowed size of {$maxWidth}x{$maxHeight}px"];
    }

    // Double-check image type based on extension
    $imageType = $imageInfo[2];
    if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
        return ['error' => true, 'message' => 'Invalid image format'];
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
    ];
}