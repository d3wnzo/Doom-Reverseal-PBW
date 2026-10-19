# DOOM REVERSAL — PHP native + MySQL/MariaDB

Website toko pakaian berdasarkan UI dalam Downloads.zip dan materi PBW pertemuan 1–7. Versi ini memakai **database SQL**, bukan file JSON. Source PHP, gambar, CSS dan JavaScript tersedia lokal. Checkout dan pembayaran tetap simulasi tugas.

## Instalasi XAMPP

1. Ekstrak ZIP, salin folder `doom-reversal` ke `C:\xampp\htdocs\`.
2. Pastikan `C:\xampp\htdocs\doom-reversal\index.php` tersedia langsung, tanpa folder proyek berlapis.
3. Nyalakan **Apache dan MySQL** melalui XAMPP Control Panel.
4. Buka `http://localhost/phpmyadmin/`.
5. Pada tab **Import**, pilih `doom-reversal/database/doom_reversal.sql`, lalu klik Import/Go.
6. SQL membuat database **doom_reversal**, lima tabel, dan 13 produk awal. Tidak ada akun bawaan.
7. Buka **http://localhost/doom-reversal/**. Klik ikon akun, Register, buat akun demo sendiri.

## Instalasi Laragon

1. Salin folder `doom-reversal` ke `C:\laragon\www\` atau document root yang kamu atur.
2. Klik **Start All** untuk menyalakan web server dan MySQL/MariaDB.
3. Import `database/doom_reversal.sql` melalui phpMyAdmin jika tersedia, atau menu database/HeidiSQL Laragon. Pada HeidiSQL, buka koneksi lokal, pilih File > Load SQL file, lalu jalankan file SQL.
4. Buka **http://localhost/doom-reversal/**. Auto Virtual Hosts Laragon juga dapat memberi alamat `http://doom-reversal.test` setelah Reload.

Laragon tertentu belum menyertakan phpMyAdmin. Import juga dapat dilakukan lewat terminal dengan client MySQL:

```bat
mysql -u root -p < database\doom_reversal.sql
```

Perintah dijalankan dari folder doom-reversal. Masukkan password MySQL ketika diminta, atau tekan Enter bila password kosong. Client mysql harus tersedia di PATH/terminal Laragon.

## Koneksi database

File `config/database.php` memiliki konfigurasi awal:

```php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'doom_reversal',
    'username' => 'root',
    'password' => '',
];
```

Ubah host/port/user/password sesuai instalasi. Root tanpa password hanya nilai default untuk demo localhost, bukan rekomendasi server publik. Aktifkan ekstensi **pdo_mysql**. Minimum PHP 8.0 dan MySQL 5.7+/8.x atau MariaDB 10.4+. Tidak perlu Composer, npm atau framework. Apache di port 8080 memakai URL `http://localhost:8080/doom-reversal/`.

Jika belum terkoneksi, aplikasi menampilkan panduan setup dengan HTTP 503 tanpa membocorkan detail kredensial.

## Data masuk ke mana?

| Aktivitas | Lokasi data | Penjelasan |
|---|---|---|
| Register | tabel `users` | Nama, email unik, hash password, waktu pembuatan |
| Login | membaca `users`, lalu session PHP | Status login berupa user di session. Tidak ada tabel log login terpisah |
| Produk | tabel `products` | Katalog, kategori, warna, harga, nama gambar, metadata available |
| Keranjang | session PHP | ID produk, ukuran dan qty. Belum disimpan sebagai tabel cart |
| Checkout | tabel `orders` | Kode, pemilik, penerima, alamat, subtotal, diskon dan total |
| Pembayaran | tabel `payments` | Metode demo, nominal, status, reference dan paid_at |
| Isi pesanan | tabel `order_items` | Produk, ukuran, qty, snapshot nama/harga, subtotal tiap baris |
| Logout | session PHP | Menghapus user dari session, tidak menghapus akun/pesanan |

Lihat data melalui phpMyAdmin > database **doom_reversal** > tabel > Browse/Jelajahi. Password disimpan sebagai hash, bukan teks asli. Keranjang dan status login tidak muncul sebagai tabel MySQL karena merupakan data sesi sementara di server PHP. Browser hanya membawa cookie ID sesi.

