<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup database DOOM REVERSAL</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="container help">
        <img src="assets/images/logo.png" alt="DOOM REVERSAL" style="width:130px;margin-bottom:35px">
        <h1>Database belum terhubung.</h1>
        <p>Versi ini memakai MySQL/MariaDB. Selesaikan setup berikut:</p>
        <ol>
            <li>Nyalakan <strong>Apache dan MySQL</strong> di XAMPP, atau Start All di Laragon.</li>
            <li>Buka phpMyAdmin pada instalasi lokalmu.</li>
            <li>Pilih Import, lalu import file <strong>database/doom_reversal.sql</strong> dari folder proyek. File ini membuat database dan data produk.</li>
            <li>Periksa <strong>config/database.php</strong> jika host, port, username atau password MySQL berbeda.</li>
            <li>Pastikan ekstensi <strong>pdo_mysql</strong> aktif.</li>
        </ol>
        <p>Konfigurasi awal: host 127.0.0.1, port 3306, database doom_reversal, username root, password kosong. Pesan teknis koneksi tidak ditampilkan agar kredensial tetap terlindungi.</p>
        <a class="button" href="index.php">Coba lagi</a>
    </main>
</body>
</html>