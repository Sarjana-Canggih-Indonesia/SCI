// ==================== JS untuk Halaman Reset Password ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Cek apakah elemen-elemen yang dibutuhkan ada pada halaman
  if (document.querySelector("form.my-reset-password-validation")) {
    // Validasi form sebelum pengiriman
    document.querySelector("form.my-reset-password-validation").addEventListener("submit", function (e) {
      var isValid = true;
      var newPassword = document.getElementById("new-password");
      var confirmPassword = document.getElementById("confirm-password");
      var csrfToken = document.querySelector("input[name='csrf_token']");
      var recaptchaResponse = grecaptcha.getResponse();

      // Clear previous error messages
      clearErrors();

      // Validasi password baru
      var passwordValidationResult = validatePassword(newPassword.value);
      if (passwordValidationResult.length > 0) {
        passwordValidationResult.forEach(function (error) {
          showError(newPassword, error);
        });
        isValid = false;
      }

      // Validasi konfirmasi password
      if (!confirmPassword.value.trim()) {
        showError(confirmPassword, "Confirm Password is required");
        isValid = false;
      } else if (confirmPassword.value !== newPassword.value) {
        showError(confirmPassword, "Passwords do not match");
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

  // Toggle confirm password visibility
  if (document.getElementById("reset-password-passeye-toggle-1")) {
    document.getElementById("reset-password-passeye-toggle-1").addEventListener("click", function () {
      var confirmPasswordField = document.getElementById("confirm-password");
      var icon = this.querySelector("i");
      if (confirmPasswordField.type === "password") {
        confirmPasswordField.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        confirmPasswordField.type = "password";
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

// Fungsi untuk validasi password
function validatePassword(password) {
  var errors = [];

  // Validasi panjang password
  if (password.length < 6 || password.length > 20) {
    errors.push("Password must be between 6 and 20 characters long.");
  }

  // Validasi huruf besar
  if (!/[A-Z]/.test(password)) {
    errors.push("Password must contain at least one uppercase letter.");
  }

  // Validasi huruf kecil
  if (!/[a-z]/.test(password)) {
    errors.push("Password must contain at least one lowercase letter.");
  }

  // Validasi angka
  if (!/\d/.test(password)) {
    errors.push("Password must contain at least one number.");
  }

  return errors;
}
// ==================== Akhir JS untuk Halaman Reset Password ==================== //
