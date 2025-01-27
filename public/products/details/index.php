<?php
// Memanggil data produk dari products.php
require_once __DIR__ . '/../products.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Cari produk berdasarkan ID
$product = array_filter($products, function ($p) use ($id) {
    return $p['id'] === $id;
});

// Ambil produk pertama yang ditemukan (jika ada)
$product = reset($product);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['name']) : 'Product Not Found'; ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/SCI/favicon.ico" />

    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Slick Slider css -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />

    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="/SCI/assets/css/styles.css" />
</head>

<body>
    <!--========== INSERT HEADER.PHP ==========-->
    <?php include '../../includes/header.php'; ?>
    <!--========== AKHIR INSERT HEADER.PHP ==========-->

    <!--========== AREA DETIL PRODUK ==========-->
    <div class="container" style="margin-top: 120px;">
        <!-- Sticky Back Button -->
        <div class="back-button">
            <a href="../" class="btn btn-outline-primary" onclick="return confirm('Are you sure you want to go back?');">
                <i class="fa fa-arrow-left"></i> Back to Products</a>
        </div>

        <?php if ($product): ?>
            <div class="product-details my-3">
                <div class="row">
                    <div class="col-md-6">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    </div>
                    <div class="col-md-6">
                        <h1 class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <p class="text-body-secondary"><?php echo htmlspecialchars($product['category']); ?></p> <!-- Hapus bagian subcategory -->
                        <h3 class="fw-bold">$<?php echo htmlspecialchars($product['price']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <hr>
                        <p><?php echo htmlspecialchars($product['about_this_item']); ?></p>
                        <hr>
                        <ul>
                            <?php foreach ($product['list'] as $item): ?>
                                <li><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="../../contact/" class="btn btn-primary">Add to Cart</a>
                        <a href="../../contact/" class="btn btn-outline-primary">Contact Us</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center mt-5">
                <h1>Product Not Found</h1>
                <a href="products.php" class="btn btn-secondary">Back to Products</a>
            </div>
        <?php endif; ?>
    </div>
    <!--========== AKHIR AREA DETIL PRODUK ==========-->
</body>

<!--================ AREA FOOTER =================-->
<?php include '../../includes/footer.php'; ?>
<!--================ AKHIR AREA FOOTER =================-->

<!-- jQuery 3.7.1 (necessary for Bootstrap's JavaScript plugins) -->
<script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.slim.min.js"></script>
<!-- POPPER 2.11.8 -->
<script type="text/javascript" src="https://unpkg.com/@popperjs/core@2"></script>
<!--Bootstrap bundle min js-->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<!-- Slick Slider JS -->
<script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
<!-- Custom JS -->
<script type="text/javascript" src="./assets/js/custom.js"></script>

</html>