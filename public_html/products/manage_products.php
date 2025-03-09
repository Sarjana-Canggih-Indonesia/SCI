<?php
// manage_products.php

// Step 1: Load necessary configurations and libraries
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/products/product_functions.php';
require_once __DIR__ . '/../../config/products/tag_functions.php';
require_once __DIR__ . '/../../config/auth/admin_functions.php';

// Step 2: Start session and generate CSRF token if it doesn't exist
startSession();

// Step 3: Load dynamic URL configuration
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];

// Step 4: Validate if the current user has the admin role
validateAdminRole();

// Step 9: Set the user profile image. If not available, use a default image.
$profileImage = $userInfo['image_filename'] ?? 'default_profile_image.jpg';
$profileImageUrl = $baseUrl . "uploads/profile_images/" . $profileImage;

// Step 10: Handle the add product form submission ONLY if the request method is POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleAddProductForm($config, $env);
}

// Step 11: Retrieve product categories and tags from the database.
$pdo = getPDOConnection($config, $env);
$activityLogs = getAdminActivityLog($pdo);
$tags = getAllTags($pdo);
$categories = getProductCategories($config, $env);
$products = getAllProductsWithCategoriesAndTags($config, $env);

// Step 12: Handle success/error messages and update cache headers
$flash = processFlashMessagesAndHeaders($isLive);
$successMessage = $flash['success'];
$errorMessage = $flash['error'];

