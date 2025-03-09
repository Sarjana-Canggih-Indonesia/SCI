<?php
// tag_functions.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/validate.php';
require_once __DIR__ . '/../auth/admin_functions.php';

/**
 * Inserts multiple unique tags into the database.
 *
 * This function validates and sanitizes an array of tag names before inserting them into the database.
 * If a tag already exists, it is skipped, and its existence is logged. The function returns an array of 
 * newly created tag IDs or `false` if no new tags were inserted.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param array $tagNames An array containing the tag names to be created.
 * @param string $env The environment setting ('local' or 'live').
 * @return array|false Returns an array of newly created tag IDs or `false` if no new tags were inserted.
 */
function createTags(PDO $pdo, array $tagNames, $env)
{
    if (!validateTags($tagNames, $env))
        return false; // Validate tag names; return false if validation fails.

    $tagIds = []; // Store newly created tag IDs.
    $existingTags = []; // Store tags that already exist.

    try {
        $stmt = $pdo->prepare("INSERT INTO tags (tag_name) VALUES (:tag_name)"); // Prepare SQL statement for inserting a tag.

        foreach ($tagNames as $tagName) {
            $tagName = strtolower($tagName); // Convert tag name to lowercase for consistency.
            $tagName = sanitize_input($tagName); // Sanitize input to prevent XSS attacks.

            // Check if the tag already exists in the database
            $checkStmt = $pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name");
            $checkStmt->bindParam(':tag_name', $tagName, PDO::PARAM_STR);
            $checkStmt->execute();

            if ($checkStmt->fetch()) { // If the tag exists, add it to the existingTags array and skip insertion.
                $existingTags[] = $tagName;
                continue;
            }

            // Insert the sanitized tag name into the database
            $stmt->bindParam(':tag_name', $tagName, PDO::PARAM_STR);
            if ($stmt->execute())
                $tagIds[] = $pdo->lastInsertId(); // Store the new tag's ID if successfully inserted.
        }

        // Log existing tags that were skipped to notify the admin
        if (!empty($existingTags))
            handleError("The following tags already exist: " . implode(", ", $existingTags), $env);

        return !empty($tagIds) ? $tagIds : false; // Return the array of newly created tag IDs or `false` if none were inserted.
    } catch (PDOException $e) {
        handleError("Database error: " . $e->getMessage(), $env); // Handle database errors.
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
 * @param string $env The environment setting ('local' or 'live').
 * @return bool Returns `true` if the update was successful, otherwise `false`.
 */
function updateTag(PDO $pdo, int $tagId, string $newTagName, string $env)
{
    $newTagName = strtolower($newTagName); // Convert tag name to lowercase for consistency.
    $newTagName = sanitize_input($newTagName); // Sanitize input to prevent XSS and SQL injection risks.

    if (!validateTag($newTagName, $env))
        return false; // Validate the new tag name; return false if invalid.

    try {
        // Check if the new tag name already exists under a different ID
        $checkStmt = $pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name AND tag_id != :tag_id");
        $checkStmt->bindParam(':tag_name', $newTagName, PDO::PARAM_STR);
        $checkStmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->fetch()) { // If a duplicate tag exists, log an error and prevent the update.
            handleError("Cannot update tag: The tag name '$newTagName' already exists.", $env);
            return false;
        }

        // Prepare update statement
        $stmt = $pdo->prepare("UPDATE tags SET tag_name = :tag_name WHERE tag_id = :tag_id");
        $stmt->bindParam(':tag_name', $newTagName, PDO::PARAM_STR);
        $stmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);

        if ($stmt->execute() && $stmt->rowCount() > 0)
            return true; // Execute update and check if any rows were affected.
        return false;
    } catch (PDOException $e) {
        handleError("Database error: " . $e->getMessage(), $env); // Handle database errors.
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

/**
 * Retrieves the tag names associated with a specific product.
 *
 * This function fetches all tag names linked to the given product ID from the database. 
 * It performs an INNER JOIN between the `tags` table and the `product_tag_mapping` table 
 * to find relevant tags.
 *
 * @param int $productId The unique identifier of the product.
 * @param PDO $pdo The PDO database connection instance.
 * @return array An array of tag names associated with the product.
 * @throws RuntimeException If a database error occurs.
 */
function getProductTagNames($productId, $pdo)
{
    try {
        // Prepare SQL query to retrieve tag names associated with the product
        $stmt = $pdo->prepare("SELECT t.tag_name FROM tags t INNER JOIN product_tag_mapping ptm ON t.tag_id = ptm.tag_id WHERE ptm.product_id = ?");

        // Execute the query with the provided product ID
        $stmt->execute([$productId]);

        // Fetch all tag names as a simple array
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Throw an exception if any database error occurs
        throw new RuntimeException("Failed to get product tags: " . $e->getMessage());
    }
}

/**
 * Updates the tags associated with a product.
 *
 * This function first removes all existing tag mappings for the given product ID from the 
 * `product_tag_mapping` table. If new tags are provided, it processes each tag:
 * - Cleans the input to remove whitespace, duplicates, and empty values.
 * - Checks if the tag already exists in the `tags` table.
 * - If the tag exists, retrieves its ID; otherwise, inserts it and retrieves the new ID.
 * - Inserts a mapping between the product and the tag in `product_tag_mapping`.
 * 
 * The function logs key operations to assist in debugging and ensures database consistency.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param int $product_id The unique identifier of the product.
 * @param array $tags An array of tag names to be associated with the product.
 * @throws RuntimeException If a database error occurs.
 */
function updateProductTags($pdo, $product_id, $tags)
{
    try {
        // Remove all existing tags associated with the product
        $stmt_delete = $pdo->prepare("DELETE FROM product_tag_mapping WHERE product_id = ?");
        $stmt_delete->execute([$product_id]);
        $deletedCount = $stmt_delete->rowCount();
        error_log("[updateProductTags] Deleted $deletedCount existing tags for product $product_id");

        // Proceed only if new tags are provided
        if (!empty($tags)) {
            // Trim whitespace, remove duplicates, and filter out empty values
            $cleaned_tags = array_map('trim', $tags);
            $unique_tags = array_unique($cleaned_tags);
            $non_empty_tags = array_filter($unique_tags, fn($tag) => !empty($tag));

            // If no valid tags remain, return early
            if (empty($non_empty_tags)) {
                error_log("[updateProductTags] All provided tags were invalid. No tags added for product $product_id");
                return;
            }

            error_log("[updateProductTags] Processing " . count($non_empty_tags) . " tags for product $product_id");

            // Prepare statements for checking, inserting tags, and mapping tags to the product
            $stmt_check_tag = $pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = ?");
            $stmt_insert_tag = $pdo->prepare("INSERT INTO tags (tag_name) VALUES (?)");
            $stmt_insert_mapping = $pdo->prepare("INSERT INTO product_tag_mapping (product_id, tag_id) VALUES (?, ?)");

            foreach ($non_empty_tags as $tag_name) {
                // Check if the tag already exists in the database
                $stmt_check_tag->execute([$tag_name]);
                $tag_id = $stmt_check_tag->fetchColumn();

                // If the tag does not exist, insert it and get its new ID
                if (!$tag_id) {
                    $stmt_insert_tag->execute([$tag_name]);
                    $tag_id = $pdo->lastInsertId();
                    error_log("[updateProductTags] Created new tag '$tag_name' with ID $tag_id");
                }

                // Insert the relationship between the product and the tag
                $stmt_insert_mapping->execute([$product_id, $tag_id]);
                error_log("[updateProductTags] Mapped product $product_id to tag $tag_id ('$tag_name')");
            }
        } else {
            error_log("[updateProductTags] No new tags provided. Tags cleared for product $product_id");
        }
    } catch (PDOException $e) {
        error_log("[updateProductTags] Error updating tags for product $product_id: " . $e->getMessage());
        throw new RuntimeException("Failed to update product tags: " . $e->getMessage());
    }
}
