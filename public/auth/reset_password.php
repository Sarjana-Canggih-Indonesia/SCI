<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Sarjana Canggih</title>

    <!-- Favicon -->
    <link rel="icon" href="../assets/images/logoscblue.png" type="image/x-icon">
    <!-- Bootstrap css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Slick Slider css -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <!-- Custom Styles CSS -->
    <link rel="stylesheet" type="text/css" href="../assets/css/styles.css">
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700&display=swap"
        rel="stylesheet">
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
        <div class="card-wrapper text-center">
            <div class="brand">
                <a href="../../SCI/">
                    <img src="../assets/images/logoscblue.png" alt="Logo Sarjana Canggih Indonesia">
                </a>
            </div>

            <div class="card fat">
                <div class="card-body">
                    <h4 class="text-start">Reset Password</h4>

                    <!-- Display the message if available -->
                    <?php if (isset($_GET['message'])): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($_GET['message']); ?></div>
                    <?php endif; ?>

                    <form action="../../config/auth/reset_password_process.php" method="POST">
                        <div class="form-group">
                            <input type="hidden" name="token"
                                value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>" />
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                            <label for="password" class="mb-3 text-start d-block">New Password</label>
                            <div style="position:relative" id="eye-password-0">
                                <input id="new-password" type="password" name="password" class="form-control" required
                                    autofocus style="padding-right: 60px;" />
                                <div class="invalid-feedback">Password is required</div>
                                <div class="btn btn-sm" id="passeye-toggle-0"
                                    style="position: absolute; right: 10px; top: 7px; padding: 2px 7px; font-size: 16px; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>
                        <div class="form-text text-muted text-start mb-3">
                            Pastikan menggunakan password yang kuat dan mudah untuk diingat
                        </div>
                        <div class="g-recaptcha mb-3" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        <!-- Ganti dengan kunci situs reCAPTCHA Anda -->
                        <button type="submit" class="btn btn-primary btn-lg w-100">Reset Password</button>
                    </form>
                </div>
            </div>
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