<?php
// Koneksi database
require '../../database/connect.php';

// Ambil data meja
$query_meja = "SELECT * FROM meja WHERE status_meja = 'kosong' ORDER BY nomor_meja";
$result_meja = mysqli_query($conn, $query_meja);
$meja_list = mysqli_fetch_all($result_meja, MYSQLI_ASSOC);

// Ambil data menu (DIKELOMPOKKAN BERDASARKAN KATEGORI)
$query_menu = "SELECT * FROM menu ORDER BY kategori, nama_menu";
$result_menu = mysqli_query($conn, $query_menu);
$menu_list = mysqli_fetch_all($result_menu, MYSQLI_ASSOC);

// Kelompokkan menu berdasarkan kategori
$menu_by_category = [
    'makanan' => [],
    'minuman' => [],
    'cemilan' => []
];

foreach ($menu_list as $menu) {
    $kategori = strtolower($menu['kategori']);
    if (isset($menu_by_category[$kategori])) {
        $menu_by_category[$kategori][] = $menu;
    }
}

// Cek apakah ada parameter id_pesanan untuk edit (Modifikasi kecil di sini untuk catatan)
$edit_mode = false;
$cart_data = [];
$id_pesanan_edit = null;

if (isset($_GET['id_pesanan'])) {
    $id_pesanan_edit = mysqli_real_escape_string($conn, $_GET['id_pesanan']);
    
    // Ambil data detail pesanan
    $query_detail = "SELECT dp.id_pesanan, dp.*, m.nama_menu, m.harga, dp.catatan_item as catatan
                     FROM detail_pesanan dp
                     JOIN menu m ON dp.id_menu = m.id_menu
                     WHERE dp.id_pesanan = '$id_pesanan_edit'";
    $result_detail = mysqli_query($conn, $query_detail);
    
    if ($result_detail && mysqli_num_rows($result_detail) > 0) {
        $edit_mode = true;
        while ($row = mysqli_fetch_assoc($result_detail)) {
            $cart_data[] = [
                'id_menu' => $row['id_menu'],
                'nama' => $row['nama_menu'],
                'harga' => $row['harga_satuan'],
                'jumlah' => $row['jumlah'],
                'catatan' => $row['catatan'] ?? ''
            ];
        }
    }
}

