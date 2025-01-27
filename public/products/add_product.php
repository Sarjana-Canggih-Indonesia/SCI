<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/product_functions.php';

$error_message = '';
$success_message = '';

// Cek apakah form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $short_description = $_POST['short_description'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $status = $_POST['status'];
    $tags = $_POST['tags'];

    // Proses gambar
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_name = $_FILES['image']['name'];
        $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
        $image_new_name = uniqid() . '.' . $image_ext; // Ganti nama gambar dengan ID unik

        $image_target = __DIR__ . '/uploads/' . $image_new_name;

        if (move_uploaded_file($image_tmp, $image_target)) {
            $image_path = $image_new_name; // Simpan nama gambar di database
        } else {
            $error_message = "Gagal mengupload gambar.";
        }
    }

    // Validasi data (contoh sederhana, sesuaikan dengan kebutuhan)
    if (empty($name) || empty($short_description) || empty($description) || empty($price) || empty($category)) {
        $error_message = "Semua kolom wajib diisi!";
    }

    if (empty($error_message)) {
        // Masukkan data ke database
        try {
            $stmt = $productConn->prepare("INSERT INTO products (name, short_description, description, price, category_id, status, image, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $short_description, $description, $price, $category, $status, $image_path, $tags]);
            $success_message = "Produk berhasil ditambahkan!";
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan saat menambahkan produk: " . $e->getMessage();
        }
    }
}

// Ambil semua produk dan kategori untuk ditampilkan dalam form
$products = getAllProducts($productConn);
$categories = getAllCategories($productConn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico" />
</head>

<body>
    <div class="container my-5">
        <h1 class="mb-4">Add New Product</h1>

        <!-- Form -->
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="short_description" class="form-label">Short Description</label>
                <textarea class="form-control" id="short_description" name="short_description" rows="2" required></textarea>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Full Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category" required>
                    <?php
                    foreach ($categories as $category) {
                        echo "<option value=\"" . $category['id'] . "\">" . htmlspecialchars($category['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Upload Image</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
            </div>
            <div class="mb-3">
                <label for="tags" class="form-label">Tags (comma-separated)</label>
                <input type="text" class="form-control" id="tags" name="tags">
            </div>
            <button type="submit" class="btn btn-primary">Add Product</button>
            <a href="manage_products.php" class="btn btn-secondary ms-3">Cancel</a>
            <button type="button" class="btn btn-light ms-3" onclick="history.back()">Back</button>
        </form>

        <!-- Modal untuk Sukses -->
        <div class="modal fade" id="addProductSuccessModal" tabindex="-1" aria-labelledby="addProductSuccessModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProductSuccessModalLabel">Success</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                    <div class="modal-footer">
                        <a href="manage_products.php" class="btn btn-primary">Go to Manage Products</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal untuk Gagal -->
        <div class="modal fade" id="addProductErrorModal" tabindex="-1" aria-labelledby="addProductErrorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProductErrorModalLabel">Error</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Tampilkan modal berdasarkan pesan sukses atau error
        <?php if (!empty($success_message)): ?>
            var successModal = new bootstrap.Modal(document.getElementById('addProductSuccessModal'));
            successModal.show();
        <?php elseif (!empty($error_message)): ?>
            var errorModal = new bootstrap.Modal(document.getElementById('addProductErrorModal'));
            errorModal.show();
        <?php endif; ?>
    </script>
</body>

</html>