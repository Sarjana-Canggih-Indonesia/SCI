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