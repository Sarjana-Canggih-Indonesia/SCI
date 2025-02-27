<?php
// contact/index.php

// Sertakan file konfigurasi
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

startSession();

// Memuat konfigurasi URL Dinamis
$config = getEnvironmentConfig(); // Load environment configuration
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']);
$isLive = $config['is_live'];

validateReCaptchaEnvVariables(); // Validate reCAPTCHA environment variables

setCacheHeaders($isLive); // Set header no cache saat local environment
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hubungi Kami - Sarjana Canggih Indonesia</title>
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
    <!-- Google reCAPTCHA -->
    <script type="text/javascript" src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- Custom Inline CSS -->
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }
    </style>
</head>

<body class="login-page">
    <section class="h-100 d-flex justify-content-center align-items-center">
        <div class="card-wrapper">
            <div class="brand">
                <a href="<?php echo $baseUrl; ?>">
                    <img src="<?php echo $baseUrl; ?>assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia"
                        srcset=""></a>
            </div>
            <!-- Area Konten Form Kontak -->
            <div class="card fat">
                <div class="card-body">
                    <h4 class="card-title">Form Kontak</h4>
                    <form action="<?php echo rtrim($baseUrl, '/'); ?>/process_contact" method="POST" target="_blank">
                        <!-- Nama (Alias atau Nama) -->
                        <div class="mb-3">
                            <label for="form-wa-nama" class="form-label">Nama</label>
                            <input type="text" class="form-control" id="form-wa-nama" name="form-wa-nama"
                                placeholder="Masukkan nama atau alias" required />
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="form-wa-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="form-wa-email" name="form-wa-email"
                                placeholder="Masukkan email" required />
                        </div>

                        <!-- Pesan (Textbox panjang) -->
                        <div class="mb-3">
                            <label for="form-wa-pesan" class="form-label">Pesan</label>
                            <textarea class="form-control" id="form-wa-pesan" name="form-wa-pesan" rows="4"
                                placeholder="Tulis pesan Anda" required></textarea>
                        </div>

                        <!-- Honeypot Field (Hidden) -->
                        <div style="display:none;">
                            <label for="form-wa-honeypot" class="form-label">Jangan Diisi (Honeypot):</label>
                            <input type="text" class="form-control" id="form-wa-honeypot" name="form-wa-honeypot" />
                        </div>

                        <!-- reCAPTCHA -->
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>"
                                required></div>
                        </div>

                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />

                        <!-- Tombol Submit -->
                        <button type="submit" class="btn btn-primary w-100 btn-lg">Kirim</button>
                    </form>
                </div>
            </div>
            <!-- Akhir Area Konten Form Kontak -->
        </div>
    </section>

    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS scripts -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/js/custom.js"></script>
</body>

</html>