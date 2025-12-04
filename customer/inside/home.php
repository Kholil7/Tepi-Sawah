<?php
require '../../database/connect.php';
require '../include/home_f.php';

$kode_unik = $_GET['kode'] ?? '';

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
    <?php $version = filemtime('../../css/customer/home.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/customer/home.css?v=<?php echo $version; ?>">
</head>
<script src="../geofence/geofence.js"></script>
<body>
    <div class="container">
        <div class="header">
            <h2 class="nama-resto">Lesehan Tepi Sawah</h2>
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
            <a href="riwayat.php?kode=<?= urlencode($kode_unik); ?>" class="btn-his">Lihat Pesanan</a>
        </div>
    </div>
</body>
  <script>
    window.onload = function() {
      checkGeofence(function(granted, distance) {
        console.log('Akses diberikan! Jarak: ' + distance + 'm');
      });
    };
  </script>
</html>