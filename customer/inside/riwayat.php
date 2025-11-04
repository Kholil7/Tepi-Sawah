<?php
require 'riwayat_pesanan_function.php'; // panggil fungsi

$kode_unik = $_GET['kode_unik'] ?? '';
if (empty($kode_unik)) {
    echo "Kode meja tidak ditemukan.";
    exit;
}

$meja = getMejaByKode($kode_unik, $conn);
if (!$meja) {
    echo "Data meja tidak ditemukan.";
    exit;
}

$pesanan = getPesananByMeja($meja['id_meja'], $conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - <?= htmlspecialchars($meja['nama_meja']); ?></title>
    <link rel="stylesheet" href="riwayat_pesanan_style.css">
</head>
<body>

<header>
    <a href="index.php" class="back-arrow">&#8592;</a>
    <div>
        <h2>Riwayat Pesanan</h2>
        <p><?= htmlspecialchars($meja['nama_meja']); ?></p>
    </div>
</header>

<div class="container">
<?php if (empty($pesanan)) : ?>
    <div class="empty">
        <div class="empty-icon">&#128340;</div>
        <h3>Belum ada riwayat pesanan</h3>
        <button class="btn" onclick="window.location.href='menu.php?kode_unik=<?= urlencode($kode_unik); ?>'">
            Pesan Sekarang
        </button>
    </div>
<?php else : ?>
    <h3>Daftar Pesanan</h3>
    <div class="pesanan-list">
        <?php foreach ($pesanan as $p) : ?>
            <div class="pesanan-item">
                <h4><?= htmlspecialchars($p['nama_menu']); ?></h4>
                <p>Jumlah: <?= $p['jumlah']; ?></p>
                <p>Total: Rp <?= number_format($p['total_harga'], 0, ',', '.'); ?></p>
                <p>Tanggal: <?= $p['tanggal']; ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

</body>
</html>
