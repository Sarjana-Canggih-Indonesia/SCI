<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sarjana Canggih</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/SCI/favicon.ico" />

    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

    <!-- Custom Styles CSS -->
    <link rel="stylesheet" type="text/css" href="../assets/css/styles.css">

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
                    <div class="d-flex text-start mb-2">
                        <a href="./login.php" class="btn btn-outline-primary"
                            onclick="return confirm('Are you sure you want to go back?');">
                            <i class="fa fa-arrow-left"></i> Back to Login</a>
                    </div>
                    <h4 class="text-start">Lupa Password</h4>
                    <form action="forgot_password.php" method="POST">
                        <div class="form-group">
                            <div class="mb-3">
                                <label for="email_or_username" class="mb-3 text-start d-block">E-mail Address or
                                    Username</label>
                                <input type="text" name="email_or_username" id="email_or_username"
                                    class="form-control mb-3" required autofocus>
                                <div class="invalid-feedback">
                                    Email or Username is not valid
                                </div>
                                <div class="form-text text-muted text-start">
                                    By clicking "Reset Password", we will send an email to reset your password.
                                </div>
                            </div>
                        </div>
                        <div class="g-recaptcha mb-3" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal for success message -->
    <?php if ($showModal): ?>
        <div class="modal fade" id="successModal-forgotpassword" tabindex="-1"
            aria-labelledby="successModal-forgotpasswordLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="successModal-forgotpasswordLabel">Password Reset Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Check your email for the password reset link.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- reCAPTCHA Script -->
    <script type="text/javascript" src="https://www.google.com/recaptcha/api.js" defer></script>
    <!-- jQuery 3.7.1 (necessary for Bootstrap's JavaScript plugins) -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.slim.min.js"></script>
    <!-- POPPER 2.11.8 -->
    <script type="text/javascript" src="https://unpkg.com/@popperjs/core@2"></script>
    <!--Bootstrap 5.3.3 bundle min js-->
    <script type="text/javascript"
        src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script type="text/javascript" src="/SCI/assets/js/custom.js"></script>
</body>

</html>