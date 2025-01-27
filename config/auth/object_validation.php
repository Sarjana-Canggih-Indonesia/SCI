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

class User
{
    private $username;
    private $email;
    private $password;

    public function __construct($username, $email, $password)
    {
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }
}

// Buat validator instance tanpa enableAnnotationMapping
$validator = Validation::createValidator();

// Contoh validasi objek
$user = new User('', 'invalid-email', '123'); // Username kosong, email tidak valid, password terlalu pendek

// Validasi untuk username
$violations = $validator->validateProperty($user, 'username', [
    new Assert\NotBlank(['message' => 'Username tidak boleh kosong.']),
    new Assert\Length(['min' => 3, 'max' => 50, 'minMessage' => 'Username minimal {{ limit }} karakter.']),
]);

// Validasi untuk email
$emailViolations = $validator->validateProperty($user, 'email', [
    new Assert\NotBlank(['message' => 'Email tidak boleh kosong.']),
    new Assert\Email(['message' => 'Email tidak valid.']),
]);

// Validasi untuk password
$passwordViolations = $validator->validateProperty($user, 'password', [
    new Assert\NotBlank(['message' => 'Password tidak boleh kosong.']),
    new Assert\Length(['min' => 8, 'minMessage' => 'Password minimal {{ limit }} karakter.']),
]);

// Tampilkan hasil validasi
$allViolations = array_merge(iterator_to_array($violations), iterator_to_array($emailViolations), iterator_to_array($passwordViolations));

if (count($allViolations) > 0) {
    foreach ($allViolations as $violation) {
        echo $violation->getMessage() . PHP_EOL;
    }
} else {
    echo "Semua data valid!\n";
}
