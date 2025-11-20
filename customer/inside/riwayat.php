<?php
require '../../database/connect.php';
require '../include/riwayat_f.php';

// Ambil kode meja
$kode_unik = $_GET['kode'] ?? '';
if (empty($kode_unik)) { echo "Kode meja tidak ditemukan."; exit; }

// Ambil data meja
$meja = getMejaByKode($kode_unik, $conn);
if (!$meja) { echo "Data meja tidak ditemukan."; exit; }

// Ambil daftar pesanan
$pesanan = getPesananByMeja($meja['id_meja'], $conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Meja <?= htmlspecialchars($meja['nomor_meja']); ?></title>

    <?php $version = filemtime('../../css/customer/riwayat.css'); ?>
    <link rel="stylesheet" href="../../css/customer/riwayat.css?v=<?= $version; ?>">
</head>

<body>

<header>
    <a href="home.php?kode=<?= urlencode($kode_unik); ?>" class="back-arrow">&#8592;</a>
    <div>
        <h2>Riwayat Pesanan</h2>
        <p>Meja <?= htmlspecialchars($meja['nomor_meja']); ?></p>
    </div>
</header>

<div class="container">

<?php if (empty($pesanan)) : ?>

    <div class="empty">
        <div class="empty-icon">&#128340;</div>
        <h3>Belum ada riwayat pesanan</h3>
        <button class="btn" onclick="window.location.href='menu.php?kode=<?= urlencode($kode_unik); ?>'">
            Pesan Sekarang
        </button>
    </div>

<?php else : ?>

    <h3>Daftar Pesanan</h3>

    <div class="pesanan-list">

        <?php foreach ($pesanan as $p) :
            $status = strtolower($p['status_pesanan']);
            $status_class = in_array($status, ['dibatalkan','selesai','diproses','pending']) ? $status : 'pending';
        ?>

        <div class="pesanan-item">
            <h4><?= htmlspecialchars($p['nama_menu']); ?></h4>

            <p>Jumlah: <?= (int)$p['jumlah']; ?></p>
            <p>Total: Rp <?= number_format($p['total_harga'], 0, ',', '.'); ?></p>
            <p>Status: <span class="status <?= $status_class; ?>"><?= ucfirst($status); ?></span></p>
            <p>Tanggal: <?= htmlspecialchars($p['tanggal']); ?></p>

            <?php if ($status !== 'dibatalkan' && $status !== 'selesai') : ?>
                <button onclick="batalkanPesanan('<?= $p['id_pesanan']; ?>')" class="btn-batalkan">
                    Batalkan
                </button>
            <?php endif; ?>

        </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

</div>

<!-- POPUP KONFIRMASI -->
<div id="popupConfirm" class="popup-overlay">
    <div class="popup-box">
        <h3>Konfirmasi</h3>
        <p>Yakin ingin membatalkan pesanan ini?</p>

        <div class="popup-buttons">
            <button class="btn-cancel" onclick="closePopup()">Batal</button>
            <button class="btn-yes" id="confirmYes">Ya, Batalkan</button>
        </div>
    </div>
</div>

<!-- POPUP SUKSES -->
<div id="successModal" class="popup-overlay">
    <div class="popup-box">
        <h3>Berhasil</h3>
        <p>Pesanan berhasil dibatalkan</p>
        <button class="btn-yes" onclick="location.reload()">Oke</button>
    </div>
</div>

<script>
let selectedId = null;

// Buka popup konfirmasi
function batalkanPesanan(id) {
    selectedId = id;
    document.getElementById("popupConfirm").style.display = "flex";
}

// Tutup popup
function closePopup() {
    document.getElementById("popupConfirm").style.display = "none";
}

// Tombol "YA" mengeksekusi pembatalan
document.getElementById("confirmYes").onclick = function () {

    const urlParams = new URLSearchParams(window.location.search);
    const kode = urlParams.get('kode');

    fetch('../include/batalkan_pesanan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_pesanan=' + selectedId + '&kode=' + kode
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById("popupConfirm").style.display = "none";
            document.getElementById("successModal").style.display = "flex";
        } else {
            alert("Gagal: " + data.message);
        }
    })
    .catch(err => console.error("Error:", err));
};
</script>

</body>
</html>
