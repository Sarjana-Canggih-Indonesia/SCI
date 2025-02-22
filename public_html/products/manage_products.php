<?php
// manage_products.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/products/product_functions.php';
require_once __DIR__ . '/../../config/products/tag_functions.php';

use Carbon\Carbon;

startSession();

// Step 1: Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    $baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
    header("Location: " . $baseUrl . "login");
    exit();
}

// Step 2: Retrieve user information from the session and database.
$userInfo = getUserInfo($_SESSION['user_id']);
$profileImage = null;

// Step 3: Handle cases where the user is not found in the database.
if (!$userInfo) {
    handleError("User not found in the database. Redirecting...", $_ENV['ENVIRONMENT']);
    exit();
}

// Step 4: Set the user profile image. If not available, use a default image.
$profileImage = $userInfo['image_filename'] ?? 'default_profile_image.jpg';
$profileImageUrl = $baseUrl . "uploads/profile_images/" . $profileImage;

// Restrict access to non-admin users.
if ($userInfo['role'] !== 'admin') {
    handleError("Access denied! Role: " . $userInfo['role'], $_ENV['ENVIRONMENT']);
    header("Location: " . $baseUrl . "login");
    exit();
}

// Retrieve product categories from the database.
$categories = getProductCategories();

// Retrieve all products along with categories and tags.
$products = getAllProductsWithCategoriesAndTags();

// Handle the add product form submission.
handleAddProductForm();

// Load dynamic URL configuration.
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];
$pdo = getPDOConnection();
$tags = getAllTags($pdo);

// Set no-cache headers in the local environment.
setCacheHeaders($isLive);

