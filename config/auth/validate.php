<?php
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * This function performs the task of loading environment variables from a .env file.
 * 1. Checks if the .env file has been loaded previously.
 * 2. If not loaded, attempts to load the .env file and set environment variables.
 * 3. If successful, marks the .env file as loaded to avoid reloading in future requests.
 */
$rootDir = __DIR__ . '/../../';
$dotenvFile = $rootDir . '.env';

// Step 1: Check if the .env file has already been loaded
if (getenv('ENV_LOADED')) {
    error_log('.env file already loaded, skipping...');
} else {
    // Step 2: Load the .env file if not loaded
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir);

    if (!file_exists($dotenvFile) || !$dotenv->load()) {
        $errorMessage = '.env file not found or failed to load';
        error_log($errorMessage);
        exit;
    } else {
        // Step 3: Mark that the .env file is loaded by setting ENV_LOADED environment variable
        putenv('ENV_LOADED=true');
        $successMessage = '.env file loaded successfully';
        error_log($successMessage);
    }
}

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validates the username based on a set of constraints.
 * 
 * Task:
 * 1. Validates that the username is not blank.
 * 2. Ensures that the username is between 3 and 20 characters in length.
 * 3. Validates that the username contains only alphanumeric characters and hyphens.
 * 4. Ensures the username does not start or end with a hyphen or space.
 * 
 * @param string $username The username to be validated.
 * 
 * @return \Symfony\Component\Validator\ConstraintViolationList The list of validation violations.
 */
function validateUsername($username)
{
    // Create a new validator instance
    $validator = Validation::createValidator();

    // Define the validation constraints for the username
    $usernameConstraint = new Assert\Collection([
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

    // Validate the username using the defined constraints
    $violations = $validator->validate(['username' => $username], $usernameConstraint);

    // Return the list of violations, if any
    return $violations;
}

/**
 * Validates the password based on a set of constraints.
 * 
 * Task:
 * 1. Validates that the password is not blank.
 * 2. Ensures that the password is between 8 and 20 characters in length.
 * 3. Ensures that the password contains at least one uppercase letter.
 * 4. Ensures that the password contains at least one lowercase letter.
 * 5. Ensures that the password contains at least one number.
 * 
 * @param string $password The password to be validated.
 * 
 * @return \Symfony\Component\Validator\ConstraintViolationList The list of validation violations.
 */
function validatePassword($password)
{
    // Create a new validator instance
    $validator = Validation::createValidator();

    // Define the validation constraints for the password
    $passwordConstraint = new Assert\Collection([
        'fields' => [
            'password' => [
                new Assert\NotBlank(['message' => 'Password cannot be blank.']), // Ensure password is not blank
                new Assert\Length([
                    'min' => 8,
                    'max' => 20,
                    'minMessage' => 'Password must be at least {{ limit }} characters long.',
                    'maxMessage' => 'Password can be a maximum of {{ limit }} characters long.',
                ]), // Check the length of the password
                new Assert\Regex([
                    'pattern' => '/[A-Z]/', // Ensure the password contains at least one uppercase letter
                    'message' => 'Password must contain at least one uppercase letter.',
                ]), // Uppercase letter check
                new Assert\Regex([
                    'pattern' => '/[a-z]/', // Ensure the password contains at least one lowercase letter
                    'message' => 'Password must contain at least one lowercase letter.',
                ]), // Lowercase letter check
                new Assert\Regex([
                    'pattern' => '/\d/', // Ensure the password contains at least one number
                    'message' => 'Password must contain at least one number.',
                ]), // Number check
            ]
        ]
    ]);

    // Validate the password using the defined constraints
    $violations = $validator->validate(['password' => $password], $passwordConstraint);

    // Return the list of violations, if any
    return $violations;
}

/**
 * Validates the email address based on a set of constraints.
 * 
 * Task:
 * 1. Validates that the email is not blank.
 * 2. Validates that the email follows the correct email format.
 * 
 * @param string $email The email address to be validated.
 * 
 * @return \Symfony\Component\Validator\ConstraintViolationList The list of validation violations.
 */
function validateEmail($email)
{
    // Create a new validator instance
    $validator = Validation::createValidator();

    // Define the validation constraints for the email
    $emailConstraint = new Assert\Collection([
        'fields' => [
            'email' => [
                new Assert\NotBlank(['message' => 'Email cannot be blank.']), // Ensure email is not blank
                new Assert\Email(['message' => 'Invalid email format.']), // Validate the email format
            ]
        ]
    ]);

    // Validate the email using the defined constraints
    $violations = $validator->validate(['email' => $email], $emailConstraint);

    // Return the list of violations, if any
    return $violations;
}