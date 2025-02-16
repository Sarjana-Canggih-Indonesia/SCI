// === JS UNTUK HALAMAN MANAGE PRODUCTS === //

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

// ==================== JS untuk Filter Category ==================== //
document.addEventListener("DOMContentLoaded", function () {
  const categoryFilter = document.getElementById("categoryFilter");

  categoryFilter.addEventListener("change", function () {
    const categoryId = this.value === "" ? null : this.value;

    // Ubah semua fetch menjadi:
    let url = `${BASE_URL}api-proxy.php`;
    if (categoryId !== null) {
      url += `?category_id=${categoryId}`;
    }

    fetch(url, {
      credentials: "include",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          updateTable(data.products);
        } else {
          console.error("Server Error:", data.message);
          alert("Gagal memuat data: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Fetch Error:", error);
        alert("Terjadi kesalahan jaringan");
      });
  });

  function updateTable(products) {
    const tbody = document.getElementById("productsTableBody");
    tbody.innerHTML = ""; // Clear existing rows

    products.forEach((product) => {
      const row = document.createElement("tr");
      row.innerHTML = `
              <td>${escapeHtml(product.product_id)}</td>
              <td>${escapeHtml(product.product_name)}</td>
              <td>${escapeHtml(product.categories || "Uncategorized")}</td>
              <td>Rp ${formatPrice(product.price_amount)}</td>
              <td>
                  <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</button>
                  <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
              </td>
          `;
      tbody.appendChild(row);
    });
  }

  function escapeHtml(unsafe) {
    return unsafe
      ? unsafe
          .toString()
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;")
      : "";
  }

  function formatPrice(amount) {
    return (
      Number(amount).toLocaleString("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }) + ",00"
    );
  }
});

// ==================== Akhir JS untuk Filter Category ==================== //

// ==================== JS untuk Tagify ==================== //
document.addEventListener("DOMContentLoaded", function () {
  let tagify = null;

  $("#addProductModal").on("shown.bs.modal", function () {
    const input = document.getElementById("productTags");

    if (tagify) tagify.destroy();

    tagify = new Tagify(input, {
      whitelist: TAGS_WHITELIST,
      dropdown: {
        enabled: 1,
        maxItems: 50,
        closeOnSelect: false,
        highlightFirst: true,
        searchKeys: ["value"],
        position: "all",
        classname: "tagify-dropdown",
      },
      enforceWhitelist: false,
      editTags: true,
      duplicates: false,
      placeholder: "Enter tags",
      maxTags: 10,
      pattern: /^[a-zA-Z0-9\s\-_]+$/,
    });

    input.addEventListener("click", function () {
      tagify.dropdown.show();
    });

    // Event listeners untuk validasi
    tagify.on("add", function (e) {
      const tagValue = e.detail.data.value;
      if (!/^[a-zA-Z0-9\s\-_]+$/.test(tagValue)) {
        alert(`Invalid tag: ${tagValue}`);
        tagify.removeTag(e.detail.tag);
      }
      if (tagify.value.length > 10) {
        alert("Max 10 tags allowed");
        tagify.removeTag(e.detail.tag);
      }
    });
  });
});
// ==================== Akhir JS untuk Tagify ==================== //

// === AKHIR JS UNTUK HALAMAN MANAGE PRODUCTS === //
