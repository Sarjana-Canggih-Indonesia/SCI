<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/product_functions.php';

// Check if the user is logged in and has admin privileges
checkAdminSession($conn);

if (isset($_GET['product_id'])) {
    $productId = $_GET['product_id'];
    $product = getProduct($productConn, $productId);

    if (!$product) {
        die("Product not found.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handling form submission to edit the product
    $productName = $_POST['name'];
    $shortDescription = $_POST['short_description'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $categoryId = $_POST['category_id'];
    $status = $_POST['status'];
    $tags = $_POST['tags'];

    // Handle image upload
    $imagePath = $product['image']; // Default to the current image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadProductImage($_FILES['image']);
        if (!$imagePath) {
            $imageError = "Failed to upload image.";
        }
    }

    if (empty($imageError)) {
        editProduct($productConn, $productId, $productName, $shortDescription, $description, $price, $imagePath, $categoryId, $status, $tags);
        header("Location: success_page.php");
        exit();
    }
}

$categories = getAllCategories($productConn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Edit Product</h2>

        <?php if (isset($imageError)) : ?>
            <div class="alert alert-danger">
                <?= $imageError ?>
            </div>
        <?php endif; ?>

        <form action="edit_product.php?product_id=<?= $productId ?>" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="short_description" class="form-label">Short Description</label>
                <textarea class="form-control" id="short_description" name="short_description" required><?= htmlspecialchars($product['short_description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" class="form-control" id="price" name="price" value="<?= htmlspecialchars($product['price']) ?>" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?= $category['id'] ?>" <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active" <?= $product['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $product['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="tags" class="form-label">Tags</label>
                <input type="text" class="form-control" id="tags" name="tags" value="<?= htmlspecialchars($product['tags']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Product Image</label>
                <input type="file" class="form-control" id="image" name="image">
                <small class="form-text text-muted">Leave empty if you don't want to change the image.</small>
            </div>
            <button type="button" class="btn btn-secondary mb-3" onclick="history.back()">Back</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>