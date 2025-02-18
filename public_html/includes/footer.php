<?php
// header.php
require_once __DIR__ . '/../../config/config.php';

// Call Function to Load Local or Live Environment
$config = getEnvironmentConfig();
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <!-- Footer -->
    <div class="mt-5">
        <footer class="text-center text-lg-start text-white" style="background-color: #001d3d;">
            <!-- Section: Social media -->
            <div style="background-color: #003566;">
                <section class="container d-flex justify-content-between p-4">
                    <div>
                        <span>Get connected with us on social networks:</span>
                    </div>
                    <div>
                        <a href="<?php echo $_ENV['SOCIAL_FACEBOOK']; ?>" class="text-white me-4"
                            style="text-decoration: none;"> <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?php echo $_ENV['SOCIAL_TWITTER']; ?>" class="text-white me-4"
                            style="text-decoration: none;"><i class="fab fa-twitter"></i>
                        </a>
                        <a href="<?php echo $_ENV['SOCIAL_INSTAGRAM']; ?>" class="text-white me-4"
                            style="text-decoration: none;"><i class="fab fa-instagram"></i>
                        </a>
                        <a href="<?php echo $_ENV['SOCIAL_LINKEDIN']; ?>" class="text-white me-4"
                            style="text-decoration: none;"><i class="fab fa-linkedin"></i>
                        </a>
                        <a href="<?php echo $_ENV['SOCIAL_GITHUB']; ?>" class="text-white me-4"
                            style="text-decoration: none;"><i class="fab fa-github"></i>
                        </a>
                    </div>
                </section>
            </div>
            <!-- Section: Social media -->

            <!-- Section: Links -->
            <section class="mt-5">
                <div class="container text-center text-md-start">
                    <div class="row">
                        <!-- Column 1 -->
                        <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
                            <h6 class="text-uppercase fw-bold">Sarjana Canggih Indonesia</h6>
                            <hr class="mb-4 mt-0 d-inline-block mx-auto"
                                style="width: 60px; background-color: #7c4dff; height: 2px;">
                            <p>
                                Sarjana Canggih Indonesia adalah platform yang menyediakan layanan bimbingan dan
                                asistensi bagi
                                pelajar serta profesional dalam menyelesaikan tugas akademik maupun pekerjaan. Kami
                                membantu dalam pemahaman materi, analisis data, dan berbagai kebutuhan lainnya untuk
                                mendukung pengembangan keterampilan serta pencapaian tujuan secara mandiri dan
                                profesional.
                            </p>
                        </div>

                        <!-- Column 2 -->
                        <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4">
                            <h6 class="text-uppercase fw-bold"><a href="<?php echo $baseUrl; ?>products"
                                    style="text-decoration: none; color:inherit;">Products</a></h6>
                            <hr class="mb-4 mt-0 d-inline-block mx-auto"
                                style="width: 60px; background-color: #7c4dff; height: 2px;">
                            <p><a href="<?php echo $baseUrl; ?>products/" class="text-white">Desain PPT</a></p>
                            <p><a href="<?php echo $baseUrl; ?>products/" class="text-white">Asistensi Tugas
                                    Akhir</a></p>
                            <p><a href="<?php echo $baseUrl; ?>products/" class="text-white">Analisis Data
                                    (Mahasiswa)</a></p>
                            <p><a href="<?php echo $baseUrl; ?>products/" class="text-white">Analisis Data
                                    (Profesional)</a></p>
                            <p><a href="<?php echo $baseUrl; ?>products/" class="text-white">E-Learning Research</a>
                            </p>
                            <p><a href="<?php echo $baseUrl; ?>products/" class="text-white">Translasi</a></p>
                            <p><a href="<?php echo $baseUrl; ?>products/" class="text-white">Web Design</a></p>
                        </div>

                        <!-- Column 3 -->
                        <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
                            <h6 class="text-uppercase fw-bold">Useful links</h6>
                            <hr class="mb-4 mt-0 d-inline-block mx-auto"
                                style="width: 60px; background-color: #7c4dff; height: 2px;">
                            <p><a href="<?php echo $baseUrl; ?>dashboard/" class="text-white">Your Account</a></p>
                            <p><a href="<?php echo $baseUrl; ?>promo/" class="text-white">Promo</a></p>
                            <p><a href="<?php echo $baseUrl; ?>blog/" class="text-white">Blogs</a></p>
                            <p><a href="<?php echo $baseUrl; ?>about" class="text-white">About Us</a></p>
                            <p><a href="<?php echo $baseUrl; ?>contact" class="text-white">Contact Us</a>
                            </p>
                        </div>

                        <!-- Column 4 -->
                        <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
                            <h6 class="text-uppercase fw-bold">
                                Contact
                            </h6>
                            <hr class="mb-4 mt-0 d-inline-block mx-auto"
                                style="width: 60px; background-color: #7c4dff; height: 2px;">
                            <p><i class="fas fa-home"></i> Kecamatan Singosari, Kabupaten Malang, 65153</p>
                            <p><i class="fas fa-envelope"></i> admin@sarjanacanggihindonesia.com</p>
                            <a class="no-underline" href="<?php echo $baseUrl; ?>contact/"><i class="fas fa-phone"></i>
                                <span id="phone-number"></span></a>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section: Links -->
            <div class="container">
                <hr>
            </div>
            <!-- Copyright -->
            <div class="text-center p-3" style="background-color: #001d3d">
                © 2024 - 2025 All rights reserved.
                <a class="text-white" href="<?php echo $baseUrl; ?>">Sarjana Canggih
                    Indonesia</a>
            </div>
            <!-- Akhir Copyright -->
        </footer>
    </div>
    <!-- Footer End -->
</body>

<script>
    document.getElementById("phone-number").innerText = "<?php echo $_ENV['PHONE_NUMBER']; ?>";
</script>

</html>