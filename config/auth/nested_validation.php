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

class Address
{
    private $city;

    public function __construct($city)
    {
        $this->city = $city;
    }

    public function getCity()
    {
        return $this->city;
    }
}

class User
{
    private $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }

    public function getAddress()
    {
        return $this->address;
    }
}

// Buat validator instance tanpa enableAnnotationMapping
$validator = Validation::createValidator();

// Contoh validasi nested
$address = new Address(''); // city kosong, akan divalidasi
$user = new User($address);

$violations = $validator->validateProperty($user->getAddress(), 'city', [
    new Assert\NotBlank(['message' => 'City tidak boleh kosong.']),
]);

if (count($violations) > 0) {
    foreach ($violations as $violation) {
        echo $violation->getMessage() . PHP_EOL;
    }
} else {
    echo "Semua data valid!\n";
}
