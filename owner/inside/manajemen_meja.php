<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../database/connect.php';
include '../../sidebar/sidebar.php';

$query = $conn->query("SELECT * FROM meja ORDER BY nomor_meja ASC");
$meja = $query->fetch_all(MYSQLI_ASSOC);

$total_meja = count($meja);
$kosong = count(array_filter($meja, fn($m) => $m['status_meja'] === 'kosong'));
$terisi = count(array_filter($meja, fn($m) => $m['status_meja'] === 'terisi'));
$menunggu = count(array_filter($meja, fn($m) => $m['status_meja'] === 'menunggu_pembayaran'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Meja</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f9fafb;
        display: flex;
    }
    main {
        flex: 1;
        padding: 20px;
        margin-left: 250px;
        transition: all 0.3s ease;
        margin-top: 50px;
    }
    @media (max-width: 768px) {
        main {
            margin-left: 0;
            padding: 10px;
        }
    }
    h2 {
        margin-bottom: 5px;
        color: #111827;
    }
    p {
        margin-top: 0;
        color: #6b7280;
    }
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }
    .stat-card h3 {
        margin: 0;
        color: #4b5563;
    }
    .stat-card p {
        font-size: 1.8em;
        font-weight: bold;
        margin-top: 10px;
    }
    .green { color: #10b981; }
    .blue { color: #3b82f6; }
    .orange { color: #f59e0b; }
    .legend {
        display: flex;
        align-items: center;
        gap: 20px;
        margin: 20px 0;
        flex-wrap: wrap;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .color-box {
        width: 16px;
        height: 16px;
        border-radius: 3px;
    }
    .bg-green { background: #10b981; }
    .bg-blue { background: #3b82f6; }
    .bg-orange { background: #f59e0b; }
    .meja-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 15px;
    }
    .meja-card {
        padding: 20px;
        border-radius: 10px;
        color: white;
        text-align: center;
        font-weight: bold;
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        cursor: default;
        transition: transform 0.2s;
    }
    .meja-card:hover {
        transform: scale(1.05);
    }
    .kosong { background-color: #10b981; }
    .terisi { background-color: #3b82f6; }
    .menunggu_pembayaran { background-color: #f59e0b; }
    @media (max-width: 600px) {
        .meja-card {
            font-size: 0.9em;
            padding: 15px;
        }
    }
</style>
</head>
<body>
<main>
    <h2>Manajemen Meja</h2>
    <p>Pantau status meja restoran</p>
    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Meja</h3>
            <p><?= $total_meja ?></p>
        </div>
        <div class="stat-card">
            <h3>Kosong</h3>
            <p class="green"><?= $kosong ?></p>
        </div>
        <div class="stat-card">
            <h3>Terisi</h3>
            <p class="blue"><?= $terisi ?></p>
        </div>
        <div class="stat-card">
            <h3>Menunggu Bayar</h3>
            <p class="orange"><?= $menunggu ?></p>
        </div>
    </div>
    <div class="legend">
        <div class="legend-item"><div class="color-box bg-green"></div> Kosong</div>
        <div class="legend-item"><div class="color-box bg-blue"></div> Terisi</div>
        <div class="legend-item"><div class="color-box bg-orange"></div> Menunggu Bayar</div>
    </div>
    <div class="meja-grid">
        <?php foreach ($meja as $m): ?>
            <div class="meja-card <?= $m['status_meja'] ?>">
                <div>#<?= htmlspecialchars($m['nomor_meja']) ?></div>
                <div style="margin-top:8px; font-size:0.9em;">
                    <?= ucwords(str_replace('_', ' ', $m['status_meja'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>
