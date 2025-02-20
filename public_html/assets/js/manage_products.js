// === JS UNTUK HALAMAN MANAGE PRODUCTS === //

// ==================== JS untuk Modal Delete ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Fungsi untuk menampilkan modal konfirmasi saat klik tombol Delete
  document.querySelectorAll(".btn-danger").forEach(function (deleteButton) {
    deleteButton.addEventListener("click", function () {
      // Show the delete confirmation modal
      var modal = new bootstrap.Modal(document.getElementById("deleteModal"));
      modal.show();

      // Select the confirmation delete button inside the modal
      var confirmDeleteButton = document.querySelector("#deleteModal .btn-danger");
      confirmDeleteButton.addEventListener("click", function () {
        // Simulate product deletion (should be replaced with actual deletion logic)
        alert("Product deleted!");
        modal.hide();
      });
    });
  });
});
// ==================== Akhir JS untuk Modal Delete ==================== //

// ==================== JS untuk Filter Category ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Get the category filter dropdown element
  const categoryFilter = document.getElementById("categoryFilter");

  // Add an event listener for when the category selection changes
  categoryFilter.addEventListener("change", function () {
    // Get the selected category ID, or set to null if empty
    const categoryId = this.value === "" ? null : this.value;

    // Construct the API URL to fetch products by category
    let url = `${BASE_URL}api-proxy.php?action=get_products_by_category`;
    if (categoryId !== null) {
      url += `&category_id=${categoryId}`;
    }

    // Fetch products based on the selected category
    fetch(url, {
      credentials: "include", // Ensures cookies and credentials are included
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json(); // Parse JSON response
      })
      .then((data) => {
        if (data.success) {
          updateTable(data.products); // Update the product table with new data
        } else {
          console.error("Server Error:", data.message);
          alert("Failed to load data: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Fetch Error:", error);
        alert(`Terjadi kesalahan jaringan: ${error.message}`);
      });
  });

  // Function to update the product table dynamically
  function updateTable(products) {
    const tbody = document.getElementById("productsTableBody");
    tbody.innerHTML = ""; // Clear existing table content

    // Loop through products and create table rows dynamically
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
      tbody.appendChild(row); // Append row to table body
    });
  }

  // Function to escape HTML to prevent XSS attacks
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

  // Function to format price as Indonesian Rupiah
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

// ==================== JS untuk Search Bar ==================== //
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const searchButton = document.querySelector("button.btn-primary");
  let debounceTimer;

  // Load all products when the page is loaded
  loadAllProducts();

  // Event listener for search button click
  searchButton.addEventListener("click", function () {
    const keyword = searchInput.value.trim();
    if (keyword) {
      searchProducts(keyword);
    }
  });

  // Event listener for input field with debounce to limit API requests
  searchInput.addEventListener("input", function () {
    const keyword = searchInput.value.trim();

    if (keyword) {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        searchProducts(keyword);
      }, 300); // Delay search execution by 300ms
    } else {
      loadAllProducts();
    }
  });

  // Function to search for products based on user input
  function searchProducts(keyword) {
    let url = `${BASE_URL}api-proxy.php?action=get_search_products&keyword=${encodeURIComponent(keyword)}`;

    fetch(url, {
      credentials: "include", // Include cookies in the request
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
          alert("Failed to load data: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Fetch Error:", error);
        alert(`Terjadi kesalahan jaringan: ${error.message}`);
      });
  }

  // Function to load all products when no search keyword is provided
  function loadAllProducts() {
    let url = `${BASE_URL}api-proxy.php?action=get_all_products`;

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
          alert("Failed to load data: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Fetch Error:", error);
        alert(`Terjadi kesalahan jaringan: ${error.message}`);
      });
  }

  // Function to update the table dynamically with product data
  function updateTable(products) {
    const tbody = document.getElementById("productsTableBody");
    tbody.innerHTML = ""; // Clear existing table content

    // Loop through products and create table rows dynamically
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

  // Function to escape HTML to prevent XSS attacks
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

  // Function to format price as Indonesian Rupiah
  function formatPrice(amount) {
    return (
      Number(amount).toLocaleString("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }) + ",00"
    );
  }
});
// ==================== Akhir JS untuk Search Bar ==================== //

// ==================== JS untuk Tagify ==================== //
document.addEventListener("DOMContentLoaded", function () {
  let tagify = null;

  // Event listener when the add product modal is shown
  $("#addProductModal").on("shown.bs.modal", function () {
    const input = document.getElementById("productTags");

    // Destroy existing Tagify instance if it exists
    if (tagify) tagify.destroy();

    // Initialize Tagify for the product tags input field
    tagify = new Tagify(input, {
      whitelist: TAGS_WHITELIST, // Predefined list of allowed tags
      dropdown: {
        enabled: 1, // Show dropdown on first character input
        maxItems: 50, // Maximum items displayed in dropdown
        closeOnSelect: false, // Keep dropdown open after selection
        highlightFirst: true, // Highlight first suggestion
        searchKeys: ["value"], // Search tags by value
        position: "all",
        classname: "tagify-dropdown",
      },
      enforceWhitelist: false, // Allow tags outside the whitelist
      editTags: true, // Enable editing tags after adding
      duplicates: false, // Prevent duplicate tags
      placeholder: "Enter tags", // Placeholder text
      maxTags: 10, // Limit number of tags
      pattern: /^[a-zA-Z0-9\s\-_]+$/, // Allow only alphanumeric, spaces, dashes, and underscores
    });

    // Show dropdown when input is clicked
    input.addEventListener("click", function () {
      tagify.dropdown.show();
    });

    // Event listener for when a new tag is added
    tagify.on("add", function (e) {
      const tagValue = e.detail.data.value;

      // Validate tag format
      if (!/^[a-zA-Z0-9\s\-_]+$/.test(tagValue)) {
        alert(`Invalid tag: ${tagValue}`);
        tagify.removeTag(e.detail.tag);
      }

      // Ensure the tag limit is not exceeded
      if (tagify.value.length > 10) {
        alert("Max 10 tags allowed");
        tagify.removeTag(e.detail.tag);
      }
    });
  });
});
// ==================== Akhir JS untuk Tagify ==================== //

// === AKHIR JS UNTUK HALAMAN MANAGE PRODUCTS === //
