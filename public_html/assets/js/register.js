// ==================== JS untuk Halaman Register ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Cek apakah elemen-elemen yang dibutuhkan ada pada halaman
  if (document.querySelector("form.halaman-register")) {
    // Toggle visibility for register password
    document.getElementById("toggle_register_password_0").addEventListener("click", function () {
      var passwordField = document.getElementById("register_password");
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

    // Toggle visibility for register confirm password
    document.getElementById("toggle_register_password_1").addEventListener("click", function () {
      var confirmPasswordField = document.getElementById("register_confirm_password");
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

    // Form validation before submission
    document.querySelector("form.halaman-register").addEventListener("submit", function (e) {
      var isValid = true;
      var username = document.getElementById("register_username");
      var email = document.getElementById("register_email");
      var password = document.getElementById("register_password");
      var confirmPassword = document.getElementById("register_confirm_password");
      var honeypot = document.getElementById("register_honeypot");
      var csrfToken = document.getElementById("register_csrf_token");
      var recaptchaResponse = grecaptcha.getResponse();

      // Clear previous error messages
      clearErrors();

      // Validasi honeypot (form spam bot)
      if (honeypot.value.trim() !== "") {
        console.warn("Honeypot field filled. Blocking submission.");
        e.preventDefault();
        return; // Stop further validation
      }

      // Validasi username dengan regex
      var usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
      if (!username.value.trim()) {
        showError(username, "Username is required");
        isValid = false;
      } else if (!usernameRegex.test(username.value)) {
        showError(username, "Username must be 3-20 characters long and contain only letters, numbers, and underscores");
        isValid = false;
      }

      // Validasi email dengan regex
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email.value.trim()) {
        showError(email, "Email is required");
        isValid = false;
      } else if (!emailRegex.test(email.value)) {
        showError(email, "Please enter a valid email address");
        isValid = false;
      }

      // Validasi password strength
      var passwordStrengthRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/;
      if (!password.value.trim()) {
        showError(password, "Password is required");
        isValid = false;
      } else if (!passwordStrengthRegex.test(password.value)) {
        showError(password, "Password must be at least 6 characters, include uppercase, lowercase, and a number");
        isValid = false;
      }

      // Validasi confirm password
      if (!confirmPassword.value.trim()) {
        showError(confirmPassword, "Confirm password is required");
        isValid = false;
      } else if (password.value !== confirmPassword.value) {
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
// ==================== Akhir JS untuk Halaman Register ==================== //