// Proses form ketika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($conn);
    
    try {
        $id_meja = mysqli_real_escape_string($conn, $_POST['id_meja']);
        
        // Perbaikan untuk mencegah 'Undefined array key "cart_data"' jika data kosong
        // dan menghindari json_decode(null) yang deprecated.
        $cart_data_json = $_POST['cart_data'] ?? '[]';
        $cart_items = json_decode($cart_data_json, true);
        
        if (empty($cart_items)) {
            throw new Exception("Keranjang kosong!");
        }
        
        // Asumsi user/kasir ID diambil dari session, default ke 1
        $dibuat_oleh = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 
        $diterima_oleh = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        
        // --- PERBAIKAN UTAMA: INSERT pesanan ---
        // Menambahkan kolom 'dibuat_oleh' dan 'jenis_pesanan' yang ada di struktur tabel pesanan
        // Menghilangkan 'metode_bayar' dari INSERT awal jika pesanan baru dibuat, 
        // dan pembayaran belum dilakukan (status masih 'menunggu').
        // Namun, jika Anda ingin menyimpannya sebagai default 'qris' di pesanan (seperti kode lama), itu juga bisa.
        // Saya asumsikan ini adalah input pesanan, bukan transaksi pembayaran.
        $query_pesanan = "INSERT INTO pesanan (id_meja, dibuat_oleh, waktu_pesan, jenis_pesanan, status_pesanan, total_harga, diterima_oleh) 
                          VALUES ('$id_meja', '$dibuat_oleh', NOW(), 'dine_in', 'menunggu', 0, '$diterima_oleh')";
        mysqli_query($conn, $query_pesanan);
        
        $id_pesanan = mysqli_insert_id($conn);
        $total_harga = 0;
        
        // Insert detail pesanan
        foreach ($cart_items as $item) {
            $id_menu = mysqli_real_escape_string($conn, $item['id_menu']);
            $jumlah = mysqli_real_escape_string($conn, $item['jumlah']);
            $catatan = mysqli_real_escape_string($conn, $item['catatan']);
            
            // Ambil harga menu
            $query_harga = "SELECT harga FROM menu WHERE id_menu = '$id_menu'";
            $result_harga = mysqli_query($conn, $query_harga);
            $menu = mysqli_fetch_assoc($result_harga);
            
            $subtotal = $menu['harga'] * $jumlah; // Digunakan hanya untuk perhitungan total_harga
            $total_harga += $subtotal;
            
            // --- PERBAIKAN UTAMA: INSERT detail_pesanan ---
            // HANYA menyisipkan kolom yang bukan AUTO_INCREMENT (id_detail) 
            // dan bukan GENERATED COLUMN (subtotal).
            $query_detail = "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, harga_satuan, status_item, catatan_item) 
                             VALUES ('$id_pesanan', '$id_menu', '$jumlah', '{$menu['harga']}', 'menunggu', '$catatan')";
            mysqli_query($conn, $query_detail);
        }
        
        // Update total harga pesanan (dengan asumsi 'total_harga' di pesanan tidak Generated Column)
        $query_update = "UPDATE pesanan SET total_harga = '$total_harga' WHERE id_pesanan = '$id_pesanan'";
        mysqli_query($conn, $query_update);
        
        // Update status meja
        $query_meja_update = "UPDATE meja SET status_meja = 'terisi' WHERE id_meja = '$id_meja'";
        mysqli_query($conn, $query_meja_update);
        
        // Insert ke laporan transaksi (Modifikasi: Tambah kolom metode_bayar ke laporan jika diperlukan, tapi kode aslinya tidak punya)
        // Saya akan mengikuti kode asli Anda yang menyisipkan ke laporan transaksi
        $metode_bayar_pesanan = 'qris'; // Mengikuti default dari kode asli Anda
        $query_laporan = "INSERT INTO laporan_transaksi (id_pesanan, id_referensi, jenis, nominal, waktu_transaksi) 
                          VALUES ('$id_pesanan', '$id_pesanan', 'penjualan', '$total_harga', NOW())";
        // Asumsi tabel laporan_transaksi memiliki kolom: id_pesanan, id_referensi, jenis, nominal, waktu_transaksi
        mysqli_query($conn, $query_laporan);
        
        mysqli_commit($conn);
        
        $success_message = "Pesanan berhasil dibuat! No. Pesanan: " . $id_pesanan;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        // Error terkait kolom 'jumlah_dibayar' atau 'kembalian' yang Anda lihat
        // kemungkinan berasal dari kode yang tidak ditampilkan atau dari *query* lain yang
        // seharusnya ada di proses pembayaran. Di sini, saya hanya menangani error dari blok ini.
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pemesanan Manual</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        h2 {
            font-size: 20px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        select, input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: #f9f9f9;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: #4A90E2;
            background: white;
        }
        
        .item-section {
            background: #f0f2f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .tab-container {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            background: white;
            padding: 5px;
            border-radius: 8px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .tab-btn:hover {
            border-color: #4A90E2;
            background: #f0f7ff;
        }
        
        .tab-btn.active {
            background: #4A90E2;
            color: white;
            border-color: #4A90E2;
        }
        
        .tab-btn .badge {
            background: #e0e0e0;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            color: #333;
        }
        
        .tab-btn.active .badge {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .item-input {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .item-input > div:first-child {
            flex: 2;
        }
        
        .item-input > div:last-child {
            flex: 1;
        }
        
        input[type="number"] {
            text-align: center;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
        }
        
        .btn-add {
            width: 100%;
            padding: 14px;
            background: #4A90E2;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-add:hover {
            background: #357ABD;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: #FFC864;
            color: #333;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            background: #FFB84D;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .order-summary {
            background: #ffffff;
            border: 2px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .summary-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: start;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .cart-item-details {
            font-size: 13px;
            color: #666;
        }
        
        .cart-item-note {
            font-size: 12px;
            color: #888;
            font-style: italic;
            margin-top: 4px;
        }
        
        .cart-item-price {
            font-weight: 600;
            color: #4A90E2;
            margin-right: 10px;
        }
        
        .btn-remove-cart {
            background: #ff4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-remove-cart:hover {
            background: #cc0000;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #e0e0e0;
            font-weight: 600;
            font-size: 18px;
            color: #333;
        }
        
        .summary-total .amount {
            color: #FF6B35;
        }
        
        .empty-cart {
            text-align: center;
            padding: 30px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Form Pemesanan Manual</h2>
        <p class="subtitle">Tambahkan pesanan untuk customer</p>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>
        
        <?php if (!empty($_POST) && !isset($_POST['cart_data'])): ?>
            <div class="alert alert-error">Warning: Keranjang kosong saat disubmit. Harap tambahkan item sebelum Buat Pesanan.</div>
        <?php endif; ?>
        
        <form method="POST" id="orderForm">
            <input type="hidden" name="cart_data" id="cartData">
            
            <div class="form-group">
                <label for="id_meja">Pilih Meja</label>
                <select name="id_meja" id="id_meja" required>
                    <option value="">Pilih meja</option>
                    <?php foreach ($meja_list as $meja): ?>
                        <option value="<?= $meja['id_meja'] ?>">
                            Meja <?= $meja['nomor_meja'] ?> (<?= $meja['kode_unik'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="item-section">
                <div class="tab-container">
                    <button type="button" class="tab-btn active" onclick="switchTab(this, 'makanan')">
                        🍽️ Makanan
                        <span class="badge"><?= count($menu_by_category['makanan']) ?></span>
                    </button>
                    <button type="button" class="tab-btn" onclick="switchTab(this, 'minuman')">
                        🥤 Minuman
                        <span class="badge"><?= count($menu_by_category['minuman']) ?></span>
                    </button>
                    <button type="button" class="tab-btn" onclick="switchTab(this, 'cemilan')">
                        🍰 Cemilan
                        <span class="badge"><?= count($menu_by_category['cemilan']) ?></span>
                    </button>
                </div>
                
                <div class="item-input">
                    <div>
                        <label id="menuLabel">Menu Makanan</label>
                        <select id="menuSelect">
                            <option value="">Pilih menu makanan</option>
                            <?php foreach ($menu_by_category['makanan'] as $menu): ?>
                                <option value="<?= $menu['id_menu'] ?>" 
                                        data-nama="<?= $menu['nama_menu'] ?>"
                                        data-harga="<?= $menu['harga'] ?>" 
                                        data-kategori="makanan">
                                    <?= $menu['nama_menu'] ?> - Rp <?= number_format($menu['harga'], 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                            <?php foreach ($menu_by_category['minuman'] as $menu): ?>
                                <option value="<?= $menu['id_menu'] ?>" 
                                        data-nama="<?= $menu['nama_menu'] ?>"
                                        data-harga="<?= $menu['harga'] ?>" 
                                        data-kategori="minuman" 
                                        style="display:none;">
                                    <?= $menu['nama_menu'] ?> - Rp <?= number_format($menu['harga'], 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                            <?php foreach ($menu_by_category['cemilan'] as $menu): ?>
                                <option value="<?= $menu['id_menu'] ?>" 
                                        data-nama="<?= $menu['nama_menu'] ?>"
                                        data-harga="<?= $menu['harga'] ?>" 
                                        data-kategori="cemilan" 
                                        style="display:none;">
                                    <?= $menu['nama_menu'] ?> - Rp <?= number_format($menu['harga'], 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Jumlah</label>
                        <input type="number" id="jumlahInput" min="1" value="1">
                    </div>
                </div>
                
                <div>
                    <label>Catatan Khusus (Opsional)</label>
                    <textarea id="catatanInput" placeholder="Contoh: Pedas level 5, tanpa bawang"></textarea>
                </div>
            </div>
            
            <button type="button" class="btn-add" onclick="addToCart()">+ Tambah Item</button>
            
            <div class="order-summary">
                <div class="summary-title">Daftar Pesanan</div>
                <div id="cartItems">
                    <div class="empty-cart">Belum ada pesanan</div>
                </div>
                <div class="summary-total" id="totalSection" style="display: none;">
                    <span>Total</span>
                    <span class="amount" id="totalAmount">Rp 0</span>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Buat Pesanan</button>
        </form>
    </div>
    
    <script>
        // Load cart dari PHP jika dalam mode edit
        let cart = <?= !empty($cart_data) ? json_encode($cart_data) : '[]' ?>;
        
        // Update display jika ada data awal
        if (cart.length > 0) {
            updateCartDisplay();
        }
        
        function switchTab(button, kategori) {
            const tabContainer = button.parentElement;
            const allTabs = tabContainer.querySelectorAll('.tab-btn');
            allTabs.forEach(tab => tab.classList.remove('active'));
            button.classList.add('active');
            
            const select = document.getElementById('menuSelect');
            select.value = '';
            
            const label = document.getElementById('menuLabel');
            const labelText = {
                'makanan': 'Menu Makanan',
                'minuman': 'Menu Minuman',
                'cemilan': 'Menu Cemilan'
            };
            label.textContent = labelText[kategori];
            
            const options = select.querySelectorAll('option');
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                    option.textContent = 'Pilih menu ' + kategori;
                } else {
                    const optionKategori = option.getAttribute('data-kategori');
                    option.style.display = optionKategori === kategori ? 'block' : 'none';
                }
            });
        }
        
        function addToCart() {
            const select = document.getElementById('menuSelect');
            const jumlah = parseInt(document.getElementById('jumlahInput').value);
            const catatan = document.getElementById('catatanInput').value;
            
            if (!select.value) {
                alert('Pilih menu terlebih dahulu!');
                return;
            }
            
            if (jumlah < 1) {
                alert('Jumlah minimal 1!');
                return;
            }
            
            const selectedOption = select.options[select.selectedIndex];
            const item = {
                id_menu: select.value,
                nama: selectedOption.getAttribute('data-nama'),
                harga: parseInt(selectedOption.getAttribute('data-harga')),
                jumlah: jumlah,
                catatan: catatan
            };
            
            cart.push(item);
            
            // Reset form
            select.value = '';
            document.getElementById('jumlahInput').value = 1;
            document.getElementById('catatanInput').value = '';
            
            updateCartDisplay();
        }
        
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }
        
        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const totalSection = document.getElementById('totalSection');
            const totalAmount = document.getElementById('totalAmount');
            
            if (cart.length === 0) {
                cartItems.innerHTML = '<div class="empty-cart">Belum ada pesanan</div>';
                totalSection.style.display = 'none';
                return;
            }
            
            let html = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                // Pastikan harga adalah number, jika dari PHP string, gunakan parseFloat
                const harga = typeof item.harga === 'string' ? parseFloat(item.harga) : item.harga;
                const subtotal = harga * item.jumlah;
                total += subtotal;
                
                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.nama}</div>
                            <div class="cart-item-details">
                                ${item.jumlah} x Rp ${harga.toLocaleString('id-ID')}
                            </div>
                            ${item.catatan ? `<div class="cart-item-note">${item.catatan}</div>` : ''}
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="cart-item-price">Rp ${subtotal.toLocaleString('id-ID')}</div>
                            <button type="button" class="btn-remove-cart" onclick="removeFromCart(${index})">Hapus</button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            totalAmount.textContent = `Rp ${total.toLocaleString('id-ID')}`;
            totalSection.style.display = 'flex';
        }
        
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Keranjang masih kosong! Tambahkan minimal 1 item.');
                return false;
            }
            
            // Masukkan data keranjang ke hidden input sebelum submit
            document.getElementById('cartData').value = JSON.stringify(cart);
        });
    </script>
</body>
</html>