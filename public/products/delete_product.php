<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/product_functions.php';

// Initial variables for success or failure messages
$delete_status = null;
$product_id = $_POST['id'] ?? null; // Mengambil ID dari POST, karena penghapusan dilakukan lewat POST

// Validasi ID produk
if ($product_id) {
    // Ambil detail produk sebelum penghapusan untuk memverifikasi apakah produk ada
    $product = getProduct($productConn, $product_id);

    if ($product) {
        // Memeriksa apakah formulir disubmit untuk penghapusan
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
            try {
                // Hapus gambar produk (jika ada) sebelum menghapus produk dari database
                if (!empty($product['image']) && file_exists($product['image'])) {
                    unlink($product['image']); // Menghapus file gambar dari server
                }

                // Coba untuk menghapus produk dari database
                if (deleteProduct($productConn, $product_id)) {
                    // Jika penghapusan berhasil
                    $delete_status = 'success';
                    header('Location: manage_products.php?status=delete_success');
                    exit();
                } else {
                    // Jika penghapusan gagal
                    $delete_status = 'error';
                }
            } catch (Exception $e) {
                // Jika terjadi error saat penghapusan
                $delete_status = 'error';
            }
        }
    } else {
        // Produk tidak ditemukan
        $delete_status = 'error';
    }
}

// Menampilkan status penghapusan (opsional, bisa dihapus jika pengalihan sudah cukup)
if ($delete_status === 'success') {
    echo "Product deleted successfully.";
} elseif ($delete_status === 'error') {
    echo "There was an error deleting the product.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Deletion Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center">Product Deletion Status</h1>

        <!-- Display success or error message -->
        <div class="card">
            <div class="card-body text-center">
                <?php if ($delete_status === 'success'): ?>
                    <h5 class="card-title text-success">Product deleted successfully!</h5>
                    <p class="card-text">The product has been removed from the database.</p>
                    <a href="manage_products.php" class="btn btn-primary">Back to Product List</a>
                <?php elseif ($delete_status === 'error'): ?>
                    <h5 class="card-title text-danger">Error deleting the product.</h5>
                    <p class="card-text">There was an issue deleting the product. Please try again later or check the product ID.</p>
                    <a href="manage_products.php" class="btn btn-primary">Back to Product List</a>
                <?php else: ?>
                    <p class="card-text">No deletion occurred. Please go back and try again.</p>
                    <a href="manage_products.php" class="btn btn-secondary">Back to Product List</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>