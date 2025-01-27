<?php
require_once __DIR__ . '/config/config.php';

// Fungsi untuk memastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Fungsi untuk mendapatkan koneksi ke database
$conn = getDbConnection();

// Fungsi untuk mendapatkan data pengguna berdasarkan session user_id
$user_id = $_SESSION['user_id'];
$user = getUserProfile($conn, $user_id);

// Memastikan data pengguna ditemukan
if (!$user) {
    die("User not found.");
}

// Fungsi untuk memeriksa apakah role pengguna adalah 'Client'
if (isset($user['role']) && $user['role'] === 'client') {
    // Jika role adalah 'Client', arahkan ke halaman lain
    header("Location: /SCI/index.php");
    exit();
}

// Menyiapkan data pengguna untuk ditampilkan
$username = $user['username'];
$profile_image = $user['profile_image'];
$created_at = date('d M Y', strtotime($user['created_at']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sarjana Canggih Indonesia</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico" />

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="./assets/css/styles.css">
</head>

<body>
    <div class="container py-5">
        <!-- Header -->
        <header class="text-center mb-5">
            <h1>Welcome to Admin Dashboard</h1>
        </header>

        <div class="row">
            <!-- Profile Section -->
            <div class="col-md-4 mb-4 text-center">
                <div class="card shadow-sm border-light rounded">
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="User Image" class="img-fluid rounded-circle mx-auto mt-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($username); ?></h3>
                        <p class="card-text"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="card-text"><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                        <p class="card-text"><strong>Joined on:</strong> <?php echo $created_at; ?></p>
                    </div>
                </div>
            </div>

            <!-- Navigation Section -->
            <div class="col-md-8">
                <div class="list-group">
                    <a href="admin_dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
                    <a href="./" class="list-group-item list-group-item-action">Home</a>
                    <a href="./blog/" class="list-group-item list-group-item-action">Blog</a>
                    <a href="./products/manage_products.php" class="list-group-item list-group-item-action">Manage Products</a>
                    <a href="projects.php" class="list-group-item list-group-item-action">Manage Projects</a>
                    <a href="users.php" class="list-group-item list-group-item-action">Manage Users</a>
                    <a href="services.php" class="list-group-item list-group-item-action">Manage Services</a>
                    <a href="reports.php" class="list-group-item list-group-item-action">Reports</a>
                    <a href="auth/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>

        <!-- Overview Section -->
        <div class="mt-5">
            <h2>Service Overview</h2>
            <div class="row">
                <!-- PPT Design Stats -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">PPT Design Projects</h5>
                            <p class="card-text">Completed: 10</p>
                            <p class="card-text">Ongoing: 5</p>
                            <p class="card-text">Pending: 3</p>
                        </div>
                    </div>
                </div>

                <!-- Data Analysis Stats -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Data Analysis Projects</h5>
                            <p class="card-text">Completed: 8</p>
                            <p class="card-text">Ongoing: 4</p>
                            <p class="card-text">Pending: 2</p>
                        </div>
                    </div>
                </div>

                <!-- Web Development Stats -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Web Development Projects</h5>
                            <p class="card-text">Completed: 12</p>
                            <p class="card-text">Ongoing: 6</p>
                            <p class="card-text">Pending: 4</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Activities Section -->
        <div class="mt-5">
            <h2>Latest Activities</h2>
            <div class="row">
                <!-- Recent Projects -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Recent Projects</h5>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <i class="fa fa-briefcase"></i> Project #001 - <strong>Completed</strong>
                                    <small class="text-muted">1 day ago</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fa fa-briefcase"></i> Project #002 - <strong>Ongoing</strong>
                                    <small class="text-muted">2 days ago</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fa fa-briefcase"></i> Project #003 - <strong>Pending</strong>
                                    <small class="text-muted">3 days ago</small>
                                </li>
                            </ul>
                            <a href="projects.php" class="btn btn-primary mt-3">View All Projects</a>
                        </div>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Recent Users</h5>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <i class="fa fa-user"></i> John Doe - <strong>Active</strong>
                                    <small class="text-muted">2 hours ago</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fa fa-user"></i> Jane Smith - <strong>Active</strong>
                                    <small class="text-muted">5 hours ago</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fa fa-user"></i> Bob Johnson - <strong>Inactive</strong>
                                    <small class="text-muted">1 day ago</small>
                                </li>
                            </ul>
                            <a href="users.php" class="btn btn-primary mt-3">View All Users</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

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
</body>

</html>