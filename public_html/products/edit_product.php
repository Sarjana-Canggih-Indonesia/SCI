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
try {
    $currentImages = getProductImages($pdo, $productId);
} catch (RuntimeException $e) {
    // Handle error sesuai kebutuhan
    $currentImages = [];
    error_log($e->getMessage());
}

// Dapatkan data produk dari database
$product = getProductBySlugAndOptimus($slug, $encodedId, $config, $env);
if ($product) {
    $product['image_path'] = getProductImagePath($product['product_id'], $config, $env);
}
if (isset($_SESSION['old_input'])) {
    $productData = array_merge($product, $_SESSION['old_input']); // Gabung data
} else {
    $productData = $product; // Pakai data database
}

// Jika produk tidak ditemukan
if (!$product) {
    http_response_code(404);
    include_once __DIR__ . '/../../404.php';
    exit();
}

// Ambil data yang diperlukan dari $product
$currentImage = $product['image_path'] ?? 'default_product.png';

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
if (empty($categories)) {
    die("Error: Tidak ada kategori yang ditemukan di database!");
}
$products = getAllProductsWithCategoriesAndTags($config, $env);
$categoryRelations = getProductCategoryRelations($product['product_id'], $config, $env);
$currentCategoryIds = $categoryRelations['category_ids'] ?? [];
$selectedCategoryId = $productData['productCategory'] ?? (isset($currentCategoryIds[0]) ? $currentCategoryIds[0] : null);
$currentTags = getProductTagNames($productId, $pdo);
$currentTagIds = getProductTagRelations($productId, $config, $env);
// Jika tidak ada tag, set sebagai array kosong
$currentTagIds = $currentTagIds ?: [];

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
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/halaman-admin.css" />
    <style>
        .file-upload-area {
            transition: background-color 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area.dragover {
            background-color: #e9f5ff !important;
            border-color: #86b7fe !important;
        }

        .preview-image {
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
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
                        <!-- Gambar Produk LIVE VERSION -->
                        <img src="<?= $baseUrl . ($product['image_path'] ?? 'assets/images/default_product.png') ?>"
                            class="img-fluid mb-3 rounded" alt="Product Image"
                            style="max-height: 200px; object-fit: cover;">
                        <!-- Nama Produk LIVE VERSION -->
                        <h3 class="mb-2"><?= htmlspecialchars($product['product_name']) ?></h3>

                        <!-- Kategori dan Status LIVE VERSION -->
                        <div class="d-flex gap-2 mb-3">
                            <!-- Kategori LIVE VERSION -->
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
                            <!-- Status Penjualan LIVE VERSION -->
                            <?= getProductStatus($product['active']) ?>
                        </div>

                        <!-- Harga LIVE VERSION -->
                        <h4 class="text-danger mb-3">
                            Rp <?= number_format($product['price_amount'], 0, ',', '.') ?>
                        </h4>

                        <!-- Deskripsi LIVE VERSION -->
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="text-muted">
                                <?= htmlspecialchars($product['description'] ?? 'No description available') ?>
                            </p>
                        </div>

                        <!-- Tags LIVE VERSION -->
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Tags</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if (!empty($currentTags)): ?>
                                        <?php foreach ($currentTags as $tag): ?>
                                            <span class="badge bg-info"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No tags</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN - EDIT FORM -->
                <div class="col-md-6 edit-section">
                    <h4 class="section-title">Edit Product</h4>
                    <form action="<?= $baseUrl ?>edit-product/<?= $slug ?>/<?= $encodedId ?>" method="post"
                        enctype="multipart/form-data">
                        <!-- Produk ID -->
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <!-- Nama Produk EDIT FORM -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" id="name" name="product_name"
                                placeholder="Masukkan nama produk"
                                value="<?= htmlspecialchars($product['product_name'] ?? '') ?>">
                        </div>
                        <!-- Harga & Status Penjualan EDIT FORM -->
                        <div class="row g-3">
                            <!-- Harga EDIT FORM -->
                            <div class="col-md-6">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="price" name="price_amount"
                                        placeholder="50000.00" step="5000"
                                        value="<?= htmlspecialchars($product['price_amount'] ?? '') ?>">
                                </div>
                            </div>
                            <!-- Status Penjualan EDIT FORM -->
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="active">
                                    <option value="active" <?= $product['active'] === 'active' ? 'selected' : '' ?>>Active
                                    </option>
                                    <option value="inactive" <?= $product['active'] === 'inactive' ? 'selected' : '' ?>>
                                        Inactive</option>
                                </select>
                            </div>
                        </div>
                        <!-- Description EDIT FORM -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="product_description" rows="4"
                                placeholder="Masukkan deskripsi produk"><?= htmlspecialchars($product['description'] ?? '') ?>
                            </textarea>
                        </div>
                        <!-- Category EDIT FORM -->
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="productCategory">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['category_id']) ?>"
                                            <?= ($category['category_id'] == $selectedCategoryId) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Tags EDIT FORM -->
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags"
                                    placeholder="Masukkan tag, dipisahkan dengan koma"
                                    value="<?= htmlspecialchars(implode(', ', $currentTags)) ?>">
                            </div>
                        </div>
                        <!-- Image Produk / Layanan EDIT FORM -->
                        <div class="mb-4 mt-4">
                            <label for="image" class="form-label">Product Images</label>
                            <div class="file-upload-area border rounded p-3 text-center bg-light">
                                <p class="text-muted">Drag & drop images here or click to upload</p>
                                <input type="file" class="form-control d-none" id="image" name="productImages[]"
                                    multiple accept="image/*">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="document.getElementById('image').click()">
                                    <i class="fa-solid fa-upload"></i> Upload Images
                                </button>
                            </div>
                            <div id="validation-feedback" class="text-danger small mt-2"></div>
                            <div id="image-preview" class="d-flex flex-wrap gap-2 mt-3"></div>
                            <div class="current-images mb-4">
                                <label class="form-label">Current Images</label>
                                <div class="row row-cols-3 g-3">
                                    <?php foreach ($currentImages as $image): ?>
                                        <div class="col">
                                            <div class="card position-relative">
                                                <img src="<?= $baseUrl . $image['image_path'] ?>"
                                                    class="card-img-top preview-image" alt="Gambar Produk">
                                                <div class="card-footer">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="images_to_delete[]"
                                                            value="<?= htmlspecialchars($image['image_path']) ?>"
                                                            id="delete-<?= $image['image_id'] ?>">
                                                        <label class="form-check-label small text-danger"
                                                            for="delete-<?= $image['image_id'] ?>">
                                                            Hapus Gambar
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-text">Upload up to 10 images (JPG, PNG, WEBP), max 2MB each, dimensions up
                                to 2000x2000px.</div>
                        </div>

                        <!-- CSRF -->
                        <div class="csrf">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        </div>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <!-- Close Button -->
                            <button type="button" class="btn btn-outline-danger" onclick="handleClose()">
                                <i class="fa-solid fa-xmark"></i> Close
                            </button>
                            <!-- Save Changes Button -->
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
    <!-- Custom JS -->
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
            // ==================== FUNGSI UPLOAD GAMBAR ==================== //
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
                    errors.push(`❌ Maksimal upload ${maxFiles} file`);
                    fileInput.value = '';
                    validationFeedback.textContent = errors.join('\n');
                    return;
                }

                // Validasi total gambar
                const currentImageCount = <?= count($currentImages) ?>;
                const imagesToDeleteCount = document.querySelectorAll('input[name="images_to_delete[]"]:checked').length;
                const remainingCurrent = currentImageCount - imagesToDeleteCount;
                const totalAfterUpload = remainingCurrent + files.length;

                if (totalAfterUpload > 10) {
                    errors.push(`❌ Maksimal total 10 gambar. Setelah upload ini akan menjadi ${totalAfterUpload} gambar`);
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

                    // Validasi tipe file
                    if (!allowedTypes.includes(file.type)) {
                        fileErrors.push(`❌ ${file.name}: Format file tidak didukung`);
                    }

                    // Validasi ukuran file
                    if (file.size > maxSize) {
                        fileErrors.push(`❌ ${file.name}: Ukuran file melebihi 2MB`);
                    }

                    // Validasi dimensi gambar
                    try {
                        await new Promise((resolve, reject) => {
                            img.onload = () => {
                                if (img.naturalWidth > maxWidth || img.naturalHeight > maxHeight) {
                                    reject(`❌ ${file.name}: Dimensi melebihi 2000x2000px`);
                                } else {
                                    resolve();
                                }
                            };
                            img.onerror = () => {
                                reject(`❌ ${file.name}: File gambar tidak valid`);
                            };
                        });
                    } catch (error) {
                        fileErrors.push(error);
                    }

                    // Tampilkan preview atau error
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

            // Fungsi hapus preview
            window.removePreview = function (button) {
                const previewContainer = button.closest('div');
                previewContainer.remove();

                // Update files array
                const dataTransfer = new DataTransfer();
                const files = Array.from(fileInput.files);
                const filenames = Array.from(imagePreview.querySelectorAll('img'))
                    .map(img => img.src.split('/').pop());

                files.forEach((file, index) => {
                    if (!filenames.includes(file.name)) {
                        dataTransfer.items.add(file);
                    }
                });

                fileInput.files = dataTransfer.files;
            };
        });

        // Fungsi umum
        function handleClose() {
            if (confirm('Apakah Anda yakin ingin menutup tab ini?')) {
                window.location.href = BASE_URL + 'manage_products';
            }
        }
    </script>
</body>

</html>