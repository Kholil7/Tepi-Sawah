<?php
require '../../database/connect.php';
require '../include/home_f.php';

// ambil kode unik dari URL (misal: ?kode=abc123)
$kode_unik = $_GET['kode'] ?? '';

// ambil data meja berdasarkan kode unik
$meja = getMejaByKode($kode_unik, $conn);

if (!$meja) {
    echo "<h2>Meja tidak ditemukan!</h2>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesehan Tepi Sawah</title>
    <link rel="stylesheet" href="../../css/customer/home.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 class="nama-resto">Kantin Tepi Sawah</h2>
            <p>Selamat Datang</p>
        </div>

        <div class="card-meja">
            <p class="text-muted">Anda duduk di</p>
            <h1 class="nomor-meja">Meja <?= htmlspecialchars($meja['nomor_meja']); ?></h1>
        </div>

        <div class="aksi">
            <a href="menu.php?kode=<?= urlencode($kode_unik); ?>" class="btn">Lihat Menu</a>
        </div>

        <div class="footer">
            <p>Riwayat Pesanan</p>
        </div>
    </div>
</body>
</html>
