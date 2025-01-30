# SCI

Repo untuk website Sarjana Canggih Indonesia

## todo:

### Local

- [] add library symfony/validator
  - [] integrasikan ke public/auth
    - [x] register
    - [x] activate
    - [x] resend activation email
    - [x] forgot password
      - [x] forgot password js
    - [x] reset password
      - [x] reset password js
    - [x] login

### LIVE

- [] ralat url, referensi di login
  - [] activate.php
  - [] logout.php
  - [] forgot_password.php
  - [] reset_password_process.php
  - [] reset_password.php
  - [x] header.php
  - [x] footer.php
  - [x] login.php
  - [x] register.php
  - [x] resend_activation_email.php
- [] rapikan css
- [] perbaiki recaptcha pada live
- [x] index.php
- [x] contact
- [x] recheck header, footer, bagian testimoni
- [x] recheck javascript supaya tidak ada password, email, nomor whatsapp yang terekspos

### DATABASE

- [] ubah database seperti [UserFrosting](https://drawsql.app/templates/userfrosting)

### PHP

- [] user dashboard
  - [] kalau role selain client arahkan ke admin dashboard
  - [] kalau role client arahkan ke user dashboard
  - [] content untuk dashboard (?)
- [] Halaman products
- [] transactions (check out & payments)
- [] Halaman Promo
- [] blog posts dan bagaimana user mengakses posting blog posts

### MAJOR

- [] AI Response (?)

## Reminder:

- Optimus hanya digunakan pada
  1. Halaman profil pengguna.
  2. Halaman yang mengakses data menggunakan ID tertentu dalam URL (seperti user_id, post_id, product_id dll).
  3. API yang mengirimkan ID sebagai parameter.
  4. Jika ID tidak pernah dibagikan di URL atau parameter, maka Anda mungkin tidak perlu menggunakan Optimus.
