<?php
session_start();
require '../../database/connect.php';

// Ambil daftar meja kosong dengan validasi
$query_meja = "SELECT id_meja, nomor_meja, kode_unik, status_meja 
               FROM meja 
               WHERE status_meja = 'kosong' 
               AND id_meja IS NOT NULL 
               AND nomor_meja IS NOT NULL
               ORDER BY CAST(nomor_meja AS UNSIGNED), nomor_meja";
$result_meja = mysqli_query($conn, $query_meja);

// Validasi hasil query
$meja_list = [];
if ($result_meja) {
    while ($row = mysqli_fetch_assoc($result_meja)) {
        // Filter hanya meja dengan data lengkap
        if (!empty($row['id_meja']) && !empty($row['nomor_meja'])) {
            $meja_list[] = $row;
        }
    }
}

// Ambil daftar menu
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

$edit_mode = false;
$cart_data = [];
$id_pesanan_edit = null;

if (isset($_GET['id_pesanan'])) {
    $id_pesanan_edit = mysqli_real_escape_string($conn, $_GET['id_pesanan']);
    
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

// Proses submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($conn);
    
    try {
        $jenis_pesanan = mysqli_real_escape_string($conn, $_POST['jenis_pesanan']);
        $metode_bayar = mysqli_real_escape_string($conn, $_POST['metode_bayar']);
        
        $id_meja = null;
        if ($jenis_pesanan === 'dine_in') {
            if (empty($_POST['id_meja'])) {
                throw new Exception("Pilih meja untuk Dine In!");
            }
            $id_meja = mysqli_real_escape_string($conn, $_POST['id_meja']);
            
            // Validasi meja masih kosong
            $check_meja = "SELECT status_meja FROM meja WHERE id_meja = '$id_meja' AND status_meja = 'kosong'";
            $result_check = mysqli_query($conn, $check_meja);
            if (mysqli_num_rows($result_check) === 0) {
                throw new Exception("Meja tidak tersedia atau sudah terisi!");
            }
        }
        
        $cart_data_json = $_POST['cart_data'] ?? '[]';
        $cart_items = json_decode($cart_data_json, true);
        
        if (empty($cart_items)) {
            throw new Exception("Keranjang kosong!");
        }
        
        // sementara gunakan session user_id bila ada, jika tidak gunakan 1
        $dibuat_oleh = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 
        $diterima_oleh = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        
        if ($jenis_pesanan === 'dine_in') {
            $query_pesanan = "INSERT INTO pesanan (id_meja, dibuat_oleh, waktu_pesan, jenis_pesanan, status_pesanan, total_harga, diterima_oleh, metode_bayar) 
                              VALUES ('$id_meja', '$dibuat_oleh', NOW(), '$jenis_pesanan', 'menunggu', 0, '$diterima_oleh', '$metode_bayar')";
        } else {
            $query_pesanan = "INSERT INTO pesanan (dibuat_oleh, waktu_pesan, jenis_pesanan, status_pesanan, total_harga, diterima_oleh, metode_bayar) 
                              VALUES ('$dibuat_oleh', NOW(), '$jenis_pesanan', 'menunggu', 0, '$diterima_oleh', '$metode_bayar')";
        }
        
        mysqli_query($conn, $query_pesanan);
        $id_pesanan = mysqli_insert_id($conn);
        $total_harga = 0;
        
        foreach ($cart_items as $item) {
            $id_menu = mysqli_real_escape_string($conn, $item['id_menu']);
            $jumlah = mysqli_real_escape_string($conn, $item['jumlah']);
            $catatan = mysqli_real_escape_string($conn, $item['catatan']);
            
            $query_harga = "SELECT harga FROM menu WHERE id_menu = '$id_menu'";
            $result_harga = mysqli_query($conn, $query_harga);
            $menu = mysqli_fetch_assoc($result_harga);
            
            $subtotal = $menu['harga'] * $jumlah;
            $total_harga += $subtotal;
            
            $query_detail = "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, harga_satuan, status_item, catatan_item) 
                             VALUES ('$id_pesanan', '$id_menu', '$jumlah', '{$menu['harga']}', 'menunggu', '$catatan')";
            mysqli_query($conn, $query_detail);
        }
        
        $query_update = "UPDATE pesanan SET total_harga = '$total_harga' WHERE id_pesanan = '$id_pesanan'";
        mysqli_query($conn, $query_update);
        
        if ($jenis_pesanan === 'dine_in' && $id_meja) {
            $query_meja_update = "UPDATE meja SET status_meja = 'terisi' WHERE id_meja = '$id_meja'";
            mysqli_query($conn, $query_meja_update);
        }
        
        $query_laporan = "INSERT INTO laporan_transaksi (id_pesanan, id_referensi, jenis, nominal, waktu_transaksi) 
                          VALUES ('$id_pesanan', '$id_pesanan', 'penjualan', '$total_harga', NOW())";
        mysqli_query($conn, $query_laporan);
        
        mysqli_commit($conn);
        
        $jenis_text = $jenis_pesanan === 'dine_in' ? 'Dine In' : 'Take Away';
        $success_message = "Pesanan berhasil dibuat! No. Pesanan: " . $id_pesanan . " | Jenis: " . $jenis_text . " | Metode: " . strtoupper($metode_bayar);
        
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
    <title>Form Pemesanan Manual</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding:20px; }
        .container { max-width:800px; margin:0 auto; background:white; border-radius:12px; padding:30px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        h2{font-size:20px;margin-bottom:5px;color:#333}
        .subtitle{color:#666;font-size:14px;margin-bottom:30px}
        .form-group{margin-bottom:25px}
        label{display:block;margin-bottom:8px;font-weight:500;color:#333;font-size:14px}
        select,input{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;background:#f9f9f9}
        select:focus,input:focus{outline:none;border-color:#4A90E2;background:white}
        .option-group{display:flex;gap:15px;margin-top:10px}
        .option-card{flex:1;padding:20px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;text-align:center;transition:all .3s;background:white}
        .option-card:hover{border-color:#4A90E2;background:#f0f7ff}
        .option-card.selected{border-color:#4A90E2;background:#e3f2fd}
        .option-card .icon{font-size:36px;margin-bottom:8px}
        .option-card .label{font-size:14px;font-weight:600;color:#333}
        .option-card .description{font-size:12px;color:#666;margin-top:4px}
        .item-section{background:#f0f2f5;padding:20px;border-radius:8px;margin-bottom:20px}
        .tab-container{display:flex;gap:10px;margin-bottom:15px;background:white;padding:5px;border-radius:8px}
        .tab-btn{flex:1;padding:12px;border:2px solid #e0e0e0;background:white;border-radius:6px;cursor:pointer;font-size:13px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:5px}
        .tab-btn.active{background:#4A90E2;color:white;border-color:#4A90E2}
        .tab-btn .badge{background:#e0e0e0;padding:2px 8px;border-radius:10px;font-size:11px;color:#333}
        .item-input{display:flex;gap:15px;margin-bottom:15px}
        .item-input>div:first-child{flex:2}
        .item-input>div:last-child{flex:1}
        input[type=number]{text-align:center}
        textarea{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:13px;background:white;resize:vertical;min-height:60px;font-family:inherit}
        .btn-add{width:100%;padding:14px;background:#4A90E2;color:white;border:none;border-radius:6px;font-size:14px;font-weight:500;cursor:pointer}
        .btn-submit{width:100%;padding:16px;background:#FFC864;color:#333;border:none;border-radius:6px;font-size:15px;font-weight:600;cursor:pointer}
        .alert{padding:15px;border-radius:6px;margin-bottom:20px}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .order-summary{background:#ffffff;border:2px solid #e0e0e0;padding:20px;border-radius:8px;margin-bottom:20px}
        .summary-title{font-weight:600;margin-bottom:15px;color:#333;font-size:16px}
        .cart-item{display:flex;justify-content:space-between;align-items:start;padding:12px;background:#f9f9f9;border-radius:6px;margin-bottom:10px}
        .cart-item-info{flex:1}
        .cart-item-name{font-weight:600;color:#333;margin-bottom:4px}
        .cart-item-details{font-size:13px;color:#666}
        .cart-item-note{font-size:12px;color:#888;font-style:italic;margin-top:4px}
        .cart-item-price{font-weight:600;color:#4A90E2;margin-right:10px}
        .btn-remove-cart{background:#ff4444;color:white;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:12px}
        .summary-total{display:flex;justify-content:space-between;padding-top:15px;margin-top:15px;border-top:2px solid #e0e0e0;font-weight:600;font-size:18px;color:#333}
        .summary-total .amount{color:#FF6B35}
        .empty-cart{text-align:center;padding:30px;color:#999}
        #mejaGroup{transition:all .3s ease;overflow:hidden}
        #mejaGroup.hidden{max-height:0;opacity:0;margin-bottom:0;padding:0}
        .no-tables-warning{background:#fff3cd;color:#856404;padding:12px;border-radius:6px;border:1px solid #ffeaa7;font-size:13px;margin-top:8px}
        @media (max-width:768px){.container{padding:15px;margin:0;border-radius:0;box-shadow:none;width:100%;max-width:100%}.item-input{flex-direction:column}.tab-container{overflow-x:auto;display:flex;white-space:nowrap;padding-bottom:5px}.option-group{flex-direction:column}.cart-item{flex-direction:column;align-items:flex-start}.summary-total{flex-direction:column;text-align:right}}
    </style>
</head>
<body>
    <div class="container">
        <h2>Form Pemesanan Manual</h2>
        <p class="subtitle">Tambahkan pesanan untuk customer</p>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" id="orderForm">
            <input type="hidden" name="cart_data" id="cartData">
            <input type="hidden" name="jenis_pesanan" id="jenisPesanan" value="dine_in">
            <input type="hidden" name="metode_bayar" id="metodeBayar" value="cash">
            
            <div class="form-group">
                <label>Jenis Pesanan</label>
                <div class="option-group">
                    <div class="option-card selected" onclick="selectOrderType('dine_in', this)">
                        <div class="icon">🍽️</div>
                        <div class="label">Dine In</div>
                        <div class="description">Makan di tempat</div>
                    </div>
                    <div class="option-card" onclick="selectOrderType('take_away', this)">
                        <div class="icon">🥡</div>
                        <div class="label">Take Away</div>
                        <div class="description">Bawa pulang</div>
                    </div>
                </div>
            </div>
            
            <div class="form-group" id="mejaGroup">
                <label for="id_meja">Pilih Meja</label>
                <select name="id_meja" id="id_meja" required>
                    <option value="">-- Pilih meja --</option>
                    <?php if (!empty($meja_list)): ?>
                        <?php foreach ($meja_list as $meja): ?>
                            <option value="<?php echo htmlspecialchars($meja['id_meja']); ?>">
                                Meja <?php echo htmlspecialchars($meja['nomor_meja']); ?>
                                <?php if (!empty($meja['kode_unik'])): ?>
                                    (<?php echo htmlspecialchars($meja['kode_unik']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Tidak ada meja tersedia</option>
                    <?php endif; ?>
                </select>
                <?php if (empty($meja_list)): ?>
                    <div class="no-tables-warning">
                        ⚠️ Semua meja sedang terisi atau belum ada data meja di database
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="item-section">
                <div class="tab-container">
                    <button type="button" class="tab-btn active" onclick="switchTab(this, 'makanan')">
                        🍽️ Makanan
                        <span class="badge"><?php echo count($menu_by_category['makanan']); ?></span>
                    </button>
                    <button type="button" class="tab-btn" onclick="switchTab(this, 'minuman')">
                        🥤 Minuman
                        <span class="badge"><?php echo count($menu_by_category['minuman']); ?></span>
                    </button>
                    <button type="button" class="tab-btn" onclick="switchTab(this, 'cemilan')">
                        🍰 Cemilan
                        <span class="badge"><?php echo count($menu_by_category['cemilan']); ?></span>
                    </button>
                </div>
                
                <div class="item-input">
                    <div>
                        <label id="menuLabel">Menu Makanan</label>
                        <select id="menuSelect">
                            <option value="">Pilih menu makanan</option>
                            <?php foreach ($menu_by_category['makanan'] as $menu): ?>
                                <option value="<?php echo $menu['id_menu']; ?>" 
                                        data-nama="<?php echo htmlspecialchars($menu['nama_menu']); ?>"
                                        data-harga="<?php echo $menu['harga']; ?>" 
                                        data-kategori="makanan">
                                    <?php echo htmlspecialchars($menu['nama_menu']); ?> - Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php foreach ($menu_by_category['minuman'] as $menu): ?>
                                <option value="<?php echo $menu['id_menu']; ?>" 
                                        data-nama="<?php echo htmlspecialchars($menu['nama_menu']); ?>"
                                        data-harga="<?php echo $menu['harga']; ?>" 
                                        data-kategori="minuman" 
                                        style="display:none;">
                                    <?php echo htmlspecialchars($menu['nama_menu']); ?> - Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php foreach ($menu_by_category['cemilan'] as $menu): ?>
                                <option value="<?php echo $menu['id_menu']; ?>" 
                                        data-nama="<?php echo htmlspecialchars($menu['nama_menu']); ?>"
                                        data-harga="<?php echo $menu['harga']; ?>" 
                                        data-kategori="cemilan" 
                                        style="display:none;">
                                    <?php echo htmlspecialchars($menu['nama_menu']); ?> - Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?>
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
            
            <div class="form-group">
                <label>Metode Pembayaran</label>
                <div class="option-group">
                    <div class="option-card selected" onclick="selectPayment('cash', this)">
                        <div class="icon">💵</div>
                        <div class="label">Cash</div>
                        <div class="description">Bayar tunai</div>
                    </div>
                    <div class="option-card" onclick="selectPayment('qris', this)">
                        <div class="icon">📱</div>
                        <div class="label">QRIS</div>
                        <div class="description">Scan QR Code</div>
                    </div>
                </div>
            </div>
            
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
        // Inisialisasi cart dari server (jika edit)
        let cart = <?php echo !empty($cart_data) ? json_encode($cart_data) : '[]'; ?>;

        // Jika ada data awal, munculkan
        if (cart.length > 0) {
            updateCartDisplay();
        }

        function selectOrderType(jenis, element) {
            document.querySelectorAll('.option-group')[0].querySelectorAll('.option-card').forEach(option => {
                option.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('jenisPesanan').value = jenis;
            
            const mejaGroup = document.getElementById('mejaGroup');
            const mejaSelect = document.getElementById('id_meja');
            
            if (jenis === 'dine_in') {
                mejaGroup.classList.remove('hidden');
                mejaSelect.setAttribute('required', 'required');
            } else {
                mejaGroup.classList.add('hidden');
                mejaSelect.removeAttribute('required');
                mejaSelect.value = '';
            }
        }
        
        function selectPayment(metode, element) {
            document.querySelectorAll('.option-group')[1].querySelectorAll('.option-card').forEach(option => {
                option.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('metodeBayar').value = metode;
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
            const cartDataInput = document.getElementById('cartData');

            if (cart.length === 0) {
                cartItems.innerHTML = '<div class="empty-cart">Belum ada pesanan</div>';
                totalSection.style.display = 'none';
                cartDataInput.value = '[]';
                return;
            }

            let total = 0;
            cartItems.innerHTML = '';

            cart.forEach((item, index) => {
                const subtotal = item.harga * item.jumlah;
                total += subtotal;

                const div = document.createElement('div');
                div.className = 'cart-item';
                div.innerHTML = `
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.nama} (${item.jumlah}x)</div>
                        <div class="cart-item-details">Rp ${item.harga.toLocaleString()}</div>
                        ${item.catatan ? `<div class="cart-item-note">Catatan: ${item.catatan}</div>` : ''}
                    </div>
                    <div>
                        <div class="cart-item-price">Rp ${subtotal.toLocaleString()}</div>
                        <button type="button" class="btn-remove-cart" onclick="removeFromCart(${index})">Hapus</button>
                    </div>
                `;
                cartItems.appendChild(div);
            });

            totalAmount.textContent = 'Rp ' + total.toLocaleString();
            totalSection.style.display = 'flex';
            cartDataInput.value = JSON.stringify(cart);
        }
        
        document.getElementById('orderForm').onsubmit = function () {
            document.getElementById('cartData').value = JSON.stringify(cart);
            return true;
        };
    </script>
</body>
</html>