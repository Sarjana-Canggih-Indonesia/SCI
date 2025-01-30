<?php
// Sertakan file konfigurasi
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/user_actions_config.php';

startSession();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hubungi Kami - Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <!-- Bootstrap css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous" />
    <!-- Slick Slider css -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="../assets/css/styles.css" />
    <!-- Memuat script reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- Membuat konten rata tengah -->
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
                <a href="../">
                    <img src="../assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia" srcset=""></a>
            </div>
            <!-- Area Konten Form Kontak -->
            <div class="card fat">
                <div class="card-body">
                    <h4 class="card-title">Form Kontak</h4>
                    <form action="process_contact.php" method="POST">
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
                            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
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


</body>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script type="text/javascript" src="../assets/js/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="../assets/js/popper.min.js"></script>
<!--Bootstrap bundle min js-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>
<!-- Slick Slider JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
<!-- Custom JS -->
<script type="text/javascript" src="../assets/js/custom.js"></script>

</html>