Foreign key: `orders.user_id` mengarah ke `users.id`. `order_items.order_id` mengarah ke `orders.id`. `order_items.product_id` mengarah ke `products.id`. Nama, warna, gambar dan harga pada order_items adalah snapshot agar riwayat tetap menjelaskan produk saat dipesan.

SQL memakai InnoDB agar foreign key dan transaksi bekerja. Import ulang memakai CREATE IF NOT EXISTS dan seed yang mempertahankan row produk yang sudah ada, bukan menghapus akun atau pesanan. File SQL ini bukan migrasi dari database dengan struktur berbeda.

## Fitur dan batasnya

- 13 produk: enam Men, enam Women, satu Pants. Kids “segera hadir”.
- Pencarian nama/warna, kategori, pengurutan, pagination enam produk.
- Detail produk, ukuran S/M/L/XL, qty 1–20, accordion dan panduan ukuran ilustratif.
- Register, login/logout, akun dan riwayat milik pengguna.
- Keranjang berdasarkan ID+ukuran, update/hapus, laporan dan cetak lewat browser.
- Diskon 10% bila subtotal minimal Rp 900.000. Tiga produk @Rp 300.000 menghasilkan diskon Rp 90.000 dan total Rp 810.000.
- Checkout memvalidasi data penerima lalu menyimpan orders dan order_items dalam **satu transaksi**. Commit sukses mengosongkan cart, kegagalan memakai rollback dan cart tetap ada.
- CSS responsif dan show/hide password; semua aset lokal.

Tidak ada pembayaran, pengiriman, stok nyata, admin, ulasan, reset password via email atau remember-me persisten. `available` adalah metadata produk, bukan jumlah stok. Keranjang belum persisten lintas sesi/perangkat. Foto dari UI berlogo Nike, sedangkan nama toko mengikuti mockup DOOM REVERSAL, bukan klaim produk resmi. Panduan ukuran dan marketing merupakan contoh tugas.

Versi file JSON sebelumnya tidak digunakan lagi. Jika sudah membuat akun/pesanan pada versi lama, data itu tidak otomatis masuk ke MySQL. Buat ulang akun demo atau lakukan migrasi terpisah jika datanya perlu dipertahankan. Jangan hapus data lama sebelum memastikan kebutuhan migrasi.

## Struktur source

- `index.php`: routing sederhana, halaman dan riwayat.
- `config/database.php`: parameter koneksi SQL.
- `database/doom_reversal.sql`: schema dan data produk awal.
- `includes/database.php`: koneksi PDO MySQL.
- `includes/bootstrap.php`: session, CSRF, helper dan perhitungan.
- `includes/products.php`: SELECT katalog SQL, hasilnya menjadi array.
- `includes/actions.php`: register/login, keranjang, transaksi checkout.
- `includes/layout.php`: header, footer dan kartu produk.
- `includes/setup-help.php`: petunjuk jika database belum siap.
- `assets/`: CSS, JS, gambar lokal.

## Penerapan materi

| Pertemuan | Penerapan |
|---|---|
| 1 | PHP server-side, HTML dinamis, localhost, index.php |
| 2 | Fungsi prosedural dan include modular |
| 3 | Variabel, array hasil query, string, integer, boolean dan konstanta float |
| 4 | Perkalian subtotal, +=, syarat >=, trim, strtolower, stripos, htmlspecialchars |
| 5 | WHILE pada cartRows() |
| 6 | DO-WHILE untuk kandidat kode pesanan sampai tidak bentrok |
| 7 | FOR pada total, katalog/laporan; nested FOR tabel ukuran; pagination |

Database PDO ditambahkan sesuai permintaan versi SQL. Proyek ini menerapkan materi ke toko, bukan mengerjakan semua latihan PDF sebagai halaman terpisah.

## Keamanan dasar

