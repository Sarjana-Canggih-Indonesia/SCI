# SCI

Repo untuk website Sarjana Canggih Indonesia

## todo:

### Local

- [] add library symfony/validator
  - [] integrasikan ke public/auth
    - [x] register
    - [x] activate
    - [x] resend activation email
    - [] reset password
    - [] login
- [] check
  - [x] folder auth
    - [x] activate.php
    - [x] resend_activation_email.php
    - []
  - [] index.php utama
- [] buatkan function untuk send password reset email
- [x] library, filp/whoops

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

## done:

- [x] ubah logika database
  - [x] tabel customer_profiles mengakibatkan error saat register
- [x] login tambahkan
  - [x] dynamic base url
  - [x] csrf
  - [x] recaptchax
  - [x] honeypot
  - [x] antixss
  - [x] symfony httpclient
- [x] register
  - [x] dynamic base url
  - [x] csrf
  - [x] recaptcha
  - [x] honeypot
  - [x] antixss
  - [x] symfony httpclient
- [x] activation page
- [x] resend_activation_email
- [x] Header
  - [x] profile image muncul
- [x] tambahkan recaptcha live dan local pada .env
- [x] periksa value pada config, bagian define recaptcha

## Reminder:

- Optimus hanya digunakan pada
  1. Halaman profil pengguna.
  2. Halaman yang mengakses data menggunakan ID tertentu dalam URL (seperti user_id, post_id, product_id dll).
  3. API yang mengirimkan ID sebagai parameter.
  4. Jika ID tidak pernah dibagikan di URL atau parameter, maka Anda mungkin tidak perlu menggunakan Optimus.
