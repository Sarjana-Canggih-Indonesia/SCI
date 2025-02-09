<?php
// image_function.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../user/user_function.php';

use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic as Image;

// Inisialisasi manager (bisa menggunakan GD atau Imagick)
$manager = new ImageManager('gd');

/**
 * Validates the file type to ensure it is a JPEG or PNG.
 * 
 * This function checks both the MIME type and the file extension to validate that the uploaded file is either a JPEG or PNG image.
 * 
 * @param array $file The uploaded file from the $_FILES array.
 * @return bool Returns true if the file is a valid image type (JPEG/PNG), false otherwise.
 */
function validateFileType(array $file): bool
{
    $allowedMimeTypes = ['image/jpeg', 'image/png']; // List of allowed MIME types
    $allowedExtensions = ['jpg', 'jpeg', 'png']; // List of allowed file extensions

    // Validate MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE); // Open fileinfo resource to check MIME type
    $detectedMimeType = finfo_file($finfo, $file['tmp_name']); // Get the MIME type of the file
    finfo_close($finfo); // Close the fileinfo resource

    // Validate file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // Get the file extension and convert to lowercase

    // Return true if both MIME type and file extension are valid
    return in_array($detectedMimeType, $allowedMimeTypes) && in_array($fileExtension, $allowedExtensions);
}

/**
 * Uploads an image to the server.
 * 
 * This function handles the process of uploading an image file to the server. It validates the file's type, ensures a directory exists to store the image, and moves the file to the specified directory. If the upload is successful, it returns the file path; otherwise, it returns false.
 * 
 * @param array $file The uploaded file from the $_FILES array.
 * @return string|false Returns the path of the uploaded image if successful, false if the upload fails.
 */
function uploadImage(array $file)
{
    if ($file['error'] !== UPLOAD_ERR_OK) { // Check for upload errors
        return false;
    }

    if (!validateFileType($file)) { // Validate the file type
        return false;
    }

    $uploadDir = __DIR__ . '/uploads/'; // Define the directory for storing the uploaded image
    if (!is_dir($uploadDir)) { // Check if the directory exists, if not create it
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('product_') . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // Generate a unique filename based on the original file extension
    $targetPath = $uploadDir . $filename; // Set the target path for the uploaded image

    if (move_uploaded_file($file['tmp_name'], $targetPath)) { // Move the file to the upload directory
        return $targetPath; // Return the file path if the upload is successful
    }

    return false; // Return false if the upload fails
}

/**
 * Uploads an image after validating its MIME type and ensuring the upload is successful.
 * 
 * This function validates the uploaded file by checking for any upload errors and ensures that the file's MIME type is either JPEG or PNG. The image is then saved to the server with a unique filename. If any errors occur during the process, they are logged, and the function returns false.
 * 
 * @param array $file The uploaded file from the $_FILES array.
 * @param \Intervention\Image\ImageManager $manager Instance of Intervention ImageManager.
 * @return string|false The path of the uploaded image if successful, false otherwise.
 */
function uploadImageWithIntervention($file, $manager)
{
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) { // Check if the file is valid and has no upload errors
        return false;
    }

    try {
        // Use Image::make() after importing the class
        $image = Image::make($file['tmp_name']);

        $allowedMimeTypes = ['image/jpeg', 'image/png']; // List of allowed MIME types
        $mimeType = $image->getMimeType(); // Correct method to get the MIME type

        if (!in_array($mimeType, $allowedMimeTypes)) { // Check if the MIME type is allowed
            return false;
        }

        $extensionMap = [ // Map MIME types to file extensions
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];
        $extension = $extensionMap[$mimeType] ?? ''; // Get the file extension based on the MIME type

        $filename = uniqid('product_') . '.' . $extension; // Generate a unique filename
        $path = __DIR__ . '/uploads/' . $filename; // Define the path to save the uploaded image

        $image->save($path); // Save the image to the server
        return $path; // Return the path of the uploaded image

    } catch (Exception $e) {
        error_log('Image error: ' . $e->getMessage()); // Log any errors encountered during the upload process
        return false; // Return false if there is an error
    }
}

/**
 * Resizes a product image to fit within the specified width and height.
 * 
 * This function resizes a given image to fit within the specified maximum width and height while maintaining its aspect ratio. It supports JPEG and PNG formats and handles transparency for PNG images. The resized image is saved to the original file path.
 * 
 * @param string $imagePath The path to the image file.
 * @param int $maxWidth The maximum width (default 800px).
 * @param int $maxHeight The maximum height (default 600px).
 * @return bool Returns true if the image was successfully resized, false otherwise.
 */
function resizeProductImage(string $imagePath, int $maxWidth = 800, int $maxHeight = 600): bool
{
    $imageType = exif_imagetype($imagePath); // Get the image type

    switch ($imageType) { // Create an image resource based on the image type
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($imagePath);
            break;
        default:
            return false;
    }

    $originalWidth = imagesx($image); // Get the original width
    $originalHeight = imagesy($image); // Get the original height

    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight); // Calculate the resize ratio
    $newWidth = (int) ($originalWidth * $ratio); // Calculate the new width
    $newHeight = (int) ($originalHeight * $ratio); // Calculate the new height

    $resizedImage = imagecreatetruecolor($newWidth, $newHeight); // Create a true color image resource for the resized image

    if ($imageType === IMAGETYPE_PNG) { // Handle transparency for PNG images
        imagealphablending($resizedImage, false); // Disable blending mode
        imagesavealpha($resizedImage, true); // Save the alpha channel (transparency)
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127); // Allocate transparent color
        imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent); // Fill the image with transparent color
    }

    imagecopyresampled( // Resize the image
        $resizedImage,
        $image,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $originalWidth,
        $originalHeight
    );

    $result = match ($imageType) { // Save the resized image based on the image type
        IMAGETYPE_JPEG => imagejpeg($resizedImage, $imagePath, 85),
        IMAGETYPE_PNG => imagepng($resizedImage, $imagePath, 9),
        default => false
    };

    imagedestroy($image); // Clean up the original image resource
    imagedestroy($resizedImage); // Clean up the resized image resource

    return $result !== false; // Return true if the image was saved successfully, false otherwise
}

/**
 * Resizes an image while maintaining the aspect ratio using the Intervention Image library.
 * 
 * This function resizes the image to fit within the specified maximum width and height, preserving the aspect ratio. It saves the resized image to the same path with a quality setting of 85. If an error occurs, it logs the error and returns false.
 * 
 * @param string $imagePath The path to the image file.
 * @param ImageManager $manager Instance of Intervention ImageManager.
 * @param int $maxWidth The maximum width (default 800px).
 * @param int $maxHeight The maximum height (default 600px).
 * @return bool Returns true if the image was successfully resized and saved, false otherwise.
 */
function resizeImageWithIntervention($imagePath, $manager, $maxWidth = 800, $maxHeight = 600)
{
    try {
        $image = Image::make($imagePath); // Load the image using the make method from ImageManager

        $image->resize($maxWidth, $maxHeight, function ($constraint) { // Resize the image while maintaining aspect ratio
            $constraint->aspectRatio(); // Maintain the aspect ratio of the image
            $constraint->upsize(); // Prevent resizing the image if it is smaller than the specified size
        });

        $image->save($imagePath, 85); // Save the resized image with 85 quality
        return true; // Return true if the image was successfully resized

    } catch (Exception $e) {
        error_log('Resize error: ' . $e->getMessage()); // Log any errors encountered during the resize process
        return false; // Return false if there is an error
    }
}
