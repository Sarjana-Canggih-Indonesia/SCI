// ==================== JS untuk Halaman Forgot Password ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Cek apakah elemen-elemen yang dibutuhkan ada pada halaman
  if (document.querySelector("form.my-forgot-password-validation")) {
    // Validasi form sebelum pengiriman
    document.querySelector("form.my-forgot-password-validation").addEventListener("submit", function (e) {
      var isValid = true;
      var emailOrUsername = document.getElementById("email_or_username");
      var csrfToken = document.querySelector("input[name='csrf_token']");
      var recaptchaResponse = grecaptcha.getResponse();

      // Clear previous error messages
      clearErrors();

      // Validasi email/username
      if (!emailOrUsername.value.trim()) {
        showError(emailOrUsername, "Email or Username is required");
        isValid = false;
      }

      // Validasi CSRF token
      if (!csrfToken || !csrfToken.value.trim()) {
        alert("CSRF token is missing or invalid.");
        isValid = false;
      }

      // Validasi reCAPTCHA
      if (!recaptchaResponse) {
        alert("Please complete the reCAPTCHA."); // Ganti dengan alert
        isValid = false;
      }

      // Prevent form submission if validation fails
      if (!isValid) {
        e.preventDefault();
      }
    });

    // Show error message
    function showError(field, message) {
      var errorDiv = field.nextElementSibling;
      if (errorDiv && errorDiv.classList.contains("invalid-feedback")) {
        errorDiv.textContent = message;
      }
      field.classList.add("is-invalid");
    }

    // Clear previous error messages
    function clearErrors() {
      var fields = document.querySelectorAll(".form-control");
      fields.forEach(function (field) {
        field.classList.remove("is-invalid");
        var errorDiv = field.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains("invalid-feedback")) {
          errorDiv.textContent = "";
        }
      });
    }

    // Remove error message when user starts typing
    document.querySelectorAll(".form-control").forEach(function (field) {
      field.addEventListener("input", function () {
        if (field.classList.contains("is-invalid")) {
          field.classList.remove("is-invalid");
          var errorDiv = field.nextElementSibling;
          if (errorDiv && errorDiv.classList.contains("invalid-feedback")) {
            errorDiv.textContent = "";
          }
        }
      });
    });
  }

  // Tampilkan alert jika ada hasil dari proses reset password
  if (typeof resultStatus !== "undefined" && typeof resultMessage !== "undefined") {
    alert(resultMessage); // Ganti modal dengan alert
  }
});
// ==================== Akhir JS untuk Halaman Forgot Password ==================== //
