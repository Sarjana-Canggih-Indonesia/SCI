<?php
// Include the configuration file
require_once __DIR__ . '/../../config/user_actions_config.php';

$config = getEnvironmentConfig(); // Load environment configuration
$baseUrl = getBaseUrl($config, $_ENV['LIVE_URL']); // Get the base URL from the configuration
$isLive = $config['is_live'];
// Deteksi environment
$isLiveEnvironment = ($config['BASE_URL'] === $_ENV['LIVE_URL']);
setCacheHeaders($isLive); // Set header no cache saat local environment

// Call the logout function
$logoutMessage = logoutUser();

// Set success or failure message based on logout outcome
$isLogoutSuccessful = $logoutMessage === 'Logged out successfully.';

// Determine the modal message
$modalTitle = $isLogoutSuccessful ? 'Logged Out' : 'Logout Failed';
$modalMessage = $isLogoutSuccessful ? htmlspecialchars($logoutMessage) : 'An error occurred while logging out. Please try again later.';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>favicon.ico" />
    <!-- Bootstrap css -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/vendor/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <!-- Custom Styles CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>assets/css/styles.css">
    <!-- Custom styles for auth pages -->
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }
    </style>
</head>

<body class="login-page">
    <!-- Success Modal -->
    <div class="modal fade" id="logoutModalSuccess" tabindex="-1" aria-labelledby="logoutModalLabelSuccess"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabelSuccess"><?php echo $modalTitle; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo $modalMessage; ?>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo $baseUrl; ?>login" type="button" class="btn btn-secondary">
                        <i class="fa fa-user"></i> Login
                    </a>
                    <?php if ($isLogoutSuccessful): ?>
                        <a href="<?php echo $baseUrl; ?>" class="btn btn-primary">
                            <i class="fa fa-home"></i> Homepage
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Try Again</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- External JS libraries -->
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/jquery-slim.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo $baseUrl; ?>assets/vendor/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show the modal once the page has loaded
        window.onload = function () {
            var modalId = '<?php echo $isLogoutSuccessful ? "logoutModalSuccess" : "logoutModalFailed"; ?>';
            var myModal = new bootstrap.Modal(document.getElementById(modalId));
            myModal.show();
        }
    </script>
</body>

</html>