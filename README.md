# Simple MP

Simple MP adalah aplikasi marketplace warga berbasis Laravel + Filament.
Aplikasi ini mendukung katalog produk publik, profil lapak, autentikasi user dengan verifikasi email, pelaporan konten, moderasi admin, serta sistem token untuk fitur angkat produk.

## Fitur Utama

### Publik (tanpa login)
- Melihat daftar produk dengan filter pencarian, kategori, dan kondisi.
- Melihat detail produk dan daftar produk lain dari lapak yang sama.
- Melihat halaman profil lapak.
- Melihat halaman peraturan pengguna.
- Menghubungi lapak via widget WhatsApp yang tampil di halaman produk dan lapak.

### Member (setelah login)
- Registrasi dan login publik.
- Wajib verifikasi email sebelum bisa mengakses panel member/admin.
- Mengelola profil akun (nama, email, password, avatar).
- Mengaktifkan Two-Factor Authentication (2FA) untuk keamanan akun (opsional).
- Mengelola profil lapak sendiri.
- Mengelola produk milik lapak sendiri.
- Melakukan report produk/lapak.
- Menggunakan token untuk membuat produk baru atau mengangkat produk yang sudah ada.
- Membeli token angkat produk (request pembelian + upload bukti transfer).
- Melihat riwayat pembelian token beserta status konfirmasi per transaksi.

### Admin
- Moderasi laporan (report) dari pengguna.
- Moderasi produk dari semua lapak (tambah, edit, nonaktifkan).
- Konfirmasi atau pembatalan pembelian token user.
- Kelola pengguna dan aktivitas log.
- Kelola halaman tutorial beserta gambar ilustrasi per URL.
- Kelola WhatsApp agent untuk widget kontak yang tampil di halaman publik.
- Atur konfigurasi aplikasi dari panel (judul situs, SEO, wilayah, token, rekening bank, konten peraturan, label link external lapak).

## Stack Teknologi

- PHP 8.2
- Laravel 12
- Filament 5
- MySQL/DB relasional (mengikuti konfigurasi .env)
- Vite + Tailwind CSS 4

## Alur Verifikasi Email

Verifikasi email diwajibkan sebelum user dapat masuk ke area panel.

Alur:
- Setelah register berhasil, sistem mengirim email verifikasi.
- User klik link verifikasi.
- Jika belum menerima email, user dapat kirim ulang dari halaman verifikasi.
- Login akan ditolak jika email belum terverifikasi.

Konfigurasi penting:
- Pastikan APP_URL sesuai domain aplikasi agar URL verifikasi valid.
- Untuk pengembangan cepat, MAIL_MAILER=log akan menulis email ke log Laravel.
- Untuk email nyata, gunakan SMTP dengan variabel MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, dan MAIL_FROM_ADDRESS.

## Sistem Token Angkat Produk

User mendapatkan token awal saat registrasi.
Token dipakai untuk fitur angkat produk, dan user bisa membeli token tambahan melalui transfer bank.

Yang tersedia di sistem:
- Request pembelian token oleh user.
- Upload bukti transfer.
- Validasi dan konfirmasi/cancel oleh admin.
- Refill minimum token harian dan mingguan via scheduler.

Scheduler bawaan:
- tokens:refill-daily (setiap hari pukul 00:00)
- tokens:refill-weekly (setiap Jumat pukul 00:00)

## Menjalankan Project (Local)

### 1. Instalasi cepat

Gunakan script bawaan composer:

```bash
composer run setup
```

Script ini menjalankan:
- install dependency PHP
- membuat file .env (jika belum ada)
- generate app key
- migrate database
- install dependency frontend
- build asset frontend

### 2. Jalankan mode development

```bash
composer run dev
```

Perintah ini menjalankan sekaligus:
- Laravel dev server
- queue worker
- log viewer (pail)
- Vite dev server

## Environment yang Perlu Diperhatikan

- APP_URL: wajib sesuai domain/host aktif.
- DB_*: sesuaikan koneksi database.
- MAIL_*: untuk verifikasi email.
- TURNSTILE_SITE_KEY dan TURNSTILE_SECRET_KEY: untuk proteksi captcha (non-local).

## Aturan Warna

Project ini menggunakan palet warna dari Color Hunt:
https://colorhunt.co/palette/3f9aae79c9c5ffe2aff96e5b

- Primary: #3F9AAE
- Secondary: #79C9C5
- Accent: #FFE2AF
- Other: #F96E5B

Aturan penggunaan:
- Primary digunakan untuk identitas utama brand, tombol primer, dan elemen fokus utama.
- Secondary digunakan untuk area pendukung seperti badge sekunder, panel informasi, atau hover state ringan.
- Accent digunakan sebagai warna latar highlight, callout, atau area penekanan non-kritis.
- Other digunakan untuk aksi penting seperti peringatan, error, atau CTA yang butuh kontras tinggi.

Token CSS disediakan di resources/css/app.css:
- --color-primary
- --color-secondary
- --color-accent
- --color-other