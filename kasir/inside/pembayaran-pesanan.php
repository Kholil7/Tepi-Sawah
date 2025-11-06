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
                        SET status_pesanan = 'dibayar',
                            metode_bayar = '$metode',
                            total_harga = '$total_harga'
                        WHERE id_pesanan = '$id_pesanan'";
        mysqli_query($conn, $query_update);
        
        
        $query_meja = "UPDATE meja SET status_meja = 'kosong' WHERE id_meja = '$id_meja'";
        mysqli_query($conn, $query_meja);
        
        
        $query_pembayaran = "INSERT INTO pembayaran (id_pesanan, metode, status, jumlah_tagihan, jumlah_dibayar, kembalian, waktu_pembayaran)
                            VALUES ('$id_pesanan', '$metode', 'sudah_bayar', '$total_harga', '$jumlah_dibayar', '$kembalian', '$waktu_bayar')";
        mysqli_query($conn, $query_pembayaran);
        
        mysqli_commit($conn);
        
        // Redirect ke halaman struk dengan data pembayaran
        header("Location: " . $_SERVER['PHP_SELF'] . "?print=1&id_pesanan=$id_pesanan&metode=$metode&jumlah_dibayar=$jumlah_dibayar&kembalian=$kembalian");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error: " . $e->getMessage();
    }
}

