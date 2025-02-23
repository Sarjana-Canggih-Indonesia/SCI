// === JS UNTUK HALAMAN MANAGE PRODUCTS === //

// ==================== Global Helper Functions ==================== //
/**
 * Escapes HTML special characters to prevent XSS attacks.
 */
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

/**
 * Formats a number into Indonesian Rupiah currency format.
 */
function formatPrice(amount) {
  return (
    Number(amount).toLocaleString("id-ID", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }) + ",00"
  );
}

/**
 * Retrieves the CSRF token from the meta tag.
 */
function getCsrfToken() {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
  if (!csrfToken) {
    throw new Error("CSRF token not found.");
  }
  return csrfToken;
}

/**
 * Handles API responses and throws errors for non-OK responses.
 */
async function handleResponse(response) {
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  return response.json();
}

/**
 * Shows a notification message (replaces alert).
 */
function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);
  setTimeout(() => notification.remove(), 3000);
}

/**
 * Debounce function to limit the rate of function execution.
 */
function debounce(func, delay) {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => func.apply(this, args), delay);
  };
}

function editProduct(slug, optimusId) {
  window.location.href = `${BASE_URL}edit-product/${slug}/${optimusId}`;
}

// ==================== Akhir Global Helper Functions ==================== //

// ==================== JS untuk Checkboxes dan Delete Selected ==================== //
/**
 * Toggles the visibility of the "Delete Selected" button.
 */
function updateDeleteButtonVisibility() {
  const checkboxes = document.querySelectorAll(".product-checkbox");
  const deleteSelectedBtn = document.getElementById("deleteSelectedBtn");
  if (deleteSelectedBtn) {
    deleteSelectedBtn.classList.toggle("d-none", ![...checkboxes].some((cb) => cb.checked));
  }
}

/**
 * Handles bulk deletion of selected products.
 */
async function deleteSelectedProducts() {
  try {
    const selectedProducts = Array.from(document.querySelectorAll(".product-checkbox:checked")).map((cb) => cb.value);

    if (selectedProducts.length === 0) {
      showNotification("Please select at least one product!", "error");
      return;
    }

    const response = await fetch(`${BASE_URL}api-proxy.php?action=delete_selected_products`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": getCsrfToken(),
      },
      body: JSON.stringify({ product_ids: selectedProducts }),
      credentials: "include",
    });

    const data = await handleResponse(response);
    if (!data.error) {
      showNotification("Selected products deleted successfully!", "success");
      window.location.reload();
    } else {
      showNotification("Failed to delete some products.", "error");
    }
  } catch (error) {
    showNotification(`An error occurred: ${error.message}`, "error");
  }
}

