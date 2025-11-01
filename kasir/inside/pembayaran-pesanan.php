<?php

require '../../database/connect.php';

$query_pesanan = "SELECT p.*, m.nomor_meja, m.kode_unik,
                  (SELECT COUNT(*) FROM detail_pesanan WHERE id_pesanan = p.id_pesanan) as total_item
                  FROM pesanan p
                  JOIN meja m ON p.id_meja = m.id_meja
                  WHERE p.status_pesanan = 'menunggu'
                  ORDER BY p.waktu_pesan DESC";
$result_pesanan = mysqli_query($conn, $query_pesanan);
$pesanan_list = mysqli_fetch_all($result_pesanan, MYSQLI_ASSOC);


$detail_pesanan = [];
$selected_pesanan = null;

if (isset($_GET['id_pesanan'])) {
    $id_pesanan = mysqli_real_escape_string($conn, $_GET['id_pesanan']);
    

    $query_selected = "SELECT p.*, m.nomor_meja, m.kode_unik
                       FROM pesanan p
                       JOIN meja m ON p.id_meja = m.id_meja
                       WHERE p.id_pesanan = '$id_pesanan'";
    $result_selected = mysqli_query($conn, $query_selected);
    $selected_pesanan = mysqli_fetch_assoc($result_selected);
    
   
    $query_detail = "SELECT dp.*, m.nama_menu
                     FROM detail_pesanan dp
                     JOIN menu m ON dp.id_menu = m.id_menu
                     WHERE dp.id_pesanan = '$id_pesanan'";
    $result_detail = mysqli_query($conn, $query_detail);
    $detail_pesanan = mysqli_fetch_all($result_detail, MYSQLI_ASSOC);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($conn);
    
    try {
        $id_pesanan = mysqli_real_escape_string($conn, $_POST['id_pesanan']);
        $metode = mysqli_real_escape_string($conn, $_POST['metode']);
        $jumlah_dibayar = mysqli_real_escape_string($conn, $_POST['jumlah_dibayar']);
        
      
        $query_total = "SELECT total_harga, id_meja FROM pesanan WHERE id_pesanan = '$id_pesanan'";
        $result_total = mysqli_query($conn, $query_total);
        $pesanan = mysqli_fetch_assoc($result_total);
        $total_harga = $pesanan['total_harga'];
        $id_meja = $pesanan['id_meja'];
        
        
        $kembalian = $jumlah_dibayar - $total_harga;
        
        if ($kembalian < 0) {
            throw new Exception("Jumlah pembayaran kurang!");
        }
        
        
        $waktu_bayar = date('Y-m-d H:i:s');
        $query_update = "UPDATE pesanan 
                        SET status_pesanan = 'sudah_bayar',
                            metode_bayar = '$metode',
                            jumlah_dibayar = '$jumlah_dibayar',
                            kembalian = '$kembalian',
                            waktu_pembayaran = '$waktu_bayar'
                        WHERE id_pesanan = '$id_pesanan'";
        mysqli_query($conn, $query_update);
        
        
        $query_meja = "UPDATE meja SET status_meja = 'kosong' WHERE id_meja = '$id_meja'";
        mysqli_query($conn, $query_meja);
        
        
        $query_pembayaran = "INSERT INTO pembayaran (id_pesanan, metode, status, jumlah_tagihan, jumlah_dibayar, kembalian, waktu_pembayaran)
                            VALUES ('$id_pesanan', '$metode', 'sudah_bayar', '$total_harga', '$jumlah_dibayar', '$kembalian', '$waktu_bayar')";
        mysqli_query($conn, $query_pembayaran);
        
        mysqli_commit($conn);
        
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Dashboard Kasir</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }

        
        .container {
            display: flex;
            flex-wrap: wrap;
            padding: 20px;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        
        .left-panel {
            width: 280px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            height: fit-content;
        }

        .left-panel h2 {
            font-size: 15px;
            margin-bottom: 20px;
            color: #333;
            font-weight: 600;
        }

        .order-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }

        .order-card:hover { border-color: #FFB84D; }
        .order-card.active { border-color: #FFC864; background: #fffbf0; }

        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .order-header h3 { font-size: 15px; color: #333; }

        .badge-menunggu {
            background: #FFC864;
            color: #333;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .order-code { color: #999; font-size: 13px; margin-bottom: 12px; }
        .order-footer { display: flex; justify-content: space-between; align-items: center; }
        .order-time { color: #666; font-size: 13px; }
        .order-price { color: #333; font-weight: 600; font-size: 14px; }

        
        .right-panel {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 30px;
        }

        .form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .form-title-section h1 { font-size: 18px; color: #333; margin-bottom: 5px; }
        .form-meta { display: flex; gap: 10px; color: #666; font-size: 14px; }
        .badge-bayar { background: #FFC864; color: #333; padding: 8px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; }

        
        .items-section { margin-bottom: 25px; }
        .item-row { display: flex; justify-content: space-between; padding: 12px 0; color: #333; }
        .item-name { font-size: 15px; }
        .item-price { font-weight: 600; font-size: 15px; }

        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            margin-bottom: 30px;
        }

        .total-label { font-size: 16px; color: #333; }
        .total-amount { font-size: 24px; font-weight: 600; color: #FFC864; }

        
        .payment-section h3 { font-size: 15px; margin-bottom: 15px; color: #333; }
        .method-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }

        .method-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
        }

        .method-card:hover { border-color: #4A90E2; }
        .method-card.selected { border-color: #4A90E2; background: #f0f7ff; }

        .method-icon { font-size: 24px; margin-bottom: 8px; }
        .method-title { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 3px; }
        .method-desc { font-size: 13px; color: #666; }

        
        .action-buttons { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-confirm, .btn-print {
            flex: 1;
            padding: 15px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-confirm { background: #4A90E2; color: white; border: none; }
        .btn-confirm:hover { background: #357ABD; }
        .btn-print { background: white; border: 2px solid #e0e0e0; color: #333; }
        .btn-print:hover { border-color: #4A90E2; }

        .empty-state { text-align: center; padding: 80px 20px; color: #999; }

        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
                padding: 10px;
            }
            .left-panel {
                width: 100%;
                order: 2;
            }
            .right-panel {
                width: 100%;
                order: 1;
                padding: 20px;
            }
            .method-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 600px) {
            .form-title-section h1 {
                font-size: 16px;
            }
            .badge-bayar {
                padding: 6px 12px;
                font-size: 12px;
            }
            .total-amount {
                font-size: 20px;
            }
            .btn-confirm, .btn-print {
                font-size: 14px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="left-panel">
            <h2>Pesanan Menunggu Pembayaran</h2>
            
            <?php if (empty($pesanan_list)): ?>
                <p style="text-align: center; color: #999; padding: 20px; font-size: 13px;">Tidak ada pesanan</p>
            <?php else: ?>
                <?php foreach ($pesanan_list as $pesanan): ?>
                    <a href="?id_pesanan=<?= $pesanan['id_pesanan'] ?>" class="order-card <?= isset($_GET['id_pesanan']) && $_GET['id_pesanan'] == $pesanan['id_pesanan'] ? 'active' : '' ?>">
                        <div class="order-header">
                            <h3>Meja <?= $pesanan['nomor_meja'] ?></h3>
                            <span class="badge-menunggu">Menunggu</span>
                        </div>
                        <div class="order-code"><?= $pesanan['kode_unik'] ?></div>
                        <div class="order-footer">
                            <span class="order-time"><?= date('H:i', strtotime($pesanan['waktu_pesan'])) ?></span>
                            <span class="order-price">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        
        <div class="right-panel">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <span>✓</span><span>Pembayaran berhasil diproses!</span>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <span>⚠</span><span><?= $error_message ?></span>
                </div>
            <?php endif; ?>

            <?php if ($selected_pesanan): ?>
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 16px; color: #333; font-weight: 600;">Form Pembayaran</h2>
                </div>
                
                <div class="form-header">
                    <div class="form-title-section">
                        <h1>Meja <?= $selected_pesanan['nomor_meja'] ?></h1>
                        <div class="form-meta">
                            <span><?= $selected_pesanan['kode_unik'] ?></span>
                            <span>•</span>
                            <span><?= date('H:i', strtotime($selected_pesanan['waktu_pesan'])) ?></span>
                        </div>
                    </div>
                    <span class="badge-bayar">Menunggu Bayar</span>
                </div>
                
                <div class="items-section">
                    <?php foreach ($detail_pesanan as $item): ?>
                        <div class="item-row">
                            <span class="item-name"><?= $item['nama_menu'] ?> x<?= $item['jumlah'] ?></span>
                            <span class="item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="total-row">
                    <span class="total-label">Total</span>
                    <span class="total-amount">Rp <?= number_format($selected_pesanan['total_harga'], 0, ',', '.') ?></span>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="id_pesanan" value="<?= $selected_pesanan['id_pesanan'] ?>">
                    <input type="hidden" name="jumlah_dibayar" value="<?= $selected_pesanan['total_harga'] ?>">
                    
                    <div class="payment-section">
                        <h3>Metode Pembayaran</h3>
                        <div class="method-grid">
                            <div class="method-card selected" onclick="selectMethod('qris', this)">
                                <div class="method-icon">📱</div>
                                <div class="method-title">QRIS</div>
                                <div class="method-desc">Scan QR Code</div>
                            </div>
                            <div class="method-card" onclick="selectMethod('cash', this)">
                                <div class="method-icon">💵</div>
                                <div class="method-title">Cash</div>
                                <div class="method-desc">Tunai</div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="metode" id="metodeInput" value="qris">
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn-confirm">✓ Konfirmasi Pembayaran</button>
                        <button type="button" class="btn-print" onclick="cetakStruk()">🖨️ Cetak Struk</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #666;">Pilih Pesanan</h3>
                    <p>Pilih pesanan dari sidebar untuk memproses pembayaran</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function selectMethod(method, element) {
            document.querySelectorAll('.method-card').forEach(card => card.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('metodeInput').value = method;
        }
        function cetakStruk() {
            alert('Fitur cetak struk akan segera tersedia!');
        }
    </script>
</body>
</html>
