<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();
require '../../database/connect.php';

$query_pesanan = "SELECT p.*, m.nomor_meja, m.kode_unik,
                 (SELECT COUNT(*) FROM detail_pesanan WHERE id_pesanan = p.id_pesanan) as total_item
                 FROM pesanan p
                 JOIN meja m ON p.id_meja = m.id_meja
                 JOIN pembayaran pb ON p.id_pesanan = pb.id_pesanan
                 WHERE p.status_pesanan = 'disajikan' 
                 AND pb.metode = 'cash'
                 AND pb.status = 'belum_bayar'
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
        $jumlah_dibayar = (float)mysqli_real_escape_string($conn, $_POST['jumlah_dibayar']); 
        
        $query_total = "SELECT total_harga, id_meja FROM pesanan WHERE id_pesanan = '$id_pesanan'";
        $result_total = mysqli_query($conn, $query_total);
        $pesanan = mysqli_fetch_assoc($result_total);
        $total_harga = (float)$pesanan['total_harga'];
        $id_meja = $pesanan['id_meja'];
        
        $kembalian = $jumlah_dibayar - $total_harga;
        
        if ($kembalian < 0) {
            throw new Exception("Jumlah pembayaran kurang dari total tagihan.");
        }
    
        $waktu_bayar = date('Y-m-d H:i:s');
        
        $query_update_pesanan = "UPDATE pesanan 
                                 SET status_pesanan = 'selesai',
                                     metode_bayar = '$metode',
                                     total_harga = '$total_harga'
                                 WHERE id_pesanan = '$id_pesanan'";
        mysqli_query($conn, $query_update_pesanan);
        
        $query_meja = "UPDATE meja SET status_meja = 'kosong' WHERE id_meja = '$id_meja'";
        mysqli_query($conn, $query_meja);
        
        $query_pembayaran = "UPDATE pembayaran 
                             SET status = 'sudah_bayar',
                                 metode = '$metode',
                                 waktu_pembayaran = '$waktu_bayar',
                                 bayar = '$jumlah_dibayar', 
                                 kembalian = '$kembalian' 
                             WHERE id_pesanan = '$id_pesanan'";
        
        if (!mysqli_query($conn, $query_pembayaran)) {
             throw new Exception("Gagal menyimpan data pembayaran. Pastikan kolom 'bayar' dan 'kembalian' ada di tabel pembayaran. MySQL Error: " . mysqli_error($conn));
        }
        
        mysqli_commit($conn);
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?print=1&id_pesanan=$id_pesanan&metode=$metode&jumlah_dibayar=$jumlah_dibayar&kembalian=$kembalian");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error: " . $e->getMessage();
    }
}