// ==================== JS untuk Filter Category dan Search Bar ==================== //
/**
 * Updates the product table with the provided data.
 */
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
        <button class="btn btn-info btn-sm" onclick="viewDetails(${escapeHtml(product.product_id)})">
          <i class="fas fa-eye"></i> View Details
        </button>
        <button class="btn btn-warning btn-sm" onclick="editProduct(${escapeHtml(product.product_id)})">
          <i class="fas fa-edit"></i> Edit
        </button>
      </td>
    `;
    tbody.appendChild(row);
  });

  updateDeleteButtonVisibility();
}

/**
 * Fetches and updates products based on a category filter.
 */
async function filterProductsByCategory(categoryId) {
  try {
    const url = `${BASE_URL}api-proxy.php?action=get_products_by_category${
      categoryId ? `&category_id=${categoryId}` : ""
    }`;
    const response = await fetch(url, { credentials: "include" });
    const data = await handleResponse(response);
    if (data.success) {
      updateTable(data.products);
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    showNotification(`Failed to load data: ${error.message}`, "error");
  }
}

/**
 * Fetches and updates products based on a search keyword.
 */
async function searchProducts(keyword) {
  try {
    const url = `${BASE_URL}api-proxy.php?action=get_search_products&keyword=${encodeURIComponent(keyword)}`;
    const response = await fetch(url, { credentials: "include" });
    const data = await handleResponse(response);
    if (data.success) {
      updateTable(data.products);
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    showNotification(`Network error: ${error.message}`, "error");
  }
}

// ==================== JS untuk Tagify ==================== //
let tagify = null;

function initializeTagify() {
  const input = document.getElementById("productTags");
  if (tagify) tagify.destroy();

  tagify = new Tagify(input, {
    whitelist: TAGS_WHITELIST,
    dropdown: { enabled: 1, maxItems: 50, closeOnSelect: false, highlightFirst: true },
    enforceWhitelist: false,
    editTags: true,
    duplicates: false,
    placeholder: "Enter tags",
    maxTags: 10,
    pattern: /^[a-zA-Z0-9\s\-_]+$/,
  });

  tagify.on("add", (e) => {
    const tagValue = e.detail.data.value;
    if (!/^[a-zA-Z0-9\s\-_]+$/.test(tagValue)) {
      showNotification(`Invalid tag: ${tagValue}`, "error");
      tagify.removeTag(e.detail.tag);
    }
    if (tagify.value.length > 10) {
      showNotification("Max 10 tags allowed", "error");
      tagify.removeTag(e.detail.tag);
    }
  });
}

// ==================== JS untuk Modal Detail Product ==================== //
function viewDetails(productId) {
  console.log("Loading product details:", productId);

  // Get CSRF token from meta tag
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  // Construct the API proxy URL
  const apiUrl = `${BASE_URL}api-proxy.php?action=get_product_details&product_id=${productId}`;

  // Perform a fetch request to retrieve product details
  fetch(apiUrl, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken,
    },
  })
    .then((response) => {
      // Check if the response is not OK (status not in the 200-299 range)
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      // Parse the JSON response, handling potential parsing errors
      return response.json().catch(() => {
        throw new Error("Invalid JSON response");
      });
    })
    .then((data) => {
      // If the API response indicates success
      if (data.success) {
        const product = data.product;

        // Update modal content with product details
        document.getElementById("detailProductName").textContent = product.name;
        document.getElementById("detailProductDescription").textContent = product.description;
        document.getElementById("detailProductPrice").textContent = `Rp ${parseInt(product.price).toLocaleString(
          "id-ID",
        )},00`;
        document.getElementById("detailProductCurrency").textContent = product.currency;
        document.getElementById("detailProductCategories").textContent = product.categories;
        document.getElementById("detailProductTags").textContent = product.tags;
        document.getElementById("detailProductCreatedAt").textContent = new Date(product.created_at).toLocaleString(
          "id-ID",
        );
        document.getElementById("detailProductUpdatedAt").textContent = new Date(product.updated_at).toLocaleString(
          "id-ID",
        );

        // Handle product image display
        const imgElement = document.getElementById("detailProductImage");
        imgElement.src = product.image ? `${BASE_URL}${product.image}` : `${BASE_URL}assets/images/no-image.jpg`;

        // Show the product details modal
        new bootstrap.Modal(document.getElementById("productDetailsModal")).show();
      } else {
        // Handle server error response
        console.error("Error from server:", data.error);
        alert(`Error: ${data.error || "An unknown error occurred"}`);
      }
    })
    .catch((error) => {
      // Handle fetch errors
      console.error("Failed to load product details:", error);
      alert("Failed to load product details. Please check the console for more information.");
    });
}

// ==================== Event Listeners dan Inisialisasi ==================== //
document.addEventListener("DOMContentLoaded", () => {
  // Event delegation untuk checkbox
  document.getElementById("productsTableBody")?.addEventListener("change", (e) => {
    if (e.target.classList.contains("product-checkbox")) {
      updateDeleteButtonVisibility();
    }
  });

  // Select All Button
  const selectAllButton = document.getElementById("manage_products-selectAllButton");
  if (selectAllButton) {
    selectAllButton.addEventListener("click", () => {
      const checkboxes = document.querySelectorAll(".product-checkbox");
      const isAnyUnchecked = [...checkboxes].some((cb) => !cb.checked);
      checkboxes.forEach((cb) => (cb.checked = isAnyUnchecked));
      updateDeleteButtonVisibility();
    });
  }

  // Delete Selected Button
  document.getElementById("confirmDeleteSelected")?.addEventListener("click", deleteSelectedProducts);

  // Filter Category
  document.getElementById("categoryFilter")?.addEventListener("change", (e) => {
    filterProductsByCategory(e.target.value || null);
  });

  // Search Bar
  const searchInput = document.getElementById("searchInput");
  const debouncedSearch = debounce(() => {
    const keyword = searchInput.value.trim();
    keyword ? searchProducts(keyword) : filterProductsByCategory(null);
  }, 300);

  searchInput?.addEventListener("input", debouncedSearch);

  // Tagify
  $("#addProductModal").on("shown.bs.modal", initializeTagify);
  $("#addProductModal").on("hidden.bs.modal", () => {
    if (tagify) tagify.destroy();
  });
});