Koneksi PDO memakai utf8mb4, exception mode dan native prepared statements. Query berinput pengguna memakai placeholder; email dan kode order mempunyai unique index. Password memakai password_hash dan password_verify. Session ID diperbarui setelah autentikasi/logout. Form POST memakai token CSRF, output pengguna di-escape, harga dari katalog server dan jumlah divalidasi server. Transaksi checkout menjaga header serta detail pesanan tersimpan bersama.

Batas: rate limit login per sesi mudah dilewati melalui sesi baru, field password di-trim, batas strlen menghitung byte, serta kredensial default khusus lokal. Tidak ada backup/migrasi otomatis. Belum layak untuk layanan transaksi publik.

## Demo singkat

1. Import SQL lalu buka web. Jika database belum siap, baca petunjuk setup.
2. Register akun demo. Pastikan row muncul pada users di phpMyAdmin.
3. Cari Lilac, pilih ukuran M qty 2, tambahkan ukuran L qty 1.
4. Cek subtotal Rp 900.000, diskon Rp 90.000, total Rp 810.000.
5. Tampilkan laporan keranjang.
6. Checkout memakai alamat contoh. Cek row baru pada orders dan dua row pada order_items.
7. Login akun berbeda: pesanan akun pertama tidak boleh muncul.

## Troubleshooting

- 503/setup: MySQL belum aktif, schema belum diimport, kredensial salah, port beda, atau pdo_mysql belum aktif.
- 404: periksa folder proyek tidak berlapis setelah ekstrak.
- 500 .htaccess: jika Apache memblok Options, hapus baris `Options -Indexes` pada .htaccess root.
- CSRF kedaluwarsa: reload form dan pastikan cookie diaktifkan.
- Gambar hilang setelah edit produk: image berisi nama file lokal, bukan path absolut atau URL sembarang.
- Forgot password membuka penjelasan karena layanan reset email belum tersedia.

## Referensi

Materi utama: tujuh PDF PBW dan UI pada ZIP pengguna. Dokumentasi resmi:
- https://www.php.net/manual/en/pdo.prepare.php
- https://www.php.net/manual/en/pdo.begintransaction.php
- https://www.php.net/manual/en/function.session-start.php
- https://www.php.net/manual/en/function.password-hash.php
- https://www.php.net/manual/en/function.password-verify.php
- https://www.php.net/manual/en/function.htmlspecialchars.php

## Pembayaran setelah checkout

Checkout membuat order, detail dan satu payments pending dalam satu transaksi. Browser langsung menuju halaman pembayaran. Pilih transfer bank demo, e-wallet demo atau COD demo. Bank/e-wallet menjadi `paid_demo` setelah konfirmasi, sementara COD menjadi `cod_pending`, **belum lunas**. Tidak ada nomor rekening, QR bayar, PIN/OTP atau proses uang nyata.

Nominal mengambil orders.total dari server. Halaman hanya untuk pemilik order. Konfirmasi memakai CSRF dan transaksi dengan SELECT FOR UPDATE. Status harus pending; request berulang tidak membuat pembayaran kedua. Tabel payments mempunyai unique order_id. Bukti simulasi mencantumkan reference DEMO dan dapat dicetak. Pembayaran tertunda dapat dilanjutkan dari riwayat akun.

Import ulang SQL juga membuat tabel payments dan mengisi pembayaran pending untuk pesanan SQL lama yang belum mempunyai row payment. Data akun/pesanan lama tidak dihapus.

## Verifikasi versi final

Diuji memakai PHP 8.3 dan MariaDB 10.11 lokal: import schema lima tabel, 13 produk, register/login/logout, email duplikat, hashing, keranjang, diskon, checkout, pembatasan riwayat antar akun, pembayaran bank demo, COD belum lunas, konfirmasi ulang, query login dengan input SQL injection, serta import ulang yang mempertahankan data. Uji kegagalan INSERT detail secara sengaja memastikan transaksi rollback tanpa meninggalkan header order/payments dan keranjang tetap ada. Semua file PHP lulus syntax check.

Pengujian ini tidak menjalankan XAMPP/Laragon Windows secara langsung. Layout responsif web belum diverifikasi visual lewat browser. PPT dan PDF sudah dirender untuk pemeriksaan layout. Dokumen presentasi berada pada folder `docs` dalam paket ini.
