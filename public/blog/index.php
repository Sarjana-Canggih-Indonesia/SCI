<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Saya</title>
    <!-- Link ke Bootstrap 5.3.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <header class="bg-dark text-white py-4">
        <div class="container">
            <h1 class="text-center">Blog Saya</h1>
            <nav>
                <ul class="nav justify-content-center">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">Kontak</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container my-5">
            <section class="row">
                <article class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title"><a href="#">Judul Artikel Pertama</a></h2>
                            <p class="text-muted">9 Januari 2025</p>
                            <p class="card-text">Ini adalah cuplikan dari artikel pertama saya di blog ini. Klik judul untuk membaca lebih lanjut.</p>
                            <a href="#" class="btn btn-primary">Baca Selengkapnya</a>
                        </div>
                    </div>
                </article>

                <article class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title"><a href="#">Judul Artikel Kedua</a></h2>
                            <p class="text-muted">8 Januari 2025</p>
                            <p class="card-text">Cuplikan dari artikel kedua saya. Klik judul untuk melanjutkan membaca.</p>
                            <a href="#" class="btn btn-primary">Baca Selengkapnya</a>
                        </div>
                    </div>
                </article>

                <!-- Tambahkan artikel lainnya di sini -->
            </section>
        </div>
    </main>

    <footer class="bg-dark text-white py-3">
        <div class="container text-center">
            <p>&copy; 2025 Blog Saya. Semua hak cipta dilindungi.</p>
        </div>
    </footer>

    <!-- Script Bootstrap 5.3.3 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>