// Ambil data untuk print struk
$print_data = null;
if (isset($_GET['print']) && isset($_GET['id_pesanan'])) {
    $id_pesanan = mysqli_real_escape_string($conn, $_GET['id_pesanan']);
    
    $query_print = "SELECT p.*, m.nomor_meja, m.kode_unik
                    FROM pesanan p
                    JOIN meja m ON p.id_meja = m.id_meja
                    WHERE p.id_pesanan = '$id_pesanan'";
    $result_print = mysqli_query($conn, $query_print);
    $print_data = mysqli_fetch_assoc($result_print);
    
    $query_detail_print = "SELECT dp.*, m.nama_menu
                          FROM detail_pesanan dp
                          JOIN menu m ON dp.id_menu = m.id_menu
                          WHERE dp.id_pesanan = '$id_pesanan'";
    $result_detail_print = mysqli_query($conn, $query_detail_print);
    $detail_print = mysqli_fetch_all($result_detail_print, MYSQLI_ASSOC);
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

        /* Input Pembayaran */
        .input-section { margin-bottom: 30px; }
        .input-group { margin-bottom: 20px; }
        .input-label { display: block; font-size: 14px; color: #333; margin-bottom: 8px; font-weight: 500; }
        .input-field {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        .input-field:focus { outline: none; border-color: #4A90E2; }
        .input-field:disabled { background: #f5f5f5; color: #999; cursor: not-allowed; }
        
        .change-display {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .change-label { font-size: 14px; color: #666; }
        .change-amount { font-size: 20px; font-weight: 600; color: #4A90E2; }

        
        .action-buttons { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-confirm {
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
            background: #4A90E2;
            color: white;
            border: none;
        }
        .btn-confirm:hover { background: #357ABD; }
        .btn-confirm:disabled { background: #ccc; cursor: not-allowed; }

        .empty-state { text-align: center; padding: 80px 20px; color: #999; }

        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Struk Print */
        .receipt-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .receipt-container {
            background: white;
            width: 350px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .receipt-content {
            padding: 30px;
            font-family: 'Courier New', monospace;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
        }
        .receipt-header h2 { font-size: 20px; margin-bottom: 5px; }
        .receipt-header p { font-size: 12px; color: #666; margin: 2px 0; }
        .receipt-info { margin-bottom: 15px; font-size: 13px; }
        .receipt-info div { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .receipt-items { border-top: 1px dashed #333; border-bottom: 1px dashed #333; padding: 15px 0; margin-bottom: 15px; }
        .receipt-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
        .receipt-total { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
        .receipt-total, .receipt-paid, .receipt-change { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .receipt-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px dashed #333; font-size: 12px; color: #666; }
        .receipt-buttons {
            display: flex;
            gap: 10px;
            padding: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .receipt-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-print-receipt { background: #4A90E2; color: white; }
        .btn-print-receipt:hover { background: #357ABD; }
        .btn-close-receipt { background: #f5f5f5; color: #333; }
        .btn-close-receipt:hover { background: #e0e0e0; }

        @media print {
            body * { visibility: hidden; }
            .receipt-container, .receipt-container * { visibility: visible; }
            .receipt-container { 
                position: absolute; 
                left: 0; 
                top: 0; 
                width: 100%;
                box-shadow: none;
                border-radius: 0;
            }
            .receipt-buttons { display: none; }
        }
        
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
            .btn-confirm {
                font-size: 14px;
                padding: 12px;
            }
            .receipt-container {
                width: 90%;
                max-width: 350px;
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
                
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="id_pesanan" value="<?= $selected_pesanan['id_pesanan'] ?>">
                    <input type="hidden" name="jumlah_dibayar" id="jumlahDibayarHidden">
                    
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
                    
                    <div class="input-section">
                        <div class="input-group">
                            <label class="input-label">Total Tagihan</label>
                            <input type="text" class="input-field" value="Rp <?= number_format($selected_pesanan['total_harga'], 0, ',', '.') ?>" disabled>
                        </div>
                        
                        <div class="input-group">
                            <label class="input-label">Jumlah Pembayaran</label>
                            <input type="number" class="input-field" id="jumlahBayar" placeholder="Masukkan jumlah pembayaran" oninput="hitungKembalian()">
                        </div>
                        
                        <div class="change-display">
                            <span class="change-label">Kembalian</span>
                            <span class="change-amount" id="kembalianDisplay">Rp 0</span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn-confirm" id="btnConfirm" disabled>✓ Konfirmasi Pembayaran</button>
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

    <!-- Receipt Overlay -->
    <?php if ($print_data): ?>
    <div class="receipt-overlay" id="receiptOverlay" style="display: flex;">
        <div class="receipt-container">
            <div class="receipt-content" id="receiptContent">
                <div class="receipt-header">
                    <h2>WARUNG MAKAN</h2>
                    <p>Jl. Contoh No. 123</p>
                    <p>Telp: 0812-3456-7890</p>
                </div>
                
                <div class="receipt-info">
                    <div>
                        <span>No. Pesanan:</span>
                        <span><?= $print_data['kode_unik'] ?></span>
                    </div>
                    <div>
                        <span>Meja:</span>
                        <span><?= $print_data['nomor_meja'] ?></span>
                    </div>
                    <div>
                        <span>Tanggal:</span>
                        <span><?= date('d/m/Y H:i', strtotime($print_data['waktu_pesan'])) ?></span>
                    </div>
                    <div>
                        <span>Kasir:</span>
                        <span>Admin</span>
                    </div>
                </div>
                
                <div class="receipt-items">
                    <?php foreach ($detail_print as $item): ?>
                    <div class="receipt-item">
                        <span><?= $item['nama_menu'] ?> x<?= $item['jumlah'] ?></span>
                        <span>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="receipt-total">
                    <span>TOTAL:</span>
                    <span>Rp <?= number_format($print_data['total_harga'], 0, ',', '.') ?></span>
                </div>
                
                <div class="receipt-paid">
                    <span>Dibayar (<?= strtoupper($_GET['metode']) ?>):</span>
                    <span>Rp <?= number_format($_GET['jumlah_dibayar'], 0, ',', '.') ?></span>
                </div>
                
                <div class="receipt-change">
                    <span>Kembalian:</span>
                    <span>Rp <?= number_format($_GET['kembalian'], 0, ',', '.') ?></span>
                </div>
                
                <div class="receipt-footer">
                    <p>Terima kasih atas kunjungan Anda!</p>
                    <p>Selamat menikmati hidangan</p>
                </div>
            </div>
            
            <div class="receipt-buttons">
                <button class="btn-print-receipt" onclick="printReceipt()">🖨️ Cetak Struk</button>
                <button class="btn-close-receipt" onclick="closeReceipt()">Tutup</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const totalTagihan = <?= $selected_pesanan ? $selected_pesanan['total_harga'] : 0 ?>;
        
        function selectMethod(method, element) {
            document.querySelectorAll('.method-card').forEach(card => card.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('metodeInput').value = method;
            
            // Auto set exact amount for QRIS
            if (method === 'qris') {
                document.getElementById('jumlahBayar').value = totalTagihan;
                hitungKembalian();
            }
        }
        
        function hitungKembalian() {
            const jumlahBayar = parseFloat(document.getElementById('jumlahBayar').value) || 0;
            const kembalian = jumlahBayar - totalTagihan;
            
            const kembalianDisplay = document.getElementById('kembalianDisplay');
            const btnConfirm = document.getElementById('btnConfirm');
            
            if (kembalian >= 0) {
                kembalianDisplay.textContent = 'Rp ' + kembalian.toLocaleString('id-ID');
                kembalianDisplay.style.color = '#4A90E2';
                btnConfirm.disabled = false;
                document.getElementById('jumlahDibayarHidden').value = jumlahBayar;
            } else {
                kembalianDisplay.textContent = 'Rp ' + Math.abs(kembalian).toLocaleString('id-ID') + ' (Kurang)';
                kembalianDisplay.style.color = '#dc3545';
                btnConfirm.disabled = true;
            }
        }
        
        function printReceipt() {
            window.print();
        }
        
        function closeReceipt() {
            window.location.href = '<?= $_SERVER['PHP_SELF'] ?>';
        }
        
        // Auto print on load
        <?php if ($print_data): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        <?php endif; ?>
    </script>
</body>
</html>