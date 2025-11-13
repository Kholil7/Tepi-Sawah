<?php

$koneksi = new mysqli("localhost", "root", "", "dbresto_app");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$qSummary = $koneksi->query("
    SELECT 
        COUNT(*) AS total_transaksi,
        SUM(total_harga) AS total_pendapatan,
        SUM(CASE WHEN metode_bayar = 'qris' THEN 1 ELSE 0 END) AS total_qris,
        SUM(CASE WHEN metode_bayar = 'cash' THEN 1 ELSE 0 END) AS total_cash
    FROM pesanan
    WHERE status_pesanan = 'dibayar'
");

$summary = $qSummary->fetch_assoc();

$qTransaksi = $koneksi->query("
    SELECT 
        p.id_pesanan, p.id_meja, p.waktu_pesan, p.metode_bayar, p.total_harga,
        GROUP_CONCAT(CONCAT(m.nama_menu, ' x', dp.jumlah) SEPARATOR ', ') AS item_pesanan
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN menu m ON dp.id_menu = m.id_menu
    WHERE p.status_pesanan = 'dibayar'
    GROUP BY p.id_pesanan
    ORDER BY p.waktu_pesan ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Restoran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fc; }
        .card-summary { border-radius: 15px; }
        .table-rounded { border-radius: 15px; overflow: hidden; }
        .badge-qris { background-color: #ffeeba; color: #856404; }
        .badge-cash { background-color: #cce5ff; color: #004085; }
        
    
        .search-filter-container {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 35px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .filter-btn {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: white;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body class="p-4">

    <div class="container-fluid">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card card-summary p-3 text-center shadow-sm">
                    <h6>Total Transaksi</h6>
                    <h4><?= $summary['total_transaksi'] ?></h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-summary p-3 text-center shadow-sm">
                    <h6>Total Pendapatan</h6>
                    <h4>Rp <?= number_format($summary['total_pendapatan'], 0, ',', '.') ?></h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-summary p-3 text-center shadow-sm">
                    <h6>Pembayaran QRIS</h6>
                    <h4><?= $summary['total_qris'] ?> transaksi</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-summary p-3 text-center shadow-sm">
                    <h6>Pembayaran Cash</h6>
                    <h4><?= $summary['total_cash'] ?> transaksi</h4>
                </div>
            </div>
        </div>

    
        <div class="search-filter-container">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari menu atau nomor meja...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterMeja" class="form-select filter-btn">
                        <option value="">🔽 Semua Meja</option>
                        <?php
                        
                        $qMeja = $koneksi->query("SELECT DISTINCT id_meja FROM pesanan WHERE status_pesanan = 'dibayar' ORDER BY id_meja");
                        while($meja = $qMeja->fetch_assoc()) {
                            echo "<option value='{$meja['id_meja']}'>Meja {$meja['id_meja']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterMetode" class="form-select filter-btn">
                        <option value="">📅 Semua Metode</option>
                        <option value="qris">QRIS</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive table-rounded">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Meja</th>
                                <th>Item Pesanan</th>
                                <th>Metode</th>
                                <th>Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php while($row = $qTransaksi->fetch_assoc()) : ?>
                            <tr data-meja="<?= $row['id_meja'] ?>" data-metode="<?= $row['metode_bayar'] ?>">
                                <td>
                                    <?= date('H:i', strtotime($row['waktu_pesan'])) ?><br>
                                    <small><?= date('d/m/Y', strtotime($row['waktu_pesan'])) ?></small>
                                </td>
                                <td><span class="badge bg-warning text-dark"><?= $row['id_meja'] ?></span></td>
                                <td class="item-pesanan"><?= $row['item_pesanan'] ?></td>
                                <td>
                                    <?php if($row['metode_bayar'] == 'qris') : ?>
                                        <span class="badge badge-qris">QRIS</span>
                                    <?php else : ?>
                                        <span class="badge badge-cash">Cash</span>
                                    <?php endif; ?>
                                </td>
                                <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-receipt"></i></button>
                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-clipboard"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        
        document.getElementById('searchInput').addEventListener('keyup', function() {
            filterTable();
        });

        
        document.getElementById('filterMeja').addEventListener('change', function() {
            filterTable();
        });

       
        document.getElementById('filterMetode').addEventListener('change', function() {
            filterTable();
        });

        
        function filterTable() {
            let searchValue = document.getElementById('searchInput').value.toLowerCase();
            let mejaValue = document.getElementById('filterMeja').value;
            let metodeValue = document.getElementById('filterMetode').value;
            
            let rows = document.querySelectorAll('#tableBody tr');
            
            rows.forEach(row => {
                
                let allText = row.textContent.toLowerCase();
                let itemPesanan = row.querySelector('.item-pesanan').textContent.toLowerCase();
                let meja = row.getAttribute('data-meja');
                let metode = row.getAttribute('data-metode');
                
                
                let matchSearch = searchValue === '' || 
                                 allText.includes(searchValue) || 
                                 itemPesanan.includes(searchValue) || 
                                 meja.includes(searchValue);
                
                
                let matchMeja = mejaValue === '' || meja === mejaValue;
                
                
                let matchMetode = metodeValue === '' || metode === metodeValue;
                
               
                if (matchSearch && matchMeja && matchMetode) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>

</body>
</html>