<?php
// edit_product.php

// Step 1: Load necessary configurations and libraries
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/products/product_functions.php';
require_once __DIR__ . '/../../config/products/tag_functions.php';

use Carbon\Carbon;

// Step 2: Start session and generate CSRF token if it doesn't exist
startSession();

// Step 3: Load dynamic URL configuration
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];

// Step 4: Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Step 5: Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "login");
    exit();
}

// Step 6: Retrieve user information from the session and database.
$userInfo = getUserInfo($_SESSION['user_id']);

// Step 7: Handle cases where the user is not found in the database.
if (!$userInfo) {
    handleError("User not found in the database. Redirecting...", $_ENV['ENVIRONMENT']);
    exit();
}

// Check if the user is logged in and has admin privileges
if (!isset($userInfo['role']) || $userInfo['role'] !== 'admin') {
    handleError("Unauthorized access attempt", $_ENV['ENVIRONMENT']);
    header("Location: " . $baseUrl . "login");
    exit();
}

// Step 9: Set the user profile image. If not available, use a default image.
$profileImage = $userInfo['image_filename'] ?? 'default_profile_image.jpg';
$profileImageUrl = $baseUrl . "uploads/profile_images/" . $profileImage;

// Step 10: Handle the add product form submission ONLY if the request method is POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleAddProductForm();
}

// Step 11: Retrieve product categories and tags from the database.
$pdo = getPDOConnection();
$tags = getAllTags($pdo);
$categories = getProductCategories();
$products = getAllProductsWithCategoriesAndTags();

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
    <title>Edit Product - Sarjana Canggih Indonesia</title>
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
    <div class="scroll">
        <!-- Scroll to Top Button -->
        <a href="#" class="scroll-to-top" id="scrollToTopBtn">
            <i class="fa-solid fa-angles-up"></i>
        </a>
    </div>
    <!--========== AKHIR AREA SCROLL TO TOP ==========-->

    <!--========== AREA GENERIC FLASH MESSAGES ==========-->
    <div class="jarak-kustom container">
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

    <!--========== AREA EDIT PRODUCT ==========-->
    <div class="halaman-admin-edit-product jarak-kustom">
        <div class="container">
            <h2 class="mb-4">Halaman Edit Produk / Layanan</h2>

            <div class="row g-5">
                <!-- Kolom Kiri - Live Version -->
                <div class="col-md-6 separator live-version">
                    <h4 class="section-title">Live Preview</h4>
                    <div class="preview-card shadow-sm">
                        <img src="placeholder-image.jpg" class="img-fluid mb-3 rounded" alt="Product Image"
                            style="max-height: 200px;">
                        <h3 class="mb-2">Sample Product Name</h3>

                        <div class="d-flex gap-2 mb-3">
                            <span class="badge bg-primary">Electronics</span>
                            <span class="badge bg-success">Active</span>
                        </div>

                        <h4 class="text-danger mb-3">$199.99</h4>

                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="text-muted">This is a detailed description of the product that customers will see
                                on
                                the live version of the website.</p>
                        </div>

                        <div class="tags-section">
                            <h5>Tags</h5>
                            <div class="d-flex gap-2">
                                <span class="badge bg-secondary">gadget</span>
                                <span class="badge bg-secondary">electronics</span>
                                <span class="badge bg-secondary">new</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan - Edit Form -->
                <div class="col-md-6 edit-section">
                    <h4 class="section-title">Edit Product</h4>
                    <form action="edit_product.php?product_id=1" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Masukkan nama produk">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="price" name="price"
                                        placeholder="50000.00" step="5000">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <textarea class="form-control" id="short_description" name="short_description" rows="2"
                                placeholder="Max 150 characters...">This is a sample short description for the product preview</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                placeholder="Masukkan deskripsi produk"></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="1" selected>Electronics</option>
                                    <option value="2">Clothing</option>
                                    <option value="3">Books</option>
                                </select>
                            </div>


                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags"
                                    placeholder="Input tag Anda. Tekan spasi untuk melihat daftar tag, pisahkan dengan koma.">
                            </div>
                        </div>

                        <div class="mb-4 mt-4">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image">
                            <div class="form-text">Current image: product-image.jpg</div>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--========== AKHIR AREA EDIT PRODUCT ==========-->

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