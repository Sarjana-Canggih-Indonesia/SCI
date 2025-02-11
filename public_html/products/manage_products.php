<?php
// manage_products.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/products/product_functions.php';

use Carbon\Carbon;

// Pastikan sesi dimulai
startSession();

// Cek apakah sesi berjalan dengan benar
if (!isset($_SESSION['user_id'])) {
    handleError("Session user_id tidak ditemukan. Redirecting...", $_ENV['ENVIRONMENT']);
}

// Ambil info user
$userInfo = getUserInfo($_SESSION['user_id']);

// Jika user tidak ditemukan
if (!$userInfo) {
    handleError("User tidak ditemukan di database. Redirecting...", $_ENV['ENVIRONMENT']);
}

// Block akses untuk non-admin
if ($userInfo['role'] !== 'admin') {
    handleError("Akses ditolak! Role: " . $userInfo['role'], $_ENV['ENVIRONMENT']);
}

// Memuat konfigurasi URL Dinamis
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];

setCacheHeaders($isLive); // Set header no cache saat local environment

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manages Products - Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>favicon.ico" />
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Slick Slider css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/slick.min.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/slick-theme.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css" />
</head>

<body style="background-color: #f7f9fb;">

    <!--========== INSERT HEADER.PHP ==========-->
    <?php include '../includes/header.php'; ?>
    <!--========== AKHIR INSERT HEADER.PHP ==========-->

    <!--========== AREA SCROLL TO TOP ==========-->
    <section class="scroll">
        <!-- Scroll to Top Button -->
        <a href="#" class="scroll-to-top" id="scrollToTopBtn">
            <i class="fa-solid fa-angles-up"></i>
        </a>
    </section>
    <!--========== AKHIR AREA SCROLL TO TOP ==========-->

    <div class="container jarak-kustom">
        <h2 class="mb-4 text-center">Manage Products</h2>

        <!-- Search Bar -->
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <input type="text" class="form-control w-75" placeholder="Search products..." id="searchInput">
            <button class="btn btn-primary ms-3"><i class="fas fa-search"></i> Search</button>
        </div>

        <!-- Product Summary and User Info Section -->
        <div class="row mb-4">
            <!-- User Info -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">User Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><strong>Username:</strong> <?php echo htmlspecialchars($userInfo['username']); ?></li>
                            <li><strong>Email:</strong> <?php echo htmlspecialchars($userInfo['email']); ?></li>
                            <li><strong>Role:</strong> <?php echo htmlspecialchars($userInfo['role']); ?></li>
                            <li><strong>Profile Image:</strong></li>
                            <li>
                                <img src="<?php echo htmlspecialchars($userInfo['profile_image_filename']); ?>"
                                    alt="Profile Image" class="img-thumbnail" style="width: 100px; height: 100px;">
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Product Summary -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Product Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><strong>Total Products:</strong> 150</li>
                            <li><strong>Total Categories:</strong> 5</li>
                            <li><strong>Total Revenue:</strong> Rp 150,000,000</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol untuk membuka modal Add Product -->
        <div class="button-add-product">
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>

        <!-- Products Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Tags</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Add dynamic rows for your products -->
                    <tr>
                        <td>1</td>
                        <td>Product A</td>
                        <td>Electronics</td>
                        <td>Gadget, Tech</td>
                        <td>Rp 150,000</td>
                        <td>100</td>
                        <td>
                            <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Product B</td>
                        <td>Clothing</td>
                        <td>Fashion, Casual</td>
                        <td>Rp 250,000</td>
                        <td>50</td>
                        <td>
                            <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Bulk Actions Section -->
        <div class="mb-4 d-flex">
            <button class="me-1 btn btn-secondary"><i class="fas fa-check-circle"></i> Select All</button>
            <button class="mx-1 btn btn-danger"><i class="fas fa-trash"></i> Delete Selected</button>
            <button class="mx-1 btn btn-success"><i class="fas fa-download"></i> Export Data</button>
            <button class="mx-1 btn btn-primary btn-sm"><i class="fas fa-eye"></i> View Details</button>
            <button class="mx-1 btn btn-warning btn-sm"><i class="fas fa-pencil-alt"></i> Quick Edit</button>
        </div>

        <!-- Filter by Category Section -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary-subtle">
                <h5 class="mb-0">Filter by Category</h5>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <!-- Filter by Category -->
                <select class="form-select w-45" aria-label="Filter by Category">
                    <option selected>Filter by Category</option>
                    <option value="electronics">Electronics</option>
                    <option value="clothing">Clothing</option>
                </select>
            </div>
        </div>

        <!-- Pagination with Product Count -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>

        <!-- Price History and Recent Activity Section (in 1 row) -->
        <div class="row my-4">
            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action">
                                2025-02-01 14:30:00 - User "JohnDoe" added Product A
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                2025-02-01 15:00:00 - User "JohnDoe" deleted Product B
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price History -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Price History / Price Updates</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><strong>Product A:</strong> Rp 180,000 -> Rp 150,000 (2025-02-01)</li>
                            <li><strong>Product B:</strong> Rp 300,000 -> Rp 250,000 (2025-01-30)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Reviews Section -->
        <div class="row my-4">
            <!-- Product Reviews -->
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Product Reviews</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action">
                                Product A - 4.5 stars - "Great product!"
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                Product B - 3 stars - "Good value for money."
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Add Product Modal -->
        <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form untuk menambahkan produk baru -->
                        <form id="addProductForm">
                            <div class="mb-3">
                                <label for="productName" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="productName" name="productName" required>
                            </div>
                            <div class="mb-3">
                                <label for="productCategory" class="form-label">Category</label>
                                <select class="form-select" id="productCategory" name="productCategory" required>
                                    <option value="" selected disabled>Select Category</option>
                                    <option value="electronics">Electronics</option>
                                    <option value="clothing">Clothing</option>
                                    <option value="books">Books</option>
                                    <option value="home">Home & Kitchen</option>
                                    <option value="sports">Sports & Outdoors</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="productTags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="productTags" name="productTags"
                                    placeholder="Enter tags separated by commas (e.g., Gadget, Tech)">
                            </div>
                            <div class="mb-3">
                                <label for="productPrice" class="form-label">Price</label>
                                <input type="number" class="form-control" id="productPrice" name="productPrice"
                                    step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="productStock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="productStock" name="productStock"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="productDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="productDescription" name="productDescription"
                                    rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="productImage" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="productImage" name="productImage"
                                    accept="image/*">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveProductBtn">Save Product</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Confirmation Modals -->
        <div class="modal" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this product?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--================ AREA FOOTER =================-->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <!--================ AKHIR AREA FOOTER =================-->

    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/slick.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fuse.js@7.1.0"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/manage_products.js"></script>
</body>

</html>