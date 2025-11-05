<?php
require '../../database/connect.php';

// ============================
// Total Penjualan Hari Ini & Bulan Ini
// ============================
$q_penjualan_hari = mysqli_query($conn, "
    SELECT SUM(total_harga) AS total 
    FROM pesanan 
    WHERE DATE(waktu_pesan) = CURDATE() AND status_pesanan = 'selesai'
");
$total_penjualan_hari_ini = mysqli_fetch_assoc($q_penjualan_hari)['total'] ?? 0;

$q_penjualan_bulan = mysqli_query($conn, "
    SELECT SUM(total_harga) AS total 
    FROM pesanan 
    WHERE MONTH(waktu_pesan) = MONTH(CURDATE()) 
      AND YEAR(waktu_pesan) = YEAR(CURDATE())
      AND status_pesanan = 'selesai'
");
$total_penjualan_bulan_ini = mysqli_fetch_assoc($q_penjualan_bulan)['total'] ?? 0;

// ============================
// Total Pembelian Bahan
// ============================
$q_pembelian_hari = mysqli_query($conn, "
    SELECT SUM(harga) AS total 
    FROM pembelian_bahan 
    WHERE DATE(tanggal_beli) = CURDATE()
");
$total_pembelian_hari_ini = mysqli_fetch_assoc($q_pembelian_hari)['total'] ?? 0;

$q_pembelian_bulan = mysqli_query($conn, "
    SELECT SUM(harga) AS total 
    FROM pembelian_bahan 
    WHERE MONTH(tanggal_beli) = MONTH(CURDATE()) 
      AND YEAR(tanggal_beli) = YEAR(CURDATE())
");
$total_pembelian_bulan_ini = mysqli_fetch_assoc($q_pembelian_bulan)['total'] ?? 0;

// ============================
// Status Meja
// ============================
$q_meja_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM meja");
$status_meja_total = mysqli_fetch_assoc($q_meja_total)['total'] ?? 0;

$q_meja_terisi = mysqli_query($conn, "SELECT COUNT(*) AS total FROM meja WHERE status_meja = 'terisi'");
$status_meja_terisi = mysqli_fetch_assoc($q_meja_terisi)['total'] ?? 0;

$q_meja_menunggu = mysqli_query($conn, "SELECT COUNT(*) AS total FROM meja WHERE status_meja = 'menunggu_pembayaran'");
$status_meja_menunggu = mysqli_fetch_assoc($q_meja_menunggu)['total'] ?? 0;

// ============================
// Menu Terlaris Hari Ini
// ============================
$q_terlaris = mysqli_query($conn, "
    SELECT m.nama_menu, SUM(d.jumlah) AS total_jual
    FROM detail_pesanan d
    JOIN menu m ON d.id_menu = m.id_menu
    JOIN pesanan p ON p.id_pesanan = d.id_pesanan
    WHERE DATE(p.waktu_pesan) = CURDATE() 
      AND p.status_pesanan = 'selesai'
    GROUP BY m.nama_menu
    ORDER BY total_jual DESC
    LIMIT 1
");
if ($row = mysqli_fetch_assoc($q_terlaris)) {
    $menu_terlaris = $row['nama_menu'];
    $menu_terlaris_jumlah = $row['total_jual'];
} else {
    $menu_terlaris = "-";
    $menu_terlaris_jumlah = 0;
}

// ============================
// Data Penjualan Mingguan
// ============================
$q_mingguan = mysqli_query($conn, "
    SELECT DAYOFWEEK(waktu_pesan) AS hari, SUM(total_harga) AS total
    FROM pesanan
    WHERE YEARWEEK(waktu_pesan, 1) = YEARWEEK(CURDATE(), 1)
      AND status_pesanan = 'selesai'
    GROUP BY hari
");
$penjualan_mingguan = array_fill(0, 7, 0);
while ($r = mysqli_fetch_assoc($q_mingguan)) {
    $penjualan_mingguan[$r['hari'] - 1] = (float)$r['total'];
}

// ============================
// Data Kategori Menu
// ============================
$q_kategori = mysqli_query($conn, "
    SELECT kategori, COUNT(*) AS jumlah 
    FROM menu 
    GROUP BY kategori
");
$kategori = [];
while ($r = mysqli_fetch_assoc($q_kategori)) {
    $kategori[ucfirst($r['kategori'])] = (int)$r['jumlah'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Restoran</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f9fb;
            margin: 0;
            padding: 20px;
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        .card h4 { margin: 0; color: #555; font-size: 15px; font-weight: 500; }
        .card h2 { margin: 5px 0; font-size: 28px; color: #1a73e8; }
        .subtext { color: #777; font-size: 13px; }
        .main-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
            margin-top: 25px;
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        .chart-container h3 { margin-bottom: 15px; font-size: 17px; color: #333; }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="card">
        <h4>Total Penjualan Hari Ini</h4>
        <h2>Rp <?= number_format($total_penjualan_hari_ini, 0, ',', '.'); ?></h2>
        <p class="subtext">Bulan ini: Rp <?= number_format($total_penjualan_bulan_ini, 0, ',', '.'); ?></p>
    </div>
    <div class="card">
        <h4>Total Pembelian Bahan</h4>
        <h2 style="color:#00b871;">Rp <?= number_format($total_pembelian_hari_ini, 0, ',', '.'); ?></h2>
        <p class="subtext">Bulan ini: Rp <?= number_format($total_pembelian_bulan_ini, 0, ',', '.'); ?></p>
    </div>
    <div class="card">
        <h4>Status Meja</h4>
        <h2 style="color:#f39c12;"><?= $status_meja_terisi; ?> / <?= $status_meja_total; ?></h2>
        <p class="subtext">Terisi • <?= $status_meja_menunggu; ?> menunggu bayar</p>
    </div>
    <div class="card">
        <h4>Menu Terlaris</h4>
        <h2 style="color:#a259ff;"><?= $menu_terlaris; ?></h2>
        <p class="subtext"><?= $menu_terlaris_jumlah; ?> porsi hari ini</p>
    </div>
</div>

<div class="main-section">
    <div class="chart-container">
        <h3>Penjualan Mingguan</h3>
        <canvas id="chartPenjualan"></canvas>
    </div>

    <div class="chart-container">
        <h3>Kategori Menu</h3>
        <canvas id="chartKategori"></canvas>
    </div>
</div>

<script>
const ctxPenjualan = document.getElementById('chartPenjualan').getContext('2d');
new Chart(ctxPenjualan, {
    type: 'bar',
    data: {
        labels: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
        datasets: [{
            label: 'Penjualan',
            data: <?= json_encode($penjualan_mingguan); ?>,
            backgroundColor: '#3b82f6',
            borderRadius: 8
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: (v) => 'Rp ' + v.toLocaleString('id-ID') }
            }
        },
        plugins: { legend: { display: false } }
    }
});

const ctxKategori = document.getElementById('chartKategori').getContext('2d');
new Chart(ctxKategori, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_keys($kategori)); ?>,
        datasets: [{
            data: <?= json_encode(array_values($kategori)); ?>,
            backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#a259ff']
        }]
    },
    options: { plugins: { legend: { position: 'right' } } }
});
</script>

</body>
</html>
