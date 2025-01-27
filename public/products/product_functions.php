<?php
$productConn = getProductDbConnection();
$conn = getDbConnection();

/**
 * Memeriksa apakah pengguna sudah login dan memiliki peran admin.
 *
 * @param PDO $conn Koneksi database
 */
function checkAdminSession($conn)
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /SCI/auth/login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $user = getUserProfile($conn, $user_id);

    if (!$user || (isset($user['role']) && $user['role'] !== 'admin')) {
        header("Location: /SCI/index.php");
        exit();
    }
}

/**
 * Menyaring dan membersihkan input untuk menghindari XSS dan injeksi HTML.
 *
 * @param string $data Data input yang akan disaring
 * @return string Data yang sudah disanitasi
 */
function sanitizeInput($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Mendapatkan produk berdasarkan ID dari database.
 *
 * @param PDO $pdo Koneksi database
 * @param int $productId ID produk yang akan diambil
 * @return array|null Data produk atau null jika tidak ditemukan
 */
function getProduct($pdo, $productId)
{
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $productId);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Mendapatkan semua produk dari database.
 *
 * @param PDO $pdo Koneksi database
 * @return array Daftar produk
 */
function getAllProducts($pdo)
{
    $query = "SELECT * FROM products";
    $stmt = $pdo->query($query);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Menambahkan produk baru ke dalam database.
 *
 * @param PDO $pdo Koneksi database
 * @param string $productName Nama produk
 * @param string $shortDescription Deskripsi singkat produk
 * @param string $description Deskripsi panjang produk
 * @param float $price Harga produk
 * @param string $imagePath Lokasi gambar produk
 * @param int $categoryId ID kategori produk
 * @param string $status Status produk (misalnya: aktif, tidak aktif)
 * @param string $tags Tag produk
 * @return bool Status eksekusi query
 */
function addProduct($pdo, $productName, $shortDescription, $description, $price, $imagePath, $categoryId, $status, $tags)
{
    $productName = sanitizeInput($productName);
    $shortDescription = sanitizeInput($shortDescription);
    $description = sanitizeInput($description);
    $tags = sanitizeInput($tags);

    $query = "INSERT INTO products (name, short_description, description, price, category_id, status, tags) 
              VALUES (:name, :short_description, :description, :price, :category_id, :status, :tags)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':name', $productName);
    $stmt->bindParam(':short_description', $shortDescription);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':category_id', $categoryId);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':tags', $tags);

    if ($stmt->execute()) {
        $productId = $pdo->lastInsertId();
        if ($imagePath) {
            return addProductImage($pdo, $productId, $imagePath);
        }
        return true;
    }
    return false;
}

/**
 * Menambahkan gambar produk ke dalam database.
 *
 * @param PDO $pdo Koneksi database
 * @param int $productId ID produk yang akan ditambahkan gambarnya
 * @param string $imagePath Lokasi gambar produk
 * @return bool Status eksekusi query
 */
function addProductImage($pdo, $productId, $imagePath)
{
    $query = "INSERT INTO product_images (product_id, image_url) VALUES (:product_id, :image_url)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->bindParam(':image_url', $imagePath);

    return $stmt->execute();
}

/**
 * Mengedit produk yang sudah ada dalam database.
 *
 * @param PDO $pdo Koneksi database
 * @param int $productId ID produk yang akan diedit
 * @param string $productName Nama produk
 * @param string $shortDescription Deskripsi singkat produk
 * @param string $description Deskripsi panjang produk
 * @param float $price Harga produk
 * @param string $imagePath Lokasi gambar produk
 * @param int $categoryId ID kategori produk
 * @param string $status Status produk
 * @param string $tags Tag produk
 * @return bool Status eksekusi query
 */
function editProduct($pdo, $productId, $productName, $shortDescription, $description, $price, $imagePath, $categoryId, $status, $tags)
{
    $productName = sanitizeInput($productName);
    $shortDescription = sanitizeInput($shortDescription);
    $description = sanitizeInput($description);
    $tags = sanitizeInput($tags);

    $query = "UPDATE products SET name = :name, short_description = :short_description, 
              description = :description, price = :price, category_id = :category_id, 
              status = :status, tags = :tags WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $productId);
    $stmt->bindParam(':name', $productName);
    $stmt->bindParam(':short_description', $shortDescription);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':category_id', $categoryId);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':tags', $tags);

    if ($stmt->execute()) {
        if ($imagePath) {
            return addProductImage($pdo, $productId, $imagePath);
        }
        return true;
    }
    return false;
}

/**
 * Menghapus produk dari database berdasarkan ID.
 *
 * @param PDO $pdo Koneksi database
 * @param int $productId ID produk yang akan dihapus
 * @return bool Status eksekusi query
 */
function deleteProduct($pdo, $productId)
{
    $query = "DELETE FROM product_images WHERE product_id = :product_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();

    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $productId);

    return $stmt->execute();
}

/**
 * Mendapatkan semua kategori dari database.
 *
 * @param PDO $pdo Koneksi database
 * @return array Daftar kategori
 */
function getAllCategories($pdo)
{
    $query = "SELECT * FROM categories";
    $stmt = $pdo->query($query);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Mengupload gambar produk dengan pemeriksaan dan penyimpanan yang aman.
 *
 * @param array $file Data file gambar yang diupload
 * @return string|false Lokasi gambar yang aman atau false jika gagal
 */
function uploadProductImage($file)
{
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    if (getimagesize($file["tmp_name"]) === false || $file["size"] > 5000000) {
        return false;
    }

    if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
        return false;
    }

    $safeFileName = md5(uniqid(rand(), true)) . '.' . $imageFileType;
    $safeTargetFile = $targetDir . $safeFileName;

    if (move_uploaded_file($file["tmp_name"], $safeTargetFile)) {
        return $safeTargetFile;
    }

    return false;
}
