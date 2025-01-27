// ==================== JS untuk Halaman Login ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Cek apakah elemen-elemen yang dibutuhkan ada pada halaman
  if (document.querySelector("form.my-login-validation")) {
    // Toggle visibility untuk password login
    document.getElementById("toggle-login-password-0").addEventListener("click", function () {
      var passwordField = document.getElementById("password");
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

    // Validasi form sebelum pengiriman
    document.querySelector("form.my-login-validation").addEventListener("submit", function (e) {
      var isValid = true;
      var username = document.getElementById("username");
      var password = document.getElementById("password");
      var honeypot = document.querySelector("input[name='honeypot']");
      var csrfToken = document.querySelector("input[name='csrf_token']");
      var recaptchaResponse = grecaptcha.getResponse();

      // Clear previous error messages
      clearErrors();

      // Validasi honeypot (form spam bot)
      if (honeypot && honeypot.value.trim() !== "") {
        console.warn("Honeypot field filled. Blocking submission.");
        e.preventDefault();
        return; // Stop further validation
      }

      // Validasi username
      if (!username.value.trim()) {
        showError(username, "Username is required");
        isValid = false;
      }

      // Validasi password
      if (!password.value.trim()) {
        showError(password, "Password is required");
        isValid = false;
      }

      // Validasi CSRF token
      if (!csrfToken || !csrfToken.value.trim()) {
        alert("CSRF token is missing or invalid.");
        isValid = false;
      }

      // Validasi reCAPTCHA
      if (!recaptchaResponse) {
        alert("Please verify you are not a robot by completing the reCAPTCHA.");
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
      if (errorDiv) {
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
        if (errorDiv) {
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
          if (errorDiv) {
            errorDiv.textContent = "";
          }
        }
      });
    });
  }
});
// ==================== Akhir JS untuk Halaman Login ==================== //
