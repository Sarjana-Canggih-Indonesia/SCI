// === JS UNTUK HALAMAN MANAGE PRODUCTS === //

// ==================== JS untuk Fuzzy Search ==================== //
document.getElementById("searchInput").addEventListener("input", function () {
  // Ambil nilai input pengguna dan ubah menjadi lowercase untuk pencocokan case-insensitive
  var searchValue = this.value.toLowerCase();

  // Validasi input untuk mencegah karakter yang berpotensi berbahaya (misalnya skrip)
  // Ini memastikan input hanya berisi huruf dan angka
  var sanitizedSearchValue = searchValue.replace(/[^a-z0-9\s]/gi, "");

  // Select all rows in the table body (tbody)
  var tableRows = document.querySelectorAll("tbody tr");

  // Convert the table rows into an array of product objects, each containing data from the columns
  var products = Array.from(tableRows).map(function (row) {
    return {
      id: row.cells[0].textContent, // Get data from the first column (ID)
      name: row.cells[1].textContent, // Get data from the second column (Name)
      category: row.cells[2].textContent, // Get data from the third column (Category)
      tags: row.cells[3].textContent, // Get data from the fourth column (Tags)
      price: row.cells[4].textContent, // Get data from the fifth column (Price)
      stock: row.cells[5].textContent, // Get data from the sixth column (Stock)
      row: row, // Keep a reference to the row element for later manipulation
    };
  });

  // Jika input pencarian kosong, tampilkan semua baris
  if (sanitizedSearchValue === "") {
    tableRows.forEach(function (row) {
      row.style.display = ""; // Tampilkan semua baris
    });
    return; // Keluar dari fungsi untuk mencegah pencarian Fuse.js saat input kosong
  }

  // Initialize Fuse.js dengan data produk dan pengaturan pencarian
  var fuse = new Fuse(products, {
    keys: ["name", "category", "tags"], // Tentukan kolom mana yang akan dipertimbangkan untuk pencarian (name, category, tags)
    includeScore: true, // Sertakan skor pencocokan (skor lebih rendah berarti kecocokan lebih baik)
    threshold: 0.3, // Tentukan ambang pencocokan (nilai lebih rendah berarti pencocokan lebih ketat)
  });

  // Lakukan pencarian berdasarkan nilai input pengguna
  var result = fuse.search(sanitizedSearchValue);

  // Iterasi melalui setiap baris tabel untuk memeriksa apakah itu cocok dengan hasil pencarian
  tableRows.forEach(function (row) {
    var product = products.find((p) => p.row === row); // Temukan produk terkait dengan baris
    // Tampilkan atau sembunyikan baris berdasarkan apakah itu cocok dengan hasil pencarian
    if (result.some((res) => res.item.row === row)) {
      row.style.display = ""; // Tampilkan baris jika ada kecocokan
    } else {
      row.style.display = "none"; // Sembunyikan baris jika tidak ada kecocokan
    }
  });
});
// ==================== Akhir JS untuk Fuzzy Search ==================== //

// ==================== JS untuk Modal Delete ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Fungsi untuk menampilkan modal konfirmasi saat klik tombol Delete
  document.querySelectorAll(".btn-danger").forEach(function (deleteButton) {
    deleteButton.addEventListener("click", function () {
      // Menampilkan modal konfirmasi
      var modal = new bootstrap.Modal(document.getElementById("deleteModal"));
      modal.show();

      // Tambahkan event listener untuk tombol Delete pada modal
      var confirmDeleteButton = document.querySelector("#deleteModal .btn-danger");
      confirmDeleteButton.addEventListener("click", function () {
        // Implementasi penghapusan produk (misalnya dengan AJAX atau refresh halaman)
        alert("Produk dihapus!"); // Ganti dengan aksi penghapusan yang sesuai
        modal.hide();
      });
    });
  });
});
// ==================== Akhir JS untuk Modal Delete ==================== //

// === AKHIR JS UNTUK HALAMAN MANAGE PRODUCTS === //
