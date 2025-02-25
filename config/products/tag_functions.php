<?php
// tag_functions.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/validate.php';
require_once __DIR__ . '/../auth/admin_functions.php';

/**
 * Inserts multiple unique tags into the database.
 *
 * This function takes an array of tag names, validates them, sanitizes them, and inserts only unique ones into the database.
 * If a tag already exists, it is skipped, and its existence is logged. The function returns an array of newly created tag IDs
 * or `false` if no new tags were added.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param array $tagNames An array containing the tag names to be created.
 * @return array|false Returns an array of newly created tag IDs or `false` if no new tags were inserted.
 */
function createTags(PDO $pdo, array $tagNames)
{
    if (!validateTags($tagNames))
        return false; // Validate tag names; return false if validation fails.
    $tagIds = []; // Array to store newly created tag IDs.
    $existingTags = []; // Array to store tags that already exist.

    try {
        $stmt = $pdo->prepare("INSERT INTO tags (tag_name) VALUES (:tag_name)"); // Prepare SQL statement for inserting a tag.

        foreach ($tagNames as $tagName) {
            $tagName = strtolower($tagName); // Convert tag name to lowercase for consistency.
            $tagName = sanitize_input($tagName); // Sanitize input to prevent XSS attacks.

            $checkStmt = $pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name"); // Check if the tag already exists.
            $checkStmt->bindParam(':tag_name', $tagName, PDO::PARAM_STR);
            $checkStmt->execute();

            if ($checkStmt->fetch()) { // If the tag exists, add it to the existingTags array and skip insertion.
                $existingTags[] = $tagName;
                continue;
            }

            $stmt->bindParam(':tag_name', $tagName, PDO::PARAM_STR); // Bind the sanitized tag name for insertion.
            if ($stmt->execute())
                $tagIds[] = $pdo->lastInsertId(); // Store the new tag's ID if successfully inserted.
        }

        if (!empty($existingTags)) { // Log existing tags that were skipped.
            handleError("The following tags already exist: " . implode(", ", $existingTags), getEnvironmentConfig()['is_live'] ? 'live' : 'local');
        }

        return !empty($tagIds) ? $tagIds : false; // Return the array of newly created tag IDs or `false` if none were inserted.
    } catch (PDOException $e) {
        handleError("Database error: " . $e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Log database errors.
        return false;
    }
}

/**
 * Retrieves a tag by its ID from the database.
 *
 * This function fetches a tag from the `tags` table based on the provided tag ID.
 * The returned array contains the following keys:
 * - `tag_id` (int): The ID of the tag.
 * - `tag_name` (string): The name of the tag.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param int $tagId The ID of the tag to retrieve.
 * @return array|null Returns an associative array containing the tag data, or null if the tag is not found.
 */
function getTagById(PDO $pdo, int $tagId): ?array
{
    try {
        // Set PDO to throw exceptions on error
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL statement for fetching a tag by ID
        $stmt = $pdo->prepare("SELECT tag_id, tag_name FROM tags WHERE tag_id = :tag_id");
        $stmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the tag as an associative array
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error and handle it appropriately
        handleError("Error fetching tag: " . $e->getMessage(), isLive() ? 'live' : 'local');
        return null; // Return null if an error occurred
    }

    return $result ?: null; // Return the result or null if the tag is not found
}

/**
 * Retrieves all tags from the database.
 *
 * This function fetches all tags from the `tags` table.
 * Each tag in the returned array contains the following keys:
 * - `tag_id` (int): The ID of the tag.
 * - `tag_name` (string): The name of the tag.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @return array Returns an array of associative arrays containing all tags.
 */
function getAllTags(PDO $pdo): array
{
    try {
        // Set PDO to throw exceptions on error
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL statement for fetching all tags
        $stmt = $pdo->prepare("SELECT tag_id, tag_name FROM tags");
        $stmt->execute();

        // Fetch all tags as an array of associative arrays
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error and handle it appropriately
        handleError("Error fetching tags: " . $e->getMessage(), isLive() ? 'live' : 'local');
        return []; // Return an empty array if an error occurred
    }

    return $result; // Return the result or an empty array if no tags are found
}

/**
 * Updates an existing tag in the database.
 *
 * This function updates the name of a tag identified by its tag ID in the `tags` table.
 * It ensures that the new tag name is valid and does not already exist under a different ID to maintain data integrity.
 * If the new tag name is invalid or already exists, the update is prevented, and an error is logged.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param int $tagId The ID of the tag to be updated.
 * @param string $newTagName The new tag name to be assigned.
 * @return bool Returns `true` if the update was successful, otherwise `false`.
 */
function updateTag(PDO $pdo, int $tagId, string $newTagName)
{
    $newTagName = strtolower($newTagName); // Convert tag name to lowercase for consistency.
    $newTagName = sanitize_input($newTagName); // Sanitize input to prevent XSS and SQL injection risks.

    if (!validateTag($newTagName))
        return false; // Validate the new tag name; return false if invalid.

    try {
        $checkStmt = $pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name AND tag_id != :tag_id"); // Check if the new tag name already exists under a different ID.
        $checkStmt->bindParam(':tag_name', $newTagName, PDO::PARAM_STR);
        $checkStmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->fetch()) { // If a duplicate tag exists, log an error and prevent the update.
            handleError("Cannot update tag: The tag name '$newTagName' already exists.", getEnvironmentConfig()['is_live'] ? 'live' : 'local');
            return false;
        }

        $stmt = $pdo->prepare("UPDATE tags SET tag_name = :tag_name WHERE tag_id = :tag_id"); // Prepare update statement.
        $stmt->bindParam(':tag_name', $newTagName, PDO::PARAM_STR);
        $stmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);

        if ($stmt->execute() && $stmt->rowCount() > 0)
            return true; // Execute update and check if any rows were affected.
        return false;
    } catch (PDOException $e) {
        handleError("Database error: " . $e->getMessage(), getEnvironmentConfig()['is_live'] ? 'live' : 'local'); // Log database errors.
        return false;
    }
}

/**
 * Deletes a tag from the database based on the given tag ID.
 *
 * This function first validates the tag ID, then attempts to delete the tag
 * from the database using a prepared statement. If the deletion is successful,
 * it logs the action and returns true. If an error occurs, it is handled and rethrown.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param int $tagId The ID of the tag to be deleted.
 * @param int $adminId The ID of the admin performing the deletion.
 * @return bool Returns true if the tag was deleted successfully, false otherwise.
 * @throws PDOException If a database error occurs, the exception is rethrown.
 */
function deleteTag(PDO $pdo, int $tagId, int $adminId): bool
{
    if ($tagId <= 0)
        return false; // Validate tag ID: must be greater than zero.

    try {
        $stmt = $pdo->prepare("DELETE FROM tags WHERE tag_id = :tag_id"); // Prepare the DELETE query.
        $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT); // Bind tag ID to the query.

        if ($stmt->execute()) { // Execute the query.
            if ($stmt->rowCount() > 0) { // Check if any row was affected.
                logAdminAction(
                    $adminId,
                    'delete',
                    'tags',
                    $tagId,
                    "Tag with ID $tagId deleted successfully."
                ); // Log the deletion action with admin ID.
                return true; // Return true if deletion was successful.
            }
        }
    } catch (PDOException $e) {
        handleError("Error deleting tag with ID $tagId: " . $e->getMessage(), isLive() ? 'live' : 'local'); // Handle and log the error.
        throw $e; // Rethrow the exception for further handling.
    }
    return false; // Return false if deletion failed.
}