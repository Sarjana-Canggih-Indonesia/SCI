# SCI

Repo untuk website Sarjana Canggih Indonesia

## Reminder:

- Optimus hanya digunakan pada
  1. Halaman profil pengguna.
  2. Halaman yang mengakses data menggunakan ID tertentu dalam URL (seperti user_id, post_id, product_id dll).
  3. API yang mengirimkan ID sebagai parameter.
  4. Jika ID tidak pernah dibagikan di URL atau parameter, maka Anda mungkin tidak perlu menggunakan Optimus.🛠 

- Rekomendasi Praktik Keamanan
  1. Saat menerima input dari user: Gunakan sanitize_input() untuk membersihkan input sebelum menyimpannya.
  2. Saat menampilkan output di HTML: Gunakan escapeHTML() sebelum mencetak data ke halaman web.
