// ==================== JS untuk Halaman Profile ==================== //
document.addEventListener("DOMContentLoaded", function () {
  // Function to validate a field with specific constraints
  function validateField(input, constraints) {
    let violations = [];

    // Check NotBlank constraint
    if (constraints.notBlank && input.value.trim() === "") {
      violations.push(constraints.notBlank.message);
    }

    // Check Length constraint
    if (constraints.length) {
      const length = input.value.length;
      if (length < constraints.length.min) {
        violations.push(constraints.length.minMessage);
      }
      if (length > constraints.length.max) {
        violations.push(constraints.length.maxMessage);
      }
    }

    // Check Regex constraint
    if (constraints.regex) {
      constraints.regex.forEach((regexConstraint) => {
        if (!regexConstraint.pattern.test(input.value)) {
          violations.push(regexConstraint.message);
        }
      });
    }

    return violations;
  }

  // Function to validate password
  function validatePassword(passwordInput) {
    const constraints = {
      notBlank: { message: "Password cannot be blank." },
      length: {
        min: 6,
        max: 20,
        minMessage: "Password must be at least 6 characters long.",
        maxMessage: "Password can be a maximum of 20 characters long.",
      },
      regex: [
        { pattern: /[A-Z]/, message: "Password must contain at least one uppercase letter." },
        { pattern: /[a-z]/, message: "Password must contain at least one lowercase letter." },
        { pattern: /\d/, message: "Password must contain at least one number." },
      ],
    };

    const violations = validateField(passwordInput, constraints);
    return violations;
  }

  // Function to validate email
  function validateEmail(emailInput) {
    const constraints = {
      notBlank: { message: "Email cannot be blank." },
      regex: [{ pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/, message: "Invalid email format." }],
    };

    const violations = validateField(emailInput, constraints);
    return violations;
  }

  // Function to validate first name and last name (only a-z characters)
  function validateName(nameInput) {
    const constraints = {
      notBlank: { message: "Name cannot be blank." },
      regex: [{ pattern: /^[a-zA-Z]+$/, message: "Name can only contain letters (A-Z)." }],
    };

    const violations = validateField(nameInput, constraints);
    return violations;
  }

  // Function to validate phone number format (+ followed by country code and 15 digits maximum)
  function validatePhone(phoneInput) {
    const constraints = {
      notBlank: { message: "Phone number cannot be blank." },
      regex: [
        {
          pattern: /^\+\d{1,4}\d{7,14}$/,
          message: 'Phone number must start with "+" followed by country code and up to 15 digits.',
        },
      ],
    };

    const violations = validateField(phoneInput, constraints);
    return violations;
  }

  // Function to validate birthday (must not be in the future)
  function validateBirthday(birthdayInput) {
    const today = new Date();
    const inputDate = new Date(birthdayInput.value);

    if (inputDate > today) {
      return ["Birthday cannot be in the future."];
    }

    return [];
  }

  // Edit Profile Modal Logic
  function editProfile() {
    // Get current profile data (This should be fetched dynamically from the server)
    const firstName = document.getElementById("profile-first-name").textContent;
    const lastName = document.getElementById("profile-last-name").textContent;
    const phone = document.getElementById("profile-phone").textContent;
    const email = document.getElementById("profile-client-email-info").textContent;
    const birthday = document.getElementById("profile-birthday").textContent;

    // Populate the modal with current profile data
    document.getElementById("profile-edit-first-name").value = firstName;
    document.getElementById("profile-edit-last-name").value = lastName;
    document.getElementById("profile-edit-phone").value = phone;
    document.getElementById("profile-edit-email").value = email;
    document.getElementById("profile-edit-birthday").value = birthday;

    // Show modal
    new bootstrap.Modal(document.getElementById("profile-editProfileModal")).show();
  }

  // Handle Edit Profile form submission
  document.getElementById("profile-editProfileForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const inputs = [
      document.getElementById("profile-edit-first-name"),
      document.getElementById("profile-edit-last-name"),
      document.getElementById("profile-edit-phone"),
      document.getElementById("profile-edit-email"),
      document.getElementById("profile-edit-birthday"),
    ];

    let isValid = true;

    // Validate each field
    inputs.forEach((input) => {
      let violations = [];
      switch (input.id) {
        case "profile-edit-first-name":
        case "profile-edit-last-name":
          violations = validateName(input);
          break;
        case "profile-edit-phone":
          violations = validatePhone(input);
          break;
        case "profile-edit-email":
          violations = validateEmail(input);
          break;
        case "profile-edit-birthday":
          violations = validateBirthday(input);
          break;
      }

      if (violations.length > 0) {
        input.classList.add("is-invalid");
        alert(violations.join("\n"));
        isValid = false;
      } else {
        input.classList.remove("is-invalid");
      }
    });

    if (isValid) {
      // Perform form submission (e.g., via AJAX)
      alert("Profil berhasil diperbarui");
      // Close the modal
      bootstrap.Modal.getInstance(document.getElementById("profile-editProfileModal")).hide();
    } else {
      alert("Harap lengkapi semua field dengan benar.");
    }
  });

  // Expose the functions to global scope for the onclick events in HTML
  window.editProfile = editProfile;
});
// ==================== Akhir JS untuk Halaman Profile ==================== //
