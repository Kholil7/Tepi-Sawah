<?php
require '../../database/connect.php';
require '../include/riwayat_f.php';

// Ambil kode meja dari URL (contoh: ?kode=KODE-M01-ABC123)
$kode_unik = $_GET['kode'] ?? '';

if (empty($kode_unik)) {
    echo "Kode meja tidak ditemukan.";
    exit;
}

// Ambil data meja
$meja = getMejaByKode($kode_unik, $conn);
if (!$meja) {
    echo "Data meja tidak ditemukan.";
    exit;
}

// Ambil daftar pesanan berdasarkan id_meja
$pesanan = getPesananByMeja($meja['id_meja'], $conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Meja <?= htmlspecialchars($meja['nomor_meja']); ?></title>
    <?php $version = filemtime('../../css/customer/riwayat.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/customer/riwayat.css?v=<?php echo $version; ?>">
</head>
<script>
function batalkanPesanan(idPesanan) {
    if(confirm('Yakin ingin membatalkan pesanan ini?')) {
        const urlParams = new URLSearchParams(window.location.search);
        const kode = urlParams.get('kode');
        
        fetch('../include/batalkan_pesanan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id_pesanan=' + idPesanan + '&kode=' + kode
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Pesanan berhasil dibatalkan');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan');
            console.error(error);
        });
    }
}
</script>
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
    <!-- <h3>Daftar Pesanan</h3> -->
<div class="pesanan-list">
    <?php 
    if (empty($pesanan)) {
        echo '<p class="no-data">Belum ada riwayat pesanan.</p>';
    } else {
        // Group pesanan berdasarkan id_pesanan
        $grouped_pesanan = [];
        foreach ($pesanan as $p) {
            $id = $p['id_pesanan'];
            if (!isset($grouped_pesanan[$id])) {
                $grouped_pesanan[$id] = [
                    'id_pesanan' => $p['id_pesanan'],
                    'waktu_pesan' => $p['tanggal'],
                    'status_pesanan' => $p['status_pesanan'],
                    'total_harga' => $p['total_harga'],
                    'jenis_pesanan' => $p['jenis_pesanan'],
                    'metode_bayar' => $p['metode_bayar'],
                    'catatan' => $p['catatan'],
                    'items' => []
                ];
            }
            // Tambahkan detail menu jika ada
            if (!empty($p['nama_menu'])) {
                $grouped_pesanan[$id]['items'][] = [
                    'nama_menu' => $p['nama_menu'],
                    'jumlah' => $p['jumlah'],
                    'harga_satuan' => $p['harga_satuan']
                ];
            }
        }
        
        foreach ($grouped_pesanan as $pesanan_data) : 
            $status = strtolower($pesanan_data['status_pesanan']);
            $status_class = in_array($status, ['dibatalkan','selesai','diproses','menunggu']) ? $status : 'menunggu';
        ?>
            <div class="pesanan-item">
                <h4>Pesanan #<?= htmlspecialchars($pesanan_data['id_pesanan']); ?></h4>
                
                <?php if (!empty($pesanan_data['items'])) : ?>
                    <div class="menu-items">
                        <?php 
                        $no = 1;
                        foreach ($pesanan_data['items'] as $item) : ?>
                            <p><?= $no; ?>. <?= htmlspecialchars($item['nama_menu']); ?> 
                               - <?= (int)$item['jumlah']; ?> porsi 
                               (Rp <?= number_format($item['harga_satuan'], 0, ',', '.'); ?>/porsi)
                            </p>
                        <?php 
                        $no++;
                        endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <p><strong>Total: Rp <?= number_format($pesanan_data['total_harga'], 0, ',', '.'); ?></strong></p>
                <p>Jenis: <?= htmlspecialchars($pesanan_data['jenis_pesanan']); ?></p>
                <p>Metode Bayar: <?= htmlspecialchars($pesanan_data['metode_bayar'] ?? '-'); ?></p>
                <p>Status: <span class="status <?= $status_class; ?>"><?= ucfirst($status); ?></span></p>
                <p>Tanggal: <?= date('d/m/Y H:i', strtotime($pesanan_data['waktu_pesan'])); ?></p>
                
                <?php if (!empty($pesanan_data['catatan'])) : ?>
                    <p>Catatan: <em><?= htmlspecialchars($pesanan_data['catatan']); ?></em></p>
                <?php endif; ?>
                
                <?php if ($status !== 'selesai' && $status !== 'dibatalkan') : ?>
                    <button onclick="batalkanPesanan('<?= $pesanan_data['id_pesanan']; ?>')" class="btn-batalkan">
                        Batalkan
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; 
    } ?>
</div>
<?php endif; ?>
</div>

</body>
</html>
