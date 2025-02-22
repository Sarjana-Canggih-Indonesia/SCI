<?php
// aboutUs.php

// Memuat config dan dependensi
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/user_actions_config.php';

// Memulai sesi apabila tidak ada
startSession();

// Memuat konfigurasi URL Dinamis
$config = getEnvironmentConfig(); // Load environment configuration
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']); // Get the base URL from the configuration
$isLive = $config['is_live'];
// Deteksi environment
$isLiveEnvironment = ($config['BASE_URL'] === $_ENV['LIVE_URL']);
setCacheHeaders($isLive); // Set header no cache saat local environment
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tentang Kami - Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>favicon.ico" />
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css" />
</head>

<body>
    <!--========== INSERT HEADER.PHP ==========-->
    <?php include __DIR__ . '/includes/header.php'; ?>
    <!--========== AKHIR INSERT HEADER.PHP ==========-->

    <!--========== AREA SCROLL TO TOP ==========-->
    <section class="scroll">
        <!-- Scroll to Top Button -->
        <a href="#" class="scroll-to-top" id="scrollToTopBtn">
            <i class="fa-solid fa-angles-up"></i>
        </a>
    </section>
    <!--========== AKHIR AREA SCROLL TO TOP ==========-->

    <!--================ AREA JUMBOTRON =================-->
    <section class="jumbotron-pk tentang-kami">
        <!-- PLACEHOLDER -->
    </section>
    <!--================Akhir Area Jumbotron=================-->
    <br />
    <br />

    <!--================ AREA JUDUL HALAMAN =================-->
    <section class="judul-halaman-about">
        <div class="container">
            <h2 class="text-center mb-4" style="font-size: 3rem; font-weight: 700;">
                Tentang Kami
            </h2>
        </div>
    </section>
    <!--================ AKHIR AREA JUDUL HALAMAN =================-->

    <!--================Area Kata Mereka=================-->
    <section class="katamereka">
        <div class="container">
            <div class="row">
                <p class="fs-2 fw-bold">Kata mereka "Joki"? Kami jawab:</p>
                <p class="fs-5" style="text-align: justify">
                    "Kami tidak berbeda dengan jasa pihak ketiga lainnya yang menjadikan
                    ide-ide pelanggan kami menjadi karya nyata. Pengalaman kami
                    menunjukkan bahwa jasa kami tidak serta merta memberikan kuasa bagi
                    siapapun untuk mencapai apa yang tidak mereka mampu. Kami mengerti
                    sulitnya memahami konsep penelitian. Karena itu, melalui jasa
                    asistensi, kami membantu klien mempercepat proses belajar penelitian
                    dengan melakukannya dan mendampingi setiap prosesnya."
                </p>
            </div>
        </div>
    </section>
    <!--================End of Area Kata Mereka=================-->
    <!--================Area VISI MISI=================-->
    <section class="tentangkami py-5">
        <div class="container tk-text">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="fs-2 text-center fw-bold">
                        VISI <font color="#FFC300">KAMI</font>
                    </h4>
                    <p class="fs-2 text-center">
                        "Mendukung percepatan dan peningkatan kualitas penelitian di
                        Indonesia."
                    </p>
                </div>
                <div class="col-lg-12 py-5">
                    <h4 class="fs-2 text-center fw-bold">
                        MISI <font color="#FFC300">KAMI</font>
                    </h4>
                    <ol>
                        <li class="fs-5">
                            Mendukung pemerataan dengan mendampingi mahasiswa yang memiliki
                            waktu yang terbatas.
                        </li>
                        <li class="fs-5">
                            Mendorong ide-ide brilian agar dapat tersampaikan secara
                            analitis dan kritis melalui berbagai bentuk penelitian.
                        </li>
                        <li class="fs-5">
                            Memandu mahasiswa Indonesia mengenai cara melakukan penelitian
                            yang terstruktur dan sistematis.
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!--================Akhir Area VISI MISI=================-->
    <!--================Area Our Values=================-->
    <section class="ourvalues">
        <div class="container">
            <div class="row">
                <img src="<?php echo $baseUrl; ?>assets/images/ourValues.webp" alt="" />
            </div>
        </div>
    </section>
    <!--================Akhir Area Our Values=================-->
    <br /><br /><br />

    <!--================ AREA FOOTER =================-->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <!--================ AKHIR AREA FOOTER =================-->

    <!--================ Area External JS libraries =================-->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
    <!--================ Akhir Area External JS libraries =================-->
</body>

</html>