// Set security headers.
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Sarjana Canggih Indonesia</title>
    <!-- Meta Tag CSRF Token -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
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
    <!-- Tagify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/halaman-admin.css" />
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

    <!--========== AREA MANAGE PRODUCTS ==========-->
    <section class="jarak-kustom">
        <div class="container">
            <h2 class="mb-4 text-center">Manage Products</h2>

            <!-- Product Summary and User Info Section -->
            <div class="row mb-4">
                <!-- User Info -->
                <div class="col-md-6 user-info-halaman-admin">
                    <div class="card shadow-lg border-0 overflow-hidden">
                        <div class="card-header bg-primary bg-gradient text-white py-3 position-relative">
                            <h5 class="mb-0 fw-semibold">
                                <i class="fa-solid fa-user-shield me-2"></i>Admin Profile
                            </h5>
                            <div class="header-accent"></div>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                                <!-- Profile Image -->
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($profileImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="Profile Image" class="profile-img shadow-lg rounded-circle"
                                        data-bs-toggle="tooltip" title="Admin Profile Picture">
                                </div>

                                <!-- User Details -->
                                <div class="flex-grow-1 w-100">
                                    <div class="d-flex flex-column gap-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-user-tag fs-5 text-primary me-3"></i>
                                            <div>
                                                <div class="text-muted small">USERNAME</div>
                                                <div class="h5 mb-0 fw-semibold">
                                                    <?php echo htmlspecialchars($userInfo['username']); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-envelope fs-5 text-primary me-3"></i>
                                            <div>
                                                <div class="text-muted small">EMAIL</div>
                                                <div class="h5 mb-0 fw-semibold">
                                                    <?php echo htmlspecialchars($userInfo['email']); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-user-gear fs-5 text-primary me-3"></i>
                                            <div>
                                                <div class="text-muted small">ROLE</div>
                                                <div class="h5 mb-0 fw-semibold">
                                                    <span
                                                        class="badge bg-primary bg-gradient"><?php echo htmlspecialchars($userInfo['role']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                <li><strong>Total Products:</strong>
                                    <?php echo count($products); ?>
                                </li>
                                <li><strong>Total Categories:</strong>
                                    <?php echo count($categories); ?>
                                </li>
                                <li><strong>Total Revenue:</strong> Rp 150,000,000</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="mb-4 d-flex">
                <input type="text" class="form-control flex-grow-1" id="searchInput"
                    placeholder="Cari produk berdasarkan nama">
                <button class="btn btn-primary ms-3 d-inline-flex align-items-center">
                    <i class="fas fa-search me-2"></i>
                    Search
                </button>
            </div>

            <!-- Filter by Category Section -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary-subtle">
                    <h5 class="mb-0">Filter by Category</h5>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <select class="form-select w-45" id="categoryFilter" aria-label="Filter by Category">
                        <option value="" selected>All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category['category_id']) ?>">
                                <?= htmlspecialchars($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Tombol untuk membuka modal Add Product -->
            <div class="button-add-product">
                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>

            <!-- Products Table -->
            <div class="halaman-manage-products-bagian-table table-responsive mb-4">
                <table class="table table-bordered table-sm table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <?php
                        $counter = 1;
                        foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_products[]" value="<?= $product['product_id'] ?>"
                                        class="product-checkbox">
                                    <?= $counter++ ?>
                                </td>
                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                <td><?= htmlspecialchars($product['categories'] ?? 'Uncategorized') ?></td>
                                <td>Rp <?= number_format($product['price_amount'], 0, ',', '.') ?>,00</td>
                                <td>
                                    <!-- Tombol View Details -->
                                    <button class="btn btn-info btn-sm"
                                        onclick="viewDetails(<?= $product['product_id'] ?>)">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                    <!-- Tombol Edit -->
                                    <button class="btn btn-warning btn-sm"
                                        onclick="editProduct(<?= $product['product_id'] ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bulk Actions Section -->
            <div class="mb-4 d-flex">
                <button class="me-1 btn btn-secondary" id="manage_products-selectAllButton"><i
                        class="fas fa-check-circle"></i> Select All</button>
                <button class="mx-1 btn btn-warning btn-sm"><i class="fas fa-pencil-alt"></i> Quick Edit</button>
                <button class="mx-1 btn btn-danger d-none" id="deleteSelectedBtn" data-bs-toggle="modal"
                    data-bs-target="#deleteSelectedModal"><i class="fas fa-trash"></i> Delete Selected
                </button>
                <button class="mx-1 btn btn-success"><i class="fas fa-download"></i> Export Data</button>
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
                            <form id="addProductForm" action="manage_products.php" method="POST"
                                enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="productName" class="form-label">Product Name</label>
                                    <input type="text" class="form-control" id="productName" name="productName"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label for="productCategory" class="form-label">Category</label>
                                    <select class="form-select" id="productCategory" name="productCategory" required>
                                        <option value="" selected disabled>Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="productTags" class="form-label">Tags</label>
                                    <input type="text" class="form-control" id="productTags" name="productTags"
                                        placeholder="Input tag Anda di sini. Tekan spasi untuk melihat daftar tag yang tersedia, pisahkan dengan koma.">
                                    <!-- Datalist untuk autocomplete tags -->
                                    <datalist id="tagList">
                                        <?php foreach ($tags as $tag): ?>
                                            <option value="<?php echo htmlspecialchars($tag['tag_name']); ?>">
                                            <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Harga</label>
                                    <div class="row g-2 align-items-end">
                                        <!-- Currency Display -->
                                        <div class="col-3">
                                            <label class="form-label small text-muted">Mata Uang</label>
                                            <select class="form-select" disabled>
                                                <option selected>IDR</option>
                                            </select>
                                            <input type="hidden" name="productCurrency" value="IDR">
                                        </div>

                                        <!-- Price Input -->
                                        <div class="col-9">
                                            <label for="productPriceAmount"
                                                class="form-label small text-muted">Jumlah</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="productPriceAmount"
                                                    name="productPriceAmount" step="5000" min="0" placeholder="50000"
                                                    required>
                                                <span class="input-group-text">,00</span>
                                            </div>
                                        </div>
                                    </div>
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
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary" id="saveProductBtn">Save
                                        Product</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Selected Modal -->
            <div class="modal fade" id="deleteSelectedModal" tabindex="-1" aria-labelledby="deleteSelectedModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteSelectedModalLabel">Konfirmasi Penghapusan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Apakah Anda yakin ingin menghapus produk yang dipilih?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteSelected">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Details Modal -->
            <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <img id="detailProductImage" src="" class="img-fluid rounded mb-3"
                                        alt="Product Image" style="max-height: 300px; object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <h3 id="detailProductName" class="mb-3"></h3>
                                    <div class="mb-3">
                                        <strong>Description:</strong>
                                        <p id="detailProductDescription" class="text-muted"></p>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Price:</strong>
                                            <div id="detailProductPrice" class="text-success fs-5"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Currency:</strong>
                                            <div id="detailProductCurrency" class="text-muted"></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Categories:</strong>
                                            <div id="detailProductCategories" class="text-primary"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Tags:</strong>
                                            <div id="detailProductTags" class="text-info"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Created At: <span
                                                    id="detailProductCreatedAt"></span></small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Last Updated: <span
                                                    id="detailProductUpdatedAt"></span></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--========== AKHIR AREA MANAGE PRODUCTS ==========-->

    <!--================ AREA FOOTER =================-->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <!--================ AKHIR AREA FOOTER =================-->

    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/fusejs.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
    <script type="text/javascript"
        src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
    <script> const BASE_URL = '<?= $baseUrl ?>';</script>
    <!-- Script terkait dengan tagify -->
    <script>
        const TAGS_WHITELIST = [
            <?php foreach ($tags as $tag): ?> "<?php echo htmlspecialchars($tag['tag_name']); ?>",
            <?php endforeach; ?>
        ];
    </script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/manage_products.js"></script>
</body>

</html>