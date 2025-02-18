<?php
// index.php

// Memuat config dan dependensi
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/user_actions_config.php';

use Carbon\Carbon;

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
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sarjana Canggih Indonesia</title>
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
</head>

<body style="background-color: #f7f9fb;">

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

  <!--========== AREA CAROUSEL ==========-->
  <section class="carousel-cover">
    <div id="bootstrapCarousel" class="carousel slide carousel-fade" data-bs-interval="false">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#bootstrapCarousel" data-bs-slide-to="0" class="active d-none d-md-block"
          aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#bootstrapCarousel" data-bs-slide-to="1" class="d-none d-md-block"
          aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#bootstrapCarousel" data-bs-slide-to="2" class="d-none d-md-block"
          aria-label="Slide 3"></button>
        <button type="button" data-bs-target="#bootstrapCarousel" data-bs-slide-to="3" class="d-none d-md-block"
          aria-label="Slide 4"></button>
        <button type="button" data-bs-target="#bootstrapCarousel" data-bs-slide-to="4" class="d-none d-md-block"
          aria-label="Slide 5"></button>
        <button type="button" data-bs-target="#bootstrapCarousel" data-bs-slide-to="5" class="d-none d-md-block"
          aria-label="Slide 6"></button>
      </div>

      <!-- Konten Carousel -->
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src=" <?php echo $baseUrl; ?>assets/images/carousel/pk1.webp" class="d-block w-100" alt="Slide 1" />
          <div class="carousel-caption">
            <h4 class="my-sm-3">Capai yang terbaik</h4>
            <p class="my-sm-5">Jangan tunda lagi, selesaikan tugas akhirmu bersama Penelitian Kita</p>
            <div class="container">
              <div class="row"> <a href="<?php echo $baseUrl; ?>contact/"
                  class="btn btn-primary carousel-btn d-none d-sm-inline-block btn-pesan" role="button">
                  <i class="fa-solid fa-cart-shopping"></i>
                  &nbsp;Pesan Sekarang
                </a>
                &nbsp;
                <a href="https://dub.sh/repqBk8" class="btn btn-success carousel-btn d-none d-sm-inline-block btn-wa"
                  role="button">
                  <i class="fa-brands fa-whatsapp"></i>
                  &nbsp; Chat on Whatsapp
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <img src=" <?php echo $baseUrl; ?>assets/images/carousel/pk2.webp" class="d-block w-100" alt="Slide 2"
            loading="lazy" />
          <div class="carousel-caption">
            <h4 class="my-sm-3">Kami adalah safe space-mu.</h4>
            <p class="my-sm-5">Rasi kami selalu siap sedia menjawab pertanyaan penelitian kamu.</p>
            <div class="container">
              <div class="row"> <a href="<?php echo $baseUrl; ?>contact/"
                  class="btn btn-primary carousel-btn d-none d-sm-inline-block btn-pesan" role="button">
                  <i class="fa-solid fa-cart-shopping"></i>
                  &nbsp;Pesan Sekarang
                </a>
                &nbsp;
                <a href="https://dub.sh/repqBk8" class="btn btn-success carousel-btn d-none d-sm-inline-block btn-wa"
                  role="button">
                  <i class="fa-brands fa-whatsapp"></i>
                  &nbsp; Chat on Whatsapp
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <img src=" <?php echo $baseUrl; ?>assets/images/carousel/pk3.webp" class="d-block w-100" alt="Slide 3"
            loading="lazy" />
          <div class="carousel-caption">
            <h4 class="my-sm-3">Banyak promosi dan layanan spesial untukmu!</h4>
            <p class="my-sm-5">Eksplor layanan tugas akhir dan jurnal Penelitian Kita.</p>
            <div class="container">
              <div class="row"> <a href="<?php echo $baseUrl; ?>contact/"
                  class="btn btn-primary carousel-btn d-none d-sm-inline-block btn-pesan" role="button">
                  <i class="fa-solid fa-cart-shopping"></i>
                  &nbsp;Pesan Sekarang
                </a>
                &nbsp;
                <a href="https://dub.sh/repqBk8" class="btn btn-success carousel-btn d-none d-sm-inline-block btn-wa"
                  role="button">
                  <i class="fa-brands fa-whatsapp"></i>
                  &nbsp; Chat on Whatsapp
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <img src=" <?php echo $baseUrl; ?>assets/images/carousel/pk4.webp" class="d-block w-100" alt="Slide 4"
            loading="lazy" />
          <div class="carousel-caption">
            <h4 class="my-sm-3">Paham sepenuhnya dengan video penjelasan.</h4>
            <p class="my-sm-5">Layanan paling lengkap ini tersedia di Paket Prime.</p>
            <div class="container">
              <div class="row"> <a href="<?php echo $baseUrl; ?>contact/"
                  class="btn btn-primary carousel-btn d-none d-sm-inline-block btn-pesan" role="button">
                  <i class="fa-solid fa-cart-shopping"></i>
                  &nbsp;Pesan Sekarang
                </a>
                &nbsp;
                <a href="https://dub.sh/repqBk8" class="btn btn-success carousel-btn d-none d-sm-inline-block btn-wa"
                  role="button">
                  <i class="fa-brands fa-whatsapp"></i>
                  &nbsp; Chat on Whatsapp
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <img src=" <?php echo $baseUrl; ?>assets/images/carousel/pk5.webp" class="d-block w-100" alt="Slide 5"
            loading="lazy" />
          <div class="carousel-caption">
            <h4 class="my-sm-3">Kejar deadline mepet tepat waktu.</h4>
            <p class="my-sm-5">Kami Menyediakan Layanan Percepatan untuk Tugas Akhir.</p>
            <div class="container">
              <div class="row"> <a href="<?php echo $baseUrl; ?>contact/"
                  class="btn btn-primary carousel-btn d-none d-sm-inline-block btn-pesan" role="button">
                  <i class="fa-solid fa-cart-shopping"></i>
                  &nbsp;Pesan Sekarang
                </a>
                &nbsp;
                <a href="https://dub.sh/repqBk8" class="btn btn-success carousel-btn d-none d-sm-inline-block btn-wa"
                  role="button">
                  <i class="fa-brands fa-whatsapp"></i>
                  &nbsp; Chat on Whatsapp
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <img src=" <?php echo $baseUrl; ?>assets/images/carousel/pk6.webp" class="d-block w-100" alt="Slide 6"
            loading="lazy" />
          <div class="carousel-caption">
            <h4 class="my-sm-3">Masuk bareng, lulus juga bareng!</h4>
            <p class="my-sm-5">Dapatkan promosi spesial untuk tugas akhir bersama teman-teman kamu.</p>
            <div class="container">
              <div class="row"> <a href="<?php echo $baseUrl; ?>contact/"
                  class="btn btn-primary carousel-btn d-none d-sm-inline-block btn-pesan" role="button">
                  <i class="fa-solid fa-cart-shopping"></i>
                  &nbsp;Pesan Sekarang
                </a>
                &nbsp;
                <a href="https://dub.sh/repqBk8" class="btn btn-success carousel-btn d-none d-sm-inline-block btn-wa"
                  role="button">
                  <i class="fa-brands fa-whatsapp"></i>
                  &nbsp; Chat on Whatsapp
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#bootstrapCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#bootstrapCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
  </section>
  <!--========== AKHIR AREA CAROUSEL ==========-->

  <!--========== AREA KENAPA KAMI ==========-->
  <section class="kenapakami">
    <div class="container-fluid">
      <div class="heading_2">
        <h2 class="mt-5 mt-sm-5 mt-md-0">Kenapa Memilih Sarjana Canggih Indonesia</h2>
      </div>
      <div class="row kenapa_inner">
        <div class="col-lg-3">
          <div class="kenapa_item">
            <img src=" <?php echo $baseUrl; ?>assets/images/knp/1.webp" alt="" />
            <h4>Pilihan paket terlengkap.</h4>
            <p>Dari paket basic hingga unlimited, semua ada!</p>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="kenapa_item">
            <img src=" <?php echo $baseUrl; ?>assets/images/knp/2.webp" alt="" />
            <h4>Layanan prima.</h4>
            <p>Kami dukung penuh proses penelitian kamu hingga selesai. Rasi kami siap menjawab pertanyaan kamu.</p>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="kenapa_item">
            <img src=" <?php echo $baseUrl; ?>assets/images/knp/3.webp" alt="" />
            <h4>Dipercaya di Indonesia hingga UK.</h4>
            <p>
              Kami telah membantu peneliti di berbagai kota dan lintas benua untuk menyelesaikan tugas akhirnya,
              bahkan mempublikasi hingga SINTA 1.
            </p>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="kenapa_item">
            <img src=" <?php echo $baseUrl; ?>assets/images/knp/4.webp" alt="" />
            <h4>Pioneer paket unlimited dan video penjelasan.</h4>
            <p>Kami memberikan layanan yang paling spesial untuk memastikan kesiapan kamu.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!--========== AKHIR AREA KENAPA KAMI ==========-->

  <!--========== AREA WUWG ==========-->
  <section class="features-wuwg">
    <div class="container">
      <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12">
          <div class="floatingheader">
            <h3 class="wuwg-h3-alt">
              What
              <font color="#ffc300">You Will Get</font>
            </h3>
            <h3 class="feature-wuwg-h3 leftalligned">
              Mengerjakan tugas akhir, menganalisis data maupun membuat jurnal menggunakan layanan kami bakalan anti
              ribet!
            </h3>
            <div class="description_leftalligned">Jadi, tenang aja! Target wisuda kamu, aman!</div>
          </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12">
          <div class="pointswrapper">
            <div class="w-layout-grid grid">
              <div class="pointcontentwrapper light">
                <div class="pointnumber">
                  <img src=" <?php echo $baseUrl; ?>assets/images/path.svg" loading="lazy" alt="Checkmark icon"
                    class="hero-check" />
                  <div class="pointnumbertxt">You Will Get #1</div>
                </div>
                <h4 class="pointtitle">Berbagai pilihan alat analisis data.</h4>
                <div class="pointdescription">
                  Kami menyediakan berbagai pilihan alat analisis data, baik untuk penelitian kuantitatif maupun
                  kualitatif.
                </div>
              </div>
              <div class="pointcontentwrapper light">
                <div class="pointnumber">
                  <img src=" <?php echo $baseUrl; ?>assets/images/path.svg" loading="lazy" alt="Checkmark icon"
                    class="hero-check" />
                  <div class="pointnumbertxt">You Will Get #2</div>
                </div>
                <h4 class="pointtitle">Referensi berkualitas.</h4>
                <div class="pointdescription">
                  Kami mengutamakan referensi dari basis data terpercaya seperti Scopus, WoS, PubMed, dan sumber lain
                  bila diperlukan, seperti Google Scholar.
                </div>
              </div>
              <div class="pointcontentwrapper light">
                <div class="pointnumber">
                  <img src=" <?php echo $baseUrl; ?>assets/images/path.svg" loading="lazy" alt="Checkmark icon"
                    class="hero-check" />
                  <div class="pointnumbertxt">You Will Get #3</div>
                </div>
                <h4 class="pointtitle">Jaminan anti plagiasi dengan batasan Turnitin &lt;30%.</h4>
                <div class="pointdescription">
                  Kami memberikan jaminan standar Turnitin &lt;30% yang ada di semua paket penelitian. Butuh lebih
                  rendah? Bisa konsultasikan dengan Rasi kami juga loh!
                </div>
              </div>
              <div class="pointcontentwrapper light">
                <div class="pointnumber">
                  <img src=" <?php echo $baseUrl; ?>assets/images/path.svg" loading="lazy" alt="Checkmark icon"
                    class="hero-check" />
                  <div class="pointnumbertxt">You Will Get #4</div>
                </div>
                <h4 class="pointtitle">Hasil karya orisinal, bukan copy paste.</h4>
                <div class="pointdescription">
                  Kami hanya menggunakan referensi jurnal terakreditasi dan buku-buku terpercaya. Semua data yang
                  diolah dan dianalisis tidak digunakan ulang untuk klien lain
                </div>
              </div>
              <div class="pointcontentwrapper light">
                <div class="pointnumber">
                  <img src=" <?php echo $baseUrl; ?>assets/images/path.svg" loading="lazy" alt="Checkmark icon"
                    class="hero-check" />
                  <div class="pointnumbertxt">You Will Get #5</div>
                </div>
                <h4 class="pointtitle">Unlimited revisi dan konsultasi (khusus paket unlimited dan Prime).</h4>
                <div class="pointdescription">
                  Kami menyediakan pilihan paket unlimited, menyelesaikan tugas akhir kamu jadi lebih tenang!
                </div>
              </div>
              <div class="pointcontentwrapper light" style="padding-bottom: 4rem">
                <div class="pointnumber">
                  <img src=" <?php echo $baseUrl; ?>assets/images/path.svg" loading="lazy" alt="Checkmark icon"
                    class="hero-check" />
                  <div class="pointnumbertxt">You Will Get #6</div>
                </div>
                <h4 class="pointtitle">Video penjelasan (khusus paket Prime)</h4>
                <div class="pointdescription">
                  Hasil penelitian tetaplah milik kamu. Karena itu, kami menyediakan video penjelasan untuk tugas
                  akhir kamu, sehingga kamu bisa lebih mudah memahaminya dan lebih siap saat harus
                  mempresentasikannya.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!--========== AKHIR AREA WUWG ==========-->

  <!--========== Area Our Technologies ==========-->
  <section class="ourteknologi">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-2">
          <!-- placeholder -->
        </div>
        <div class="col-md-8">
          <div class="heading_2">
            <h2>Our Technologies</h2>
          </div>
        </div>
        <div class="col-md-2">
          <!-- placeholder -->
        </div>
      </div>
    </div>
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-2">
          <!-- placeholder -->
        </div>
        <div class="col-md-8">
          <section class="tech-logos slider">
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/EViews.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/IBMSPSS.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/Mendeley.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/NVIVO.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/POP.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/Python.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/R.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/SmartPLS.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/Turnitin.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/VOSViewer.webp" />
            </div>
            <div class="slide">
              <img src=" <?php echo $baseUrl; ?>assets/images/logo/Zotero.webp" />
            </div>
          </section>
        </div>
        <div class="col-md-2">
          <!-- placeholder -->
        </div>
      </div>
    </div>
  </section>
  <br />
  <!--========== Akhir Area Our Technologies ==========-->

  <!--========== INSERT TESTIMONI.PHP ==========-->
  <?php include __DIR__ . '/includes/testimoni.php'; ?>
  <!--========== AKHIR INSERT TESTIMONI.PHP ==========-->

  <!--================ AREA FOOTER =================-->
  <?php include __DIR__ . '/includes/footer.php'; ?>
  <!--================ AKHIR AREA FOOTER =================-->

  <!-- External JS libraries -->
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/slick.min.js"></script>
  <!-- Custom JS -->
  <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
</body>

</html>