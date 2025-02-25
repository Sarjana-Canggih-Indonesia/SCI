<?php
// tag_functions.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/validate.php';

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
 * This function updates the name of a tag in the `tags` table based on the provided tag ID.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param int $tagId The ID of the tag to update.
 * @param string $newTagName The new name for the tag.
 * @return bool Returns true if the update was successful, otherwise false.
 */
function updateTag(PDO $pdo, int $tagId, string $newTagName)
{
    // Sanitize the input to prevent XSS and SQL injection
    $newTagName = sanitize_input($newTagName);

    try {
        // Prepare the SQL statement for updating a tag
        $stmt = $pdo->prepare("UPDATE tags SET tag_name = :tag_name WHERE tag_id = :tag_id");
        $stmt->bindParam(':tag_name', $newTagName, PDO::PARAM_STR);
        $stmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);

        // Execute the statement and return true if the update was successful
        if ($stmt->execute()) {
            return true;
        }
    } catch (PDOException $e) {
        // Log the error and handle it appropriately
        handleError("Error updating tag: " . $e->getMessage(), isLive() ? 'live' : 'local');
    }

    return false; // Return false if the update failed
}

/**
 * Deletes a tag from the database.
 *
 * This function removes a tag from the `tags` table based on the provided tag ID.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param int $tagId The ID of the tag to delete.
 * @return bool Returns true if the deletion was successful, otherwise false.
 */
function deleteTag(PDO $pdo, int $tagId)
{
    try {
        // Prepare the SQL statement for deleting a tag
        $stmt = $pdo->prepare("DELETE FROM tags WHERE tag_id = :tag_id");
        $stmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);

        // Execute the statement and return true if the deletion was successful
        if ($stmt->execute()) {
            return true;
        }
    } catch (PDOException $e) {
        // Log the error and handle it appropriately
        handleError("Error deleting tag: " . $e->getMessage(), isLive() ? 'live' : 'local');
    }

    return false; // Return false if the deletion failed
}