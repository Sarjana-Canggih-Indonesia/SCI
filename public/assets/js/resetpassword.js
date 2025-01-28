// ==================== JS untuk Halaman Reset Password ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Cek apakah elemen-elemen yang dibutuhkan ada pada halaman
  if (document.querySelector("form.my-reset-password-validation")) {
    // Validasi form sebelum pengiriman
    document.querySelector("form.my-reset-password-validation").addEventListener("submit", function (e) {
      var isValid = true;
      var newPassword = document.getElementById("new-password");
      var csrfToken = document.querySelector("input[name='csrf_token']");
      var recaptchaResponse = grecaptcha.getResponse();

      // Clear previous error messages
      clearErrors();

      // Validasi password baru
      if (!newPassword.value.trim()) {
        showError(newPassword, "New Password is required");
        isValid = false;
      } else if (newPassword.value.length < 8) {
        showError(newPassword, "Password must be at least 8 characters long");
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

  // Toggle password visibility
  if (document.getElementById("reset-password-passeye-toggle-0")) {
    document.getElementById("reset-password-passeye-toggle-0").addEventListener("click", function () {
      var passwordField = document.getElementById("new-password");
      var icon = this.querySelector("i");
      if (passwordField.type === "password") {
        passwordField.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        passwordField.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    });
  }

  // Tampilkan alert jika ada hasil dari proses reset password
  if (typeof resultStatus !== "undefined" && typeof resultMessage !== "undefined") {
    alert(resultMessage); // Ganti modal dengan alert
  }
});
// ==================== Akhir JS untuk Halaman Reset Password ==================== //
