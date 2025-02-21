// === JS UNTUK HALAMAN MANAGE PRODUCTS === //

// ==================== Global Helper Functions ==================== //
function updateDeleteButtonVisibility() {
  const checkboxes = document.querySelectorAll(".product-checkbox");
  const deleteSelectedBtn = document.getElementById("deleteSelectedBtn");
  const anyChecked = Array.from(checkboxes).some((checkbox) => checkbox.checked);
  if (deleteSelectedBtn) {
    deleteSelectedBtn.classList.toggle("d-none", !anyChecked);
  }
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
// ==================== Akhir Global Helper Functions ==================== //

// ==================== JS untuk Checkboxes ==================== //
function attachCheckboxListeners() {
  const selectAllButton = document.getElementById("manage_products-selectAllButton");
  const checkboxes = document.querySelectorAll(".product-checkbox");

  // Select All functionality
  if (selectAllButton) {
    selectAllButton.addEventListener("click", function () {
      const isAnyUnchecked = [...checkboxes].some((cb) => !cb.checked);

      checkboxes.forEach((checkbox) => {
        checkbox.checked = isAnyUnchecked;
      });

      if (isAnyUnchecked) {
        selectAllButton.innerHTML = '<i class="fas fa-check-circle"></i> Deselect All';
      } else {
        selectAllButton.innerHTML = '<i class="fas fa-check-circle"></i> Select All';
      }

      updateDeleteButtonVisibility();
    });
  }

  // Update buttons when individual checkboxes are changed
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      const allChecked = [...checkboxes].every((cb) => cb.checked);

      if (allChecked) {
        selectAllButton.innerHTML = '<i class="fas fa-check-circle"></i> Deselect All';
      } else {
        selectAllButton.innerHTML = '<i class="fas fa-check-circle"></i> Select All';
      }

      updateDeleteButtonVisibility();
    });
  });

  // Initial update
  updateDeleteButtonVisibility();
}
// ==================== Akhir JS untuk Checkboxes ==================== //

// ==================== JS untuk Delete Selected ==================== //
document.addEventListener("DOMContentLoaded", function () {
  const confirmDeleteSelected = document.getElementById("confirmDeleteSelected");

  // Ensure the delete button exists
  if (!confirmDeleteSelected) {
    return;
  }

  confirmDeleteSelected.addEventListener("click", function () {
    // Collect selected product IDs
    const selectedProducts = Array.from(document.querySelectorAll(".product-checkbox:checked")).map(
      (checkbox) => checkbox.value,
    );

    // Validate if at least one product is selected
    if (selectedProducts.length === 0) {
      alert("Please select at least one product!");
      return;
    }

    // Retrieve CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");

    // Validate CSRF token existence
    if (!csrfToken) {
      alert("An internal error occurred. Please reload the page.");
      return;
    }

    const url = `${BASE_URL}api-proxy.php?action=delete_selected_products`;

    // Send DELETE request to the API
    fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken,
      },
      body: JSON.stringify({ product_ids: selectedProducts }),
      credentials: "include",
    })
      .then(handleResponse)
      .then((data) => {
        // Handle successful and failed deletions
        if (!data.error) {
          alert("Selected products have been successfully deleted!");
          window.location.reload();
        } else {
          alert("Failed to delete some products.");
        }
      })
      .catch((error) => {
        alert("An error occurred while deleting products.");
      });
  });

  // Handle API response
  function handleResponse(response) {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  }
});
// ==================== Akhir JS untuk Delete Selected ==================== //

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

  // Function to update the table dynamically with product data
  function updateTable(products) {
    const tbody = document.getElementById("productsTableBody");
    tbody.innerHTML = "";

    products.forEach((product, index) => {
      const row = document.createElement("tr");
      row.innerHTML = `
      <td>
        <input type="checkbox" name="selected_products[]" 
               value="${escapeHtml(product.product_id)}" 
               class="product-checkbox">
        ${index + 1}
      </td>
      <td>${escapeHtml(product.product_name)}</td>
      <td>${escapeHtml(product.categories || "Uncategorized")}</td>
      <td>Rp ${formatPrice(product.price_amount)}</td>
      <td>
        <!-- Tombol View Details -->
        <button class="btn btn-info btn-sm" onclick="viewDetails(${escapeHtml(product.product_id)})">
          <i class="fas fa-eye"></i> View Details
        </button>
        <!-- Tombol Edit -->
        <button class="btn btn-warning btn-sm" onclick="editProduct(${escapeHtml(product.product_id)})">
          <i class="fas fa-edit"></i> Edit
        </button>
      </td>
    `;
      tbody.appendChild(row);
    });

    // Re-attach listeners and update UI
    attachCheckboxListeners();
    updateDeleteButtonVisibility();
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
    tbody.innerHTML = "";

    products.forEach((product, index) => {
      const row = document.createElement("tr");
      row.innerHTML = `
      <td>
        <input type="checkbox" name="selected_products[]" 
               value="${escapeHtml(product.product_id)}" 
               class="product-checkbox">
        ${index + 1}
      </td>
      <td>${escapeHtml(product.product_name)}</td>
      <td>${escapeHtml(product.categories || "Uncategorized")}</td>
      <td>Rp ${formatPrice(product.price_amount)}</td>
      <td>
        <!-- Tombol View Details -->
        <button class="btn btn-info btn-sm" onclick="viewDetails(${escapeHtml(product.product_id)})">
          <i class="fas fa-eye"></i> View Details
        </button>
        <!-- Tombol Edit -->
        <button class="btn btn-warning btn-sm" onclick="editProduct(${escapeHtml(product.product_id)})">
          <i class="fas fa-edit"></i> Edit
        </button>
      </td>
    `;
      tbody.appendChild(row);
    });

    // Re-attach listeners and update UI
    attachCheckboxListeners();
    updateDeleteButtonVisibility();
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

// ==================== JS untuk Attach Checkboxes ==================== //
document.addEventListener("DOMContentLoaded", function () {
  attachCheckboxListeners();

  // Initial check
  updateDeleteButtonVisibility();

  // Attach global event listener untuk perubahan dinamis
  document.addEventListener("change", function (e) {
    if (e.target.classList.contains("product-checkbox")) {
      updateDeleteButtonVisibility();
    }
  });
});
// ==================== Akhir JS untuk Attach Checkboxes ==================== //

// === AKHIR JS UNTUK HALAMAN MANAGE PRODUCTS === //
