<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/product_functions.php';

// Ambil semua produk dari database
$products = getAllProducts($productConn);

// Cek apakah status penghapusan berhasil
$delete_status = $_GET['status'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico" />
</head>

<body>
    <div class="container my-5">
        <h1 class="mb-4">Manage Products</h1>

        <!-- Tambah Layanan Button -->
        <a href="add_product.php" class="btn btn-primary mb-3">Add New Product</a>

        <!-- Tabel Layanan -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Short Description</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                alt="product Image"
                                class="img-thumbnail"
                                style="max-width: 100px; height: auto;">
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['short_description']); ?></td>
                        <td>
                            <?php echo $product['status'] == 'available' ? '<span class="badge bg-success">Available</span>' : '<span class="badge bg-danger">Unavailable</span>'; ?>
                        </td>
                        <td><?php echo $product['price'] ? '$' . number_format($product['price'], 2) : 'Free'; ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $product['id']; ?>">Delete</button>
                        </td>
                    </tr>

                    <!-- Modal untuk konfirmasi penghapusan produk -->
                    <div class="modal fade" id="deleteModal<?php echo $product['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $product['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $product['id']; ?>">Confirm Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to delete the product "<?php echo htmlspecialchars($product['name']); ?>"?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <!-- Form untuk menghapus produk menggunakan metode POST -->
                                    <form action="delete_product.php" method="POST">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Pemberitahuan Penghapusan Berhasil -->
    <?php if ($delete_status === 'delete_success'): ?>
        <div class="modal fade" id="deleteproduct-SuccessModal" tabindex="-1" aria-labelledby="deleteproduct-SuccessModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteproduct-SuccessModalLabel">Product Deleted Successfully</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        The product has been successfully deleted.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            // Show the modal automatically when the page loads
            var myModal = new bootstrap.Modal(document.getElementById('deleteproduct-SuccessModal'));
            myModal.show();
        </script>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>