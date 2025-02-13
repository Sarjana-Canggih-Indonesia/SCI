<?php
// tag_functions.php

/**
 * Creates a new tag in the database.
 *
 * This function inserts a new tag into the `tags` table. It sanitizes the input
 * to prevent SQL injection and XSS attacks.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param string $tagName The name of the tag to be created.
 * @return int|false Returns the ID of the newly created tag on success, or false on failure.
 */
function createTag(PDO $pdo, string $tagName)
{
    // Sanitize the input to prevent XSS and SQL injection
    $tagName = sanitize_input($tagName);

    try {
        // Prepare the SQL statement for inserting a new tag
        $stmt = $pdo->prepare("INSERT INTO tags (tag_name) VALUES (:tag_name)");
        $stmt->bindParam(':tag_name', $tagName, PDO::PARAM_STR);

        // Execute the statement and check if the insertion was successful
        if ($stmt->execute()) {
            return $pdo->lastInsertId(); // Return the ID of the newly created tag
        }
    } catch (PDOException $e) {
        // Log the error and handle it appropriately
        handleError("Error creating tag: " . $e->getMessage(), isLive() ? 'live' : 'local');
    }

    return false; // Return false if the insertion failed
}

/**
 * Retrieves a tag by its ID from the database.
 *
 * This function fetches a tag from the `tags` table based on the provided tag ID.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @param int $tagId The ID of the tag to retrieve.
 * @return array|null Returns an associative array containing the tag data, or null if the tag is not found.
 */
function getTagById(PDO $pdo, int $tagId)
{
    try {
        // Prepare the SQL statement for fetching a tag by ID
        $stmt = $pdo->prepare("SELECT * FROM tags WHERE tag_id = :tag_id");
        $stmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the tag as an associative array
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error and handle it appropriately
        handleError("Error fetching tag: " . $e->getMessage(), isLive() ? 'live' : 'local');
        return null; // Return null if an error occurred
    }

    return $result; // Return the result or null if the tag is not found
}

/**
 * Retrieves all tags from the database.
 *
 * This function fetches all tags from the `tags` table.
 *
 * @param PDO $pdo The PDO database connection instance.
 * @return array Returns an array of associative arrays containing all tags.
 */
function getAllTags(PDO $pdo)
{
    try {
        // Prepare the SQL statement for fetching all tags
        $stmt = $pdo->query("SELECT * FROM tags");

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