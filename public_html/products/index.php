<?php
// Memuat config dan dependensi
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

// Memulai sesi apabila tidak ada
startSession();

// Buat koneksi ke database
$conn = getPDOConnection($config, $env);

// Memuat konfigurasi URL Dinamis
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$env = ($_SERVER['HTTP_HOST'] === 'localhost') ? 'local' : 'live';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>favicon.ico" />
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="../assets/css/styles.css" />

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
                                            <?php echo htmlspecialchars($product['category']); ?>
                                        </p>
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

<!-- External JS libraries -->
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/slick.min.js"></script>
<!-- Custom JS -->
<script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>

</html>