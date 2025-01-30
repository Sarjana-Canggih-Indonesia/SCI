// ====================Scroll to top==================== //
document.addEventListener("DOMContentLoaded", function () {
  const scrollToTopBtn = document.getElementById("scrollToTopBtn");

  // Cek apakah elemen scrollToTopBtn ada
  if (scrollToTopBtn) {
    // Menampilkan atau menyembunyikan tombol scroll to top
    window.addEventListener("scroll", function () {
      if (window.scrollY > 300) {
        // Menampilkan tombol jika menggulir lebih dari 300px
        scrollToTopBtn.style.display = "block";
      } else {
        scrollToTopBtn.style.display = "none";
      }
    });

    // Menambahkan efek scroll ke atas saat tombol diklik
    scrollToTopBtn.addEventListener("click", function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  } else {
    console.log("Elemen scrollToTopBtn tidak ditemukan, kode JavaScript dinonaktifkan.");
  }
});
// ====================Akhir Scroll to top==================== //

// ====================JS untuk Navigasi Bar berubah warna==================== //
$(function () {
  const navbar = $(".navbar");

  if (navbar.length) {
    $(window).on("scroll", function () {
      if ($(window).scrollTop() > 10) {
        navbar.addClass("active");
      } else {
        navbar.removeClass("active");
      }
    });
  } else {
    console.log("Elemen navbar tidak ditemukan, kode JavaScript dinonaktifkan.");
  }
});
// ====================Akhir JS untuk Navigasi Bar berubah warna==================== //

// ====================JS untuk ourteknologi==================== //
$(function () {
  const techLogos = $(".tech-logos");

  if (techLogos.length) {
    techLogos.slick({
      slidesToShow: 6,
      slidesToScroll: 1,
      autoplay: true,
      autoplaySpeed: 1500,
      arrows: false,
      dots: false,
      pauseOnHover: false,
      responsive: [
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 4,
          },
        },
        {
          breakpoint: 578,
          settings: {
            slidesToShow: 2,
          },
        },
      ],
    });
  } else {
    console.log("Elemen .tech-logos tidak ditemukan, Slick carousel dinonaktifkan.");
  }
});
// ====================Akhir JS untuk ourteknologi==================== //

// ==================== JS UNTUK FILTER PRODUK ==================== //
const productCards = document.querySelectorAll(".product-card");
const applyFiltersButton = document.getElementById("apply-filters");
const productsContainer = document.getElementById("products-container");

// Fungsi untuk menerapkan filter kategori
function applyCategoryFilter() {
  // Ambil semua kategori yang dipilih dari checkbox
  const selectedCategories = Array.from(document.querySelectorAll(".product-filter:checked")).map(
    (input) => input.value,
  );

  let productsFound = false;

  // Iterasi setiap produk dan sesuaikan visibilitasnya berdasarkan kategori yang dipilih
  productCards.forEach((card) => {
    const productCategory = card.getAttribute("data-category"); // Ambil data kategori dari atribut

    // Sesuaikan visibilitas produk
    const shouldDisplay = selectedCategories.length === 0 || selectedCategories.includes(productCategory);
    card.style.display = shouldDisplay ? "block" : "none";
    if (shouldDisplay) {
      productsFound = true;
    }
  });

  // Tangani tampilan pesan "Produk tidak ditemukan"
  let noProductsMessage = document.getElementById("no-products-message");

  if (!noProductsMessage) {
    // Jika elemen pesan belum ada, tambahkan
    noProductsMessage = document.createElement("p");
    noProductsMessage.id = "no-products-message";
    noProductsMessage.textContent = "Produk tidak ditemukan.";
    noProductsMessage.style.display = "none"; // Sembunyikan secara default
    productsContainer.appendChild(noProductsMessage);
  }

  // Tampilkan atau sembunyikan pesan berdasarkan hasil filter
  noProductsMessage.style.display = productsFound ? "none" : "block";
}

// Cek apakah tombol apply-filters ada
if (applyFiltersButton) {
  // Menambahkan event listener pada tombol apply filter
  applyFiltersButton.addEventListener("click", applyCategoryFilter);
} else {
  console.log("Tombol apply-filters tidak ditemukan, pengaplikasian filter dibatalkan.");
}
// ==================== AKHIR JS UNTUK FILTER PRODUK ====================//

// ====================JS untuk successModal-forgotpassword==================== //
document.addEventListener("DOMContentLoaded", function () {
  var successModalElement = document.getElementById("successModal-forgotpassword");

  if (successModalElement) {
    var myModal = new bootstrap.Modal(successModalElement);
    myModal.show();
  } else {
    console.log("Script untuk successModal-forgotpassword dinonaktifkan karena elemen tidak ditemukan.");
  }
});
// ====================Akhir JS untuk successModal-forgotpassword==================== //