// Step 13: Set no-cache headers in the local environment.
setCacheHeaders($isLive);
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
    <?php include __DIR__ . '/../includes/admin-navbar.php'; ?>
    <!--========== AKHIR INSERT HEADER.PHP ==========-->

    <!--========== AREA SCROLL TO TOP ==========-->
    <div class="scroll">
        <!-- Scroll to Top Button -->
        <a href="#" class="scroll-to-top" id="scrollToTopBtn">
            <i class="fa-solid fa-angles-up"></i>
        </a>
    </div>
    <!--========== AKHIR AREA SCROLL TO TOP ==========-->

    <!--========== AREA MANAGE PRODUCTS ==========-->
    <div class="area-konten-manage-products">
        <div class="container">
            <section class="judul-halaman-admin-dashboard">
                <h2 class="fs-1 mb-5 text-center">Manage Products</h2>
            </section>
            <!-- User Info Section & Admin Navigation -->
            <div class="row mb-4">
                <!-- User Info -->
                <div class="col-md-6 user-info-halaman-admin">
                    <div class="card shadow-sm border-0 overflow-hidden">
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
                                        alt="Profile Image" class="profile-img shadow-sm rounded-circle"
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

                <!-- Navigasi Halaman Admin -->
                <div class="col-md-6 navigasi-halaman-admin">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0 text-primary fw-semibold">
                                <i class="fa-solid fa-compass me-2"></i>Quick Navigation
                            </h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-6 mb-2">
                                    <a href="<?php echo $baseUrl; ?>admin-dashboard"
                                        onclick="if(document.referrer) { if(confirm('Kembali ke halaman Admin Dashboard?')) { history.back(); } return false; }"
                                        class="btn btn-light w-100 h-100 p-3 text-start border-hover">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-arrow-left fs-4 text-secondary me-2"></i>
                                            <span class="fw-medium">Back</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 mb-2">
                                    <a href="<?php echo $baseUrl; ?>manage_users"
                                        class="btn btn-light w-100 h-100 p-3 text-start border-hover">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-users fs-4 text-primary me-2"></i>
                                            <span class="fw-medium">Users</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 mb-2">
                                    <a href="<?php echo $baseUrl; ?>manage_promos"
                                        class="btn btn-light w-100 h-100 p-3 text-start border-hover">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-percent fs-4 text-danger me-2"></i>
                                            <span class="fw-medium">Promos</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 mb-2">
                                    <a href="#" class="btn btn-light w-100 h-100 p-3 text-start border-hover">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-pen-nib fs-4 text-warning me-2"></i>
                                            <span class="fw-medium">Blogs</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
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

            <!--========== AREA GENERIC FLASH MESSAGES ==========-->
            <div class="area-generic-flash-messages mb-4">
                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($successMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($errorMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>
            <!--========== AKHIR AREA GENERIC FLASH MESSAGES ==========-->

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
                            <th>Status</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <?php
                        $counter = 1;
                        foreach ($products as $product):
                            $encodedId = $optimus->encode($product['product_id']);
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_products[]" value="<?= $product['product_id'] ?>"
                                        class="product-checkbox">
                                    <?= $counter++ ?>
                                </td>
                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                <td><?= htmlspecialchars($product['categories'] ?? 'Uncategorized') ?></td>

                                <!-- Kolom Status Penjualan -->
                                <td>
                                    <?php
                                    $status = strtolower($product['active']);
                                    $badgeClass = ($status === 'active') ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>

                                <td>Rp <?= number_format($product['price_amount'], 0, ',', '.') ?>,00</td>
                                <td>
                                    <!-- Tombol View Details -->
                                    <button class="btn btn-info btn-sm"
                                        onclick="viewDetails(<?= $product['product_id'] ?>)">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                    <!-- Tombol Edit -->
                                    <button class="btn btn-warning btn-sm"
                                        onclick="editProduct('<?= $product['slug'] ?>', <?= $encodedId ?>)">
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
                <button class="mx-1 btn btn-danger d-none" id="deleteSelectedBtn" data-bs-toggle="modal"
                    data-bs-target="#deleteSelectedModal"><i class="fas fa-trash"></i> Delete Selected
                </button>
                <button class="mx-1 btn btn-success"><i class="fas fa-download"></i> Export Data</button>
            </div>

            <!-- Pagination untuk Tabel Produk -->
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center"></ul>
            </nav>

            <!-- Price History and Recent Activity Section (in 1 row) -->
            <div class="row my-4">
                <!-- Recent Activity -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Recent Activity</h5>
                        </div>
                        <!-- Recent Activity Section -->
                        <div class="card-body p-2">
                            <div class="list-group compact-recent-activity">
                                <?php if (empty($activityLogs)): ?>
                                    <div class="list-group-item text-center py-2 small text-muted">No recent activities
                                        found</div>
                                <?php else: ?>
                                    <?php foreach ($activityLogs as $log): ?>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <div class="row align-items-center g-1">
                                                <!-- Kolom Waktu -->
                                                <div class="col-1 text-nowrap">
                                                    <small class="text-muted">
                                                        <?= date('H:i', strtotime($log['created_at'])) ?>
                                                    </small>
                                                </div>

                                                <!-- Kolom Konten -->
                                                <div class="col-11">
                                                    <div class="d-flex align-items-center">
                                                        <!-- User dan Action -->
                                                        <div class="flex-grow-1">
                                                            <span class="badge bg-secondary bg-opacity-25 text-dark me-2">
                                                                <?= htmlspecialchars($log['username']) ?>
                                                            </span>
                                                            <span class="text-capitalize text-primary">
                                                                <?= htmlspecialchars($log['action']) ?>
                                                            </span>

                                                            <?php if (!empty($log['details'])): ?>
                                                                <div class="text-muted mt-1 small">
                                                                    <?= htmlspecialchars($log['details']) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                            <form id="addProductForm" action="<?php echo $baseUrl; ?>manage_products" method="POST"
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
                                        placeholder="Input tag Anda. Tekan spasi untuk melihat daftar tag, pisahkan dengan koma.">
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
                                    <label for="productImages" class="form-label">Product Images (Max 10 images)</label>
                                    <input type="file" class="form-control" id="productImages" name="productImages[]"
                                        accept="image/*" multiple>
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
            <div class="modal-halaman-admin-bagian-details modal fade" id="productDetailsModal" tabindex="-1"
                aria-labelledby="productDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <!-- Carousel Container -->
                                    <div id="productImageCarousel" class="carousel slide" data-bs-ride="carousel">
                                        <div class="carousel-inner" id="detailProductImagesContainer"
                                            style="max-height: 300px; overflow: hidden;">
                                            <!-- Dynamic images will be inserted here -->
                                        </div>

                                        <!-- Carousel Controls -->
                                        <button class="carousel-control-prev" type="button"
                                            data-bs-target="#productImageCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button"
                                            data-bs-target="#productImageCarousel" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    </div>
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
    </div>
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