$print_data = null;
$detail_print = [];
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
    <?php $version = filemtime('../../css/kasir/pembayaran-kasir.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/kasir/pembayaran-kasir.css?v=<?php echo $version; ?>">
    <title>Pembayaran - Dashboard Kasir</title>
</head>
<body>
    <?php include '../../sidebar/sidebar_kasir.php'; ?>
    
    <div class="main-content">
        <div class="container">
            
            <div class="left-panel">
                <h2>Pesanan Siap Dibayar</h2>
                
                <?php if (empty($pesanan_list)): ?>
                    <p style="text-align: center; color: #999; padding: 20px; font-size: 13px;">Tidak ada pesanan</p>
                <?php else: ?>
                    <?php foreach ($pesanan_list as $pesanan): ?>
                        <a href="?id_pesanan=<?= $pesanan['id_pesanan'] ?>" class="order-card <?= isset($_GET['id_pesanan']) && $_GET['id_pesanan'] == $pesanan['id_pesanan'] ? 'active' : '' ?>">
                            <div class="order-header">
                                <h3>Meja <?= $pesanan['nomor_meja'] ?></h3>
                                <span class="badge-disajikan">Disajikan</span>
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
                                <div class="method-card selected" onclick="selectMethod('cash', this)">
                                    <div class="method-icon">💵</div>
                                    <div class="method-title">Cash</div>
                                    <div class="method-desc">Tunai</div>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="metode" id="metodeInput" value="cash">
                        
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
    </div>

    <?php if (isset($error_message)): ?>
    <div class="notification-popup" id="notificationPopup" style="display: flex;">
        <div class="notification-content">
            <div class="notification-icon error">
                ⚠️
            </div>
            <h2 class="notification-title error">Pembayaran Gagal!</h2>
            <p class="notification-message"><?= htmlspecialchars($error_message) ?></p>
            <button class="notification-button" onclick="closeNotification('notificationPopup')">Tutup</button>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($print_data): ?>
    <div class="notification-popup" id="successPopup" style="display: none;">
        <div class="notification-content">
            <div class="notification-icon success">
                ✓
            </div>
            <h2 class="notification-title success">Pembayaran Berhasil!</h2>
            <p class="notification-message">
                Lanjut Untuk Cetak Struk<br>
            </p>
            <button class="notification-button" onclick="showReceipt()">Cetak Struk</button>
        </div>
    </div>

    <div class="receipt-overlay" id="receiptOverlay" style="display: none;">
        <div class="receipt-container">
            <div class="receipt-content" id="receiptContent">
                <div class="receipt-header">
                    <h2>Lesehan Tepi Sawah</h2>
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
                        <span><?= htmlspecialchars($username) ?></span>
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
                    <!-- <p>Selamat menikmati hidangan</p> -->
                </div>
            </div>
            
            <div class="receipt-buttons">
                <button class="btn-print-receipt" id="printBtn" onclick="printReceipt()">🖨️ Cetak Struk</button>
                <button class="btn-close-receipt" id="closeReceiptBtn" onclick="closeReceipt()" disabled>Tutup</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

<script>
    const totalTagihan = <?= $selected_pesanan ? (float)$selected_pesanan['total_harga'] : 0 ?>;
    let isPrinted = false; // Status untuk mengontrol tombol Tutup

    function formatRupiah(number) {
        return 'Rp ' + Math.abs(number).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function selectMethod(method, element) {
        document.querySelectorAll('.method-card').forEach(card => card.classList.remove('selected'));
        element.classList.add('selected');
        document.getElementById('metodeInput').value = method;
        hitungKembalian(); 
    }
    
    function hitungKembalian() {
        const jumlahBayar = parseFloat(document.getElementById('jumlahBayar').value) || 0; 
        const kembalian = jumlahBayar - totalTagihan;
        
        const kembalianDisplay = document.getElementById('kembalianDisplay');
        const btnConfirm = document.getElementById('btnConfirm');
        
        if (kembalian >= 0) {
            kembalianDisplay.textContent = formatRupiah(kembalian);
            kembalianDisplay.style.color = '#4A90E2';
            btnConfirm.disabled = false;
            document.getElementById('jumlahDibayarHidden').value = jumlahBayar; 
        } else {
            kembalianDisplay.textContent = formatRupiah(kembalian) + ' (Kurang)';
            kembalianDisplay.style.color = '#dc3545';
            btnConfirm.disabled = true;
            document.getElementById('jumlahDibayarHidden').value = jumlahBayar; 
        }
    }
    
    function closeNotification(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Menampilkan overlay struk setelah notifikasi sukses
    function showReceipt() {
        document.getElementById('successPopup').style.display = 'none';
        document.getElementById('receiptOverlay').style.display = 'flex';
        document.getElementById('closeReceiptBtn').disabled = !isPrinted; 
    }
    
    // Fungsi mencetak struk
    function printReceipt() {
        const content = document.getElementById('receiptContent').innerHTML;
        const printWindow = window.open('', '', 'height=600,width=400');
        
        printWindow.document.write('<html><head><title>Struk Pembayaran</title>');
        
        printWindow.document.write(`
            <style>
                @media print {
                    body { font-family: 'Courier New', monospace; font-size: 10px; margin: 0; padding: 10px; }
                    .receipt-content { width: 100%; max-width: 300px; margin: 0 auto; }
                    .receipt-header, .receipt-footer { text-align: center; margin-bottom: 5px; }
                    .receipt-header h2 { margin: 0; font-size: 14px; }
                    .receipt-header p { margin: 0; font-size: 10px; }
                    .receipt-info { margin: 5px 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; }
                    .receipt-info div, .receipt-item, .receipt-total, .receipt-paid, .receipt-change { display: flex; justify-content: space-between; margin-bottom: 2px; }
                    .receipt-total, .receipt-paid, .receipt-change { font-weight: bold; }
                    .receipt-items { border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
                    @page { margin: 0.5cm; }
                }
            </style>
        `);
        
        printWindow.document.write('</head><body>');
        printWindow.document.write(content); 
        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
        
        isPrinted = true;
        document.getElementById('closeReceiptBtn').disabled = false;
        document.getElementById('printBtn').textContent = "✅ Sudah Cetak";
        document.getElementById('printBtn').disabled = true;
    }
    
    function closeReceipt() {
        if (isPrinted) {
            window.location.href = '<?= $_SERVER['PHP_SELF'] ?>';
        } else {
            alert("Harap Cetak Struk terlebih dahulu!"); 
        }
    }
    
    <?php if ($print_data): ?>
    // Alur: Popup Sukses muncul saat halaman dimuat
    window.onload = function() {
        document.getElementById('successPopup').style.display = 'flex';
    };
    <?php endif; ?>
</script>
</body>
</html>