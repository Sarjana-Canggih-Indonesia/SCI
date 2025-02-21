// === JS UNTUK HALAMAN MANAGE PRODUCTS === //

// ==================== Global Helper Functions ==================== //
/**
 * Toggles the visibility of the "Delete Selected" button based on checkbox selection.
 * - Selects all elements with the class "product-checkbox".
 * - Checks if at least one checkbox is selected.
 * - Shows the delete button if any checkbox is checked; hides it otherwise.
 */
function updateDeleteButtonVisibility() {
  const checkboxes = document.querySelectorAll(".product-checkbox");
  const deleteSelectedBtn = document.getElementById("deleteSelectedBtn");
  const anyChecked = Array.from(checkboxes).some((checkbox) => checkbox.checked);
  if (deleteSelectedBtn) {
    deleteSelectedBtn.classList.toggle("d-none", !anyChecked);
  }
}

/**
 * Escapes HTML special characters to prevent XSS attacks.
 * - Converts &, <, >, ", and ' to their HTML entity equivalents.
 * - Ensures the input is safely displayed as plain text.
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
 * - Uses "id-ID" locale settings.
 * - Ensures no decimal places and appends ",00" for currency format.
 */
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
/**
 * Attaches event listeners to the "Select All" button and individual checkboxes.
 * - Enables "Select All" functionality to toggle checkbox selection.
 * - Updates the button text dynamically based on the checkbox states.
 * - Ensures the "Delete Selected" button visibility updates when checkboxes change.
 */
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
/**
 * Handles bulk deletion of selected products when the "Confirm Delete" button is clicked.
 * - Ensures the delete button exists before attaching an event listener.
 * - Collects selected product IDs from checked checkboxes.
 * - Validates that at least one product is selected before proceeding.
 * - Retrieves CSRF token from the meta tag for request authentication.
 * - Sends a DELETE request to the API endpoint using `fetch`.
 * - Handles the API response, displaying success or error messages accordingly.
 * - Refreshes the page upon successful deletion to reflect changes.
 */
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

  /**
   * Handles API response by checking if the request was successful.
   * - Throws an error if the HTTP response status is not OK.
   * - Parses and returns the response as JSON.
   */
  function handleResponse(response) {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  }
});
// ==================== Akhir JS untuk Delete Selected ==================== //

// ==================== JS untuk Filter Category ==================== //
/**
 * Handles filtering products by category when the dropdown selection changes.
 * - Retrieves the selected category ID and constructs an API request URL.
 * - Fetches products from the API based on the selected category.
 * - Updates the product table dynamically with the fetched data.
 * - Provides error handling for API failures or network issues.
 */
document.addEventListener("DOMContentLoaded", function () {
  const categoryFilter = document.getElementById("categoryFilter");

  // Add an event listener for when the category selection changes
  categoryFilter.addEventListener("change", function () {
    const categoryId = this.value === "" ? null : this.value;

    let url = `${BASE_URL}api-proxy.php?action=get_products_by_category`;
    if (categoryId !== null) {
      url += `&category_id=${categoryId}`;
    }

    // Fetch products based on the selected category
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
  });

  /**
   * Dynamically updates the product table with the retrieved data.
   * - Clears the existing table content.
   * - Iterates through the product list and creates table rows.
   * - Ensures data is sanitized using `escapeHtml` to prevent XSS.
   * - Attaches event listeners to checkboxes and updates UI states.
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
        <!-- View Details Button -->
        <button class="btn btn-info btn-sm" onclick="viewDetails(${escapeHtml(product.product_id)})">
          <i class="fas fa-eye"></i> View Details
        </button>
        <!-- Edit Button -->
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

  /**
   * Escapes HTML special characters to prevent XSS attacks.
   * - Converts &, <, >, ", and ' to their HTML entity equivalents.
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
   * - Uses "id-ID" locale settings.
   * - Ensures no decimal places and appends ",00" for currency format.
   */
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
  // Get references to the search input field and the search button.
  const searchInput = document.getElementById("searchInput");
  const searchButton = document.querySelector("button.btn-primary");
  let debounceTimer; // Timer for debouncing search input.

  // Load all products when the page is initially loaded.
  loadAllProducts();

  // Add an event listener to the search button to trigger product search when clicked.
  searchButton.addEventListener("click", function () {
    const keyword = searchInput.value.trim(); // Get the trimmed search keyword.
    if (keyword) {
      searchProducts(keyword); // Perform search if keyword is not empty.
    }
  });

  // Add an event listener to the search input field to trigger search as the user types.
  searchInput.addEventListener("input", function () {
    const keyword = searchInput.value.trim(); // Get the trimmed search keyword.

    if (keyword) {
      // Clear the previous timer and set a new one to debounce the search.
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        searchProducts(keyword); // Perform search after a 300ms delay.
      }, 300);
    } else {
      loadAllProducts(); // If the keyword is empty, load all products.
    }
  });

  // Function to search for products based on the provided keyword.
  function searchProducts(keyword) {
    let url = `${BASE_URL}api-proxy.php?action=get_search_products&keyword=${encodeURIComponent(keyword)}`;

    // Fetch data from the server using the constructed URL.
    fetch(url, {
      credentials: "include", // Include cookies in the request.
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json(); // Parse the response as JSON.
      })
      .then((data) => {
        if (data.success) {
          updateTable(data.products); // Update the table with the fetched products.
        } else {
          console.error("Server Error:", data.message);
          alert("Failed to load data: " + data.message); // Show error message if the request fails.
        }
      })
      .catch((error) => {
        console.error("Fetch Error:", error);
        alert(`Terjadi kesalahan jaringan: ${error.message}`); // Show network error message.
      });
  }

  // Function to load all products when no search keyword is provided.
  function loadAllProducts() {
    let url = `${BASE_URL}api-proxy.php?action=get_all_products`;

    // Fetch all products from the server.
    fetch(url, {
      credentials: "include",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json(); // Parse the response as JSON.
      })
      .then((data) => {
        if (data.success) {
          updateTable(data.products); // Update the table with all products.
        } else {
          console.error("Server Error:", data.message);
          alert("Failed to load data: " + data.message); // Show error message if the request fails.
        }
      })
      .catch((error) => {
        console.error("Fetch Error:", error);
        alert(`Terjadi kesalahan jaringan: ${error.message}`); // Show network error message.
      });
  }

  // Function to update the table dynamically with the provided product data.
  function updateTable(products) {
    const tbody = document.getElementById("productsTableBody");
    tbody.innerHTML = ""; // Clear the existing table content.

    // Loop through the products and create a table row for each product.
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
        <!-- Button to view product details -->
        <button class="btn btn-info btn-sm" onclick="viewDetails(${escapeHtml(product.product_id)})">
          <i class="fas fa-eye"></i> View Details
        </button>
        <!-- Button to edit product -->
        <button class="btn btn-warning btn-sm" onclick="editProduct(${escapeHtml(product.product_id)})">
          <i class="fas fa-edit"></i> Edit
        </button>
      </td>
    `;
      tbody.appendChild(row); // Append the row to the table body.
    });

    // Re-attach event listeners and update the UI.
    attachCheckboxListeners();
    updateDeleteButtonVisibility();
  }

  // Function to escape HTML to prevent XSS (Cross-Site Scripting) attacks.
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

  // Function to format the price as Indonesian Rupiah.
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
// This code block ensures that the script runs only after the DOM is fully loaded.
document.addEventListener("DOMContentLoaded", function () {
  let tagify = null; // Variable to store the Tagify instance.

  // Event listener triggered when the "add product" modal is shown.
  $("#addProductModal").on("shown.bs.modal", function () {
    const input = document.getElementById("productTags"); // Get the input field for tags.

    // Destroy the existing Tagify instance if it exists to avoid duplicates.
    if (tagify) tagify.destroy();

    // Initialize a new Tagify instance for the product tags input field.
    tagify = new Tagify(input, {
      whitelist: TAGS_WHITELIST, // Predefined list of allowed tags.
      dropdown: {
        enabled: 1, // Enable dropdown suggestions.
        maxItems: 50, // Maximum number of items to display in the dropdown.
        closeOnSelect: false, // Keep the dropdown open after selecting a tag.
        highlightFirst: true, // Automatically highlight the first suggestion.
        searchKeys: ["value"], // Search tags by their value.
        position: "all", // Position the dropdown relative to the input.
        classname: "tagify-dropdown", // Custom class for the dropdown.
      },
      enforceWhitelist: false, // Allow tags that are not in the whitelist.
      editTags: true, // Allow editing of tags after they are added.
      duplicates: false, // Prevent duplicate tags from being added.
      placeholder: "Enter tags", // Placeholder text for the input field.
      maxTags: 10, // Maximum number of tags allowed.
      pattern: /^[a-zA-Z0-9\s\-_]+$/, // Regex pattern to allow only alphanumeric, spaces, dashes, and underscores.
    });

    // Show the dropdown when the input field is clicked.
    input.addEventListener("click", function () {
      tagify.dropdown.show();
    });

    // Event listener triggered when a new tag is added.
    tagify.on("add", function (e) {
      const tagValue = e.detail.data.value; // Get the value of the added tag.

      // Validate the tag format using the same regex pattern.
      if (!/^[a-zA-Z0-9\s\-_]+$/.test(tagValue)) {
        alert(`Invalid tag: ${tagValue}`); // Show an alert if the tag is invalid.
        tagify.removeTag(e.detail.tag); // Remove the invalid tag.
      }

      // Ensure the maximum number of tags (10) is not exceeded.
      if (tagify.value.length > 10) {
        alert("Max 10 tags allowed"); // Show an alert if the limit is exceeded.
        tagify.removeTag(e.detail.tag); // Remove the excess tag.
      }
    });
  });
});
// ==================== Akhir JS untuk Tagify ==================== //

// ==================== JS untuk Attach Checkboxes ==================== //
// This code block ensures that the script runs only after the DOM is fully loaded.
document.addEventListener("DOMContentLoaded", function () {
  // Attach event listeners to checkboxes for handling product selection.
  attachCheckboxListeners();

  // Perform an initial check to update the visibility of the delete button.
  updateDeleteButtonVisibility();

  // Attach a global event listener to handle dynamic changes in the DOM.
  document.addEventListener("change", function (e) {
    // Check if the changed element has the class "product-checkbox".
    if (e.target.classList.contains("product-checkbox")) {
      // Update the visibility of the delete button when a checkbox is toggled.
      updateDeleteButtonVisibility();
    }
  });
});
// ==================== Akhir JS untuk Attach Checkboxes ==================== //

// === AKHIR JS UNTUK HALAMAN MANAGE PRODUCTS === //
