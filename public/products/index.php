<?php
session_start();

// Menggunakan konfigurasi dari config.php dengan path relatif menggunakan __DIR__
require_once __DIR__ . '/../config/config.php';

// Buat koneksi ke database
$conn = getPDOConnection();

// Menghancurkan koneksi setelah selesai (opsional)
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/SCI/favicon.ico" />

    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Slick Slider css -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />

    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="/SCI/assets/css/styles.css" />

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

    <!--========== AREA PRODUCTS ==========-->
    <section class="products-area jarak-kustom">
        <div class="container">
            <div class="row">
                <?php
                require_once 'products.php';
                ?>

                <!-- Filter Section -->
                <div class="col-12 col-md-4 col-lg-2">
                    <h5>Filter Products</h5>
                    <div>
                        <h6>Category</h6>
                        <?php
                        // Menampilkan kategori menggunakan data dari array $categories
                        foreach ($categories as $key => $category) {
                            echo '<div class="form-check">
                        <input class="form-check-input product-filter" type="checkbox" value="' . $key . '" id="category-' . strtolower(str_replace(" ", "", $category)) . '">
                        <label class="form-check-label" for="category-' . strtolower(str_replace(" ", "", $category)) . '">' . htmlspecialchars($category) . '</label>
                    </div>';
                        }
                        ?>
                    </div>
                    <button id="apply-filters" class="btn btn-primary w-100 my-3">Apply Filter</button>
                </div>
                <!-- End of Filter Section -->

                <!-- Products Section -->
                <div class="col-12 col-md-8 col-lg-10">
                    <div class="row" id="products-container">
                        <?php
                        // Menampilkan produk dari array $products
                        foreach ($products as $product): ?>
                            <article class="col-12 col-sm-6 col-md-6 col-lg-3 mb-4 product-card"
                                data-category="<?php echo htmlspecialchars($product['category_key']); ?>">
                                <div class="card h-100">
                                    <a href="<?php echo htmlspecialchars($product['link']); ?>" class="no-underline">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                            class="card-img-top img-fluid"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </a>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="<?php echo htmlspecialchars($product['link']); ?>"
                                                class="no-underline">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text">Price: $<?php echo htmlspecialchars($product['price']); ?></p>
                                        <p class="card-text mb-3">Category:
                                            <?php echo htmlspecialchars($product['category']); ?></p>
                                        <p class="card-text mb-3"><?php echo htmlspecialchars($product['description']); ?>
                                        </p>
                                        <button type="button" class="btn btn-primary w-100"
                                            onclick="window.location.href='<?php echo htmlspecialchars($product['link']); ?>';">Details</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- End of Products Section -->
            </div>
        </div>
    </section>
    <!--========== AKHIR AREA PRODUCTS ==========-->
</body>

<!--================ AREA FOOTER =================-->
<?php include '../includes/footer.php'; ?>
<!--================ AKHIR AREA FOOTER =================-->

<!-- jQuery 3.7.1 (necessary for Bootstrap's JavaScript plugins) -->
<script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.slim.min.js"></script>
<!-- POPPER 2.11.8 -->
<script type="text/javascript" src="https://unpkg.com/@popperjs/core@2"></script>
<!--Bootstrap bundle min js-->
<script type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<!-- Slick Slider JS -->
<script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
<!-- Custom JS -->
<script type="text/javascript" src="/SCI/assets/js/custom.js"></script>

</html>