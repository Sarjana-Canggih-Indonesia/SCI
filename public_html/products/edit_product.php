<?php
// edit_product.php

// Step 1: Load necessary configurations and libraries
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';
require_once __DIR__ . '/../../config/products/product_functions.php';
require_once __DIR__ . '/../../config/products/tag_functions.php';

// Step 2: Start session and generate CSRF token if it doesn't exist
startSession();

// Ambil parameter dari URL
$slug = $_GET['slug'] ?? null;
$encodedId = $_GET['optimus'] ?? null;

// Validasi parameter
if (!$slug || !$encodedId) {
    http_response_code(404);
    include_once __DIR__ . '/../../404.php';
    exit();
}

// Dekode ID menggunakan Optimus
$productId = $optimus->decode($encodedId);

// Dapatkan data produk dari database
$product = getProductBySlugAndOptimus($slug, $encodedId, $config, $env);

// Jika produk tidak ditemukan
if (!$product) {
    http_response_code(404);
    include_once __DIR__ . '/../../404.php';
    exit();
}

// Ambil data yang diperlukan dari $product
$currentImage = $product['image_path'] ?? 'default_product.jpg';

// Step 3: Load dynamic URL configuration
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];

// Step 4: Validates if the current user has the admin role and enforces access restrictions.
validateAdminRole();

// Step 5: Set the user profile image. If not available, use a default image.
$profileImage = $userInfo['image_filename'] ?? 'default_profile_image.jpg';
$profileImageUrl = $baseUrl . "uploads/profile_images/" . $profileImage;

// Step 6: Handle the add product form submission ONLY if the request method is POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleEditProductForm($config, $env);
}

// Step 8: Retrieve product categories and tags from the database.
$pdo = getPDOConnection($config, $env);
$tags = getAllTags($pdo);
$categories = getProductCategories($config, $env);
$products = getAllProductsWithCategoriesAndTags($config, $env);
$categoryRelations = getProductCategoryRelations($product['product_id'], $config, $env);
$currentCategoryIds = $categoryRelations['category_ids'] ?? [];

// Step 9: Handle success/error messages and update cache headers
$flash = processFlashMessagesAndHeaders($isLive);
$successMessage = $flash['success'];
$errorMessage = $flash['error'];

// Step 10: Set no-cache headers in the local environment.
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
                <!-- KOLOM KIRI - LIVE VERSION -->
                <div class="col-md-6 separator live-version">
                    <h4 class="section-title">Live Preview</h4>
                    <div class="preview-card shadow-sm">
                        <!-- Gambar Produk -->
                        <img src="<?= $baseUrl . ($product['image_path'] ?? 'assets/images/default_product.jpg') ?>"
                            class="img-fluid mb-3 rounded" alt="Product Image"
                            style="max-height: 200px; object-fit: cover;">
                        <!-- Nama Produk -->
                        <h3 class="mb-2"><?= htmlspecialchars($product['product_name']) ?></h3>

                        <!-- Kategori dan Status -->
                        <div class="d-flex gap-2 mb-3">
                            <!-- Kategori -->
                            <?php
                            // Loop melalui semua kategori yang tersedia
                            foreach ($categories as $category) {
                                // Periksa apakah kategori saat ini terkait dengan produk
                                if (in_array($category['category_id'], $currentCategoryIds)) {
                                    echo '<span class="badge bg-primary">' . htmlspecialchars($category['category_name']) . '</span>';
                                }
                            }

                            // Jika tidak ada kategori yang terkait, tampilkan "Uncategorized"
                            if (empty($currentCategoryIds)) {
                                echo '<span class="badge bg-secondary">Uncategorized</span>';
                            }
                            ?>
                            <!-- Status Penjualan -->
                            <?= getProductStatus($product['active']) ?>
                        </div>

                        <!-- Harga -->
                        <h4 class="text-danger mb-3">
                            Rp <?= number_format($product['price_amount'], 0, ',', '.') ?>
                        </h4>

                        <!-- Deskripsi -->
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="text-muted">
                                <?= htmlspecialchars($product['description'] ?? 'No description available') ?>
                            </p>
                        </div>

                        <!-- Tags -->
                        <div class="tags-section">
                            <h5>Tags</h5>
                            <div class="d-flex gap-2">
                                <?php
                                $tags = explode(',', $product['tags'] ?? '');
                                foreach ($tags as $tag):
                                    if (trim($tag)):
                                        ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars(trim($tag)) ?></span>
                                        <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN - EDIT FORM -->
                <div class="col-md-6 edit-section">
                    <h4 class="section-title">Edit Product</h4>
                    <form action="<?= $baseUrl ?>edit-product/<?= $slug ?>/<?= $encodedId ?>" method="post"
                        enctype="multipart/form-data">
                        <!-- Nama Produk -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Masukkan nama produk"
                                value="<?= htmlspecialchars($product['product_name'] ?? '') ?>">
                        </div>
                        <!-- Harga -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="price" name="price"
                                        placeholder="50000.00" step="5000"
                                        value="<?= htmlspecialchars($product['price_amount'] ?? '') ?>">
                                </div>
                            </div>
                            <!-- Status Penjualan -->
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= ($product['deleted_at'] === null) ? 'selected' : '' ?>>
                                        Active</option>
                                    <option value="inactive" <?= ($product['deleted_at'] !== null) ? 'selected' : '' ?>>
                                        Inactive</option>
                                </select>
                            </div>
                        </div>
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                placeholder="Masukkan deskripsi produk"><?= htmlspecialchars($product['description'] ?? '') ?>
                            </textarea>
                        </div>
                        <!-- Category -->
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id[]" multiple>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['category_id'] ?>"
                                            <?= in_array($category['category_id'], $currentCategoryIds) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Tags -->
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags"
                                    placeholder="Input tag Anda..."
                                    value="<?= htmlspecialchars($product['tags'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Image Produk / Layanan -->
                        <div class="mb-4 mt-4">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image">
                            <div class="form-text">Current image: <?= htmlspecialchars($currentImage) ?></div>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <button type="button" class="btn btn-outline-danger" onclick="handleClose()">
                                <i class="fa-solid fa-xmark"></i> Close
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Changes
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
    <script>
        function handleClose() {
            if (confirm('Apakah Anda yakin ingin menutup tab ini?')) {
                window.close();
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('image');
            const fileUploadArea = document.querySelector('.file-upload-area');
            const imagePreview = document.getElementById('image-preview');
            const validationFeedback = document.getElementById('validation-feedback');
            const maxFiles = 10;
            const maxSize = 2 * 1024 * 1024; // 2MB
            const maxWidth = 2000;
            const maxHeight = 2000;
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            // Drag-and-Drop Functionality
            fileUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUploadArea.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', () => {
                fileUploadArea.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUploadArea.classList.remove('dragover');
                fileInput.files = e.dataTransfer.files;
                handleFiles(fileInput.files);
            });

            // File Input Change Event
            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });

            // Handle File Validation and Preview
            async function handleFiles(files) {
                validationFeedback.textContent = '';
                imagePreview.innerHTML = '';
                let errors = [];

                if (files.length > maxFiles) {
                    errors.push(`❌ You can upload a maximum of ${maxFiles} files.`);
                    fileInput.value = '';
                    validationFeedback.textContent = errors.join('\n');
                    return;
                }

                for (const file of files) {
                    const fileErrors = [];
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.classList.add('img-thumbnail', 'preview-image');
                    img.style.maxWidth = '150px';
                    img.style.maxHeight = '150px';

                    // Validate File Type
                    if (!allowedTypes.includes(file.type)) {
                        fileErrors.push(`❌ ${file.name}: Invalid file type (only JPG, PNG, WEBP allowed).`);
                    }

                    // Validate File Size
                    if (file.size > maxSize) {
                        fileErrors.push(`❌ ${file.name}: File size exceeds 2MB.`);
                    }

                    // Validate Image Dimensions
                    try {
                        await new Promise((resolve, reject) => {
                            img.onload = () => {
                                if (img.naturalWidth > maxWidth || img.naturalHeight > maxHeight) {
                                    reject(`❌ ${file.name}: Dimensions exceed ${maxWidth}x${maxHeight}px.`);
                                } else {
                                    resolve();
                                }
                            };
                            img.onerror = () => {
                                reject(`❌ ${file.name}: Invalid image file.`);
                            };
                        });
                    } catch (error) {
                        fileErrors.push(error);
                    }

                    // Display Preview or Errors
                    if (fileErrors.length > 0) {
                        errors.push(...fileErrors);
                    } else {
                        const previewContainer = document.createElement('div');
                        previewContainer.classList.add('position-relative', 'd-inline-block');
                        previewContainer.innerHTML = `
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="removePreview(this)">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    `;
                        previewContainer.querySelector('button').insertAdjacentElement('beforebegin', img);
                        imagePreview.appendChild(previewContainer);
                    }
                }

                if (errors.length > 0) {
                    validationFeedback.textContent = errors.join('\n');
                    fileInput.value = '';
                }
            }

            // Remove Preview Function
            window.removePreview = function (button) {
                const previewContainer = button.closest('div');
                previewContainer.remove();
            };
        });
    </script>
</body>

</html>