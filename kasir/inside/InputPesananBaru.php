<?php
session_start();
require '../../database/connect.php';


$query_meja = "SELECT id_meja, nomor_meja, kode_unik, status_meja 
               FROM meja 
               WHERE status_meja = 'kosong' 
               ORDER BY CAST(nomor_meja AS UNSIGNED), nomor_meja";
$result_meja = mysqli_query($conn, $query_meja);

$meja_list = [];
if ($result_meja && mysqli_num_rows($result_meja) > 0) {
    while ($row = mysqli_fetch_assoc($result_meja)) {
        if (!empty($row['id_meja']) && !empty($row['nomor_meja'])) {
            $meja_list[] = $row;
        }
    }
}


$query_menu = "SELECT * FROM menu ORDER BY kategori, nama_menu";
$result_menu = mysqli_query($conn, $query_menu);
$menu_list = mysqli_fetch_all($result_menu, MYSQLI_ASSOC);

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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($conn);
    try {
        $jenis_pesanan = $_POST['jenis_pesanan'];
        $metode_bayar  = strtolower(trim(mysqli_real_escape_string($conn, $_POST['metode_bayar'] ?? '')));
        $id_meja = $jenis_pesanan === 'dine_in' ? $_POST['id_meja'] : null;
        $cart_data_json = $_POST['cart_data'] ?? '[]';
        $cart_items = json_decode($cart_data_json, true);

        
        if (empty($cart_items)) {
            throw new Exception("Keranjang masih kosong!");
        }

        
        if ($jenis_pesanan === 'dine_in' && !$id_meja) {
            throw new Exception("Harap pilih meja untuk Dine In!");
        }

        
        $allowed_methods = ['qris', 'cash'];
        if (!in_array($metode_bayar, $allowed_methods)) {
            throw new Exception("Metode bayar tidak valid!");
        }

        $dibuat_oleh = $_SESSION['user_id'] ?? 1;
        $diterima_oleh = $_SESSION['user_id'] ?? 1;

        
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
            $id_menu = $item['id_menu'];
            $jumlah = $item['jumlah'];
            $catatan = mysqli_real_escape_string($conn, $item['catatan']);
            $harga_result = mysqli_query($conn, "SELECT harga FROM menu WHERE id_menu='$id_menu'");
            $menu = mysqli_fetch_assoc($harga_result);
            $harga = $menu['harga'];
            $subtotal = $harga * $jumlah;
            $total_harga += $subtotal;

            mysqli_query($conn, "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, harga_satuan, status_item, catatan_item)
                                 VALUES ('$id_pesanan', '$id_menu', '$jumlah', '$harga', 'menunggu', '$catatan')");
        }

        
        mysqli_query($conn, "UPDATE pesanan SET total_harga='$total_harga' WHERE id_pesanan='$id_pesanan'");

        
        if ($jenis_pesanan === 'dine_in' && $id_meja) {
            mysqli_query($conn, "UPDATE meja SET status_meja='terisi' WHERE id_meja='$id_meja'");
        }

        mysqli_commit($conn);
        $success_message = "✅ Pesanan berhasil dibuat! Nomor pesanan: $id_pesanan";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "❌ Gagal membuat pesanan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pesanan Baru</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f5f5f5; 
            padding: 20px;
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            display: flex;
            gap: 20px;
        }
        .left-section { flex: 1; }
        .right-section {
            width: 350px;
            transition: all 0.3s ease;
        }
        
        .right-section.hidden {
            opacity: 0;
            pointer-events: none;
            transform: translateX(20px);
        }
        
        h2 { 
            font-size: 24px; 
            margin-bottom: 5px;
            color: #333;
        }
        .subtitle { 
            color: #999; 
            font-size: 13px; 
            margin-bottom: 25px;
        }
        
        .section-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #666;
        }
        
        .order-type-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .type-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }
        
        .type-card:hover {
            border-color: #FFA726;
            background: #FFF8F0;
        }
        
        .type-card.active {
            border-color: #FFA726;
            background: #FFF8F0;
        }
        
        .type-card .icon {
            font-size: 42px;
            margin-bottom: 10px;
        }
        
        .type-card .label {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .type-card .desc {
            font-size: 12px;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: #fafafa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #FFA726;
            background: white;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box::before {
            content: "🔍";
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }
        
        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 500;
            color: #999;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab-btn.active {
            color: #333;
            border-bottom-color: #FFA726;
        }
        
        .tab-btn .icon {
            margin-right: 5px;
        }
        
        .tab-btn .badge {
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .menu-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            background: white;
            transition: all 0.2s;
        }
        
        .menu-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .menu-card .image {
            width: 100%;
            height: 100px;
            background: #f5f5f5;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #999;
        }
        
        .menu-card .name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .menu-card .price {
            color: #FFA726;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .btn-tambah {
            width: 100%;
            padding: 8px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-tambah:hover {
            background: #1976D2;
        }
        
        .cart-box {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .cart-header {
            background: #FFF8F0;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-header .title {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }
        
        .cart-header .badge {
            background: #FFA726;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .cart-header .clear {
            color: #f44336;
            font-size: 13px;
            cursor: pointer;
            text-decoration: underline;
        }
        
        .cart-items {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .cart-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 14px;
        }
        
        .cart-item {
            background: #FFF8F0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
        }
        
        .cart-item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .cart-item-name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }
        
        .cart-item-note {
            font-size: 12px;
            color: #888;
            font-style: italic;
            margin-top: 4px;
        }
        
        .cart-item-note-input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 8px;
            font-family: inherit;
        }
        
        .cart-item-note-input:focus {
            outline: none;
            border-color: #FFA726;
        }
        
        .cart-item-remove {
            background: none;
            border: none;
            color: #f44336;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            width: 24px;
            height: 24px;
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .qty-btn {
            width: 28px;
            height: 28px;
            border: 1px solid #e0e0e0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:hover {
            background: #f5f5f5;
        }
        
        .qty-value {
            font-weight: 600;
            font-size: 14px;
            min-width: 20px;
            text-align: center;
        }
        
        .item-price {
            font-weight: 700;
            color: #FFA726;
            font-size: 14px;
        }
        
        .cart-summary {
            border-top: 2px solid #f0f0f0;
            padding: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .summary-row.total {
            font-weight: 700;
            font-size: 18px;
            color: #333;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }
        
        .summary-row.total .amount {
            color: #FFA726;
        }
        
        .payment-section {
            margin-top: 20px;
        }
        
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .payment-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-card:hover {
            border-color: #FFA726;
            background: #FFF8F0;
        }
        
        .payment-card.active {
            border-color: #FFA726;
            background: #FFF8F0;
        }
        
        .payment-card .icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 8px;
        }
        
        .payment-card .info .name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }
        
        .payment-card .info .desc {
            font-size: 12px;
            color: #999;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: #FFA726;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .btn-submit:hover {
            background: #FF9800;
        }
        
        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        #mejaGroup {
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        #mejaGroup.hidden {
            max-height: 0;
            opacity: 0;
            margin: 0;
            padding: 0;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .right-section {
                width: 100%;
            }
            .right-section.hidden {
                display: none;
            }
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h2>Input Pesanan Baru</h2>
            <p class="subtitle">Kelola pesanan dari pelanggan</p>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?= $success_message; ?></div>
            <?php elseif (isset($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="formPesanan">
                <input type="hidden" name="cart_data" id="cart_data">
                <input type="hidden" name="jenis_pesanan" id="jenis_pesanan" value="">
                <input type="hidden" name="metode_bayar" id="metode_bayar" value="">
                
                <div class="section-box">
                    <div class="section-title">Tipe Pesanan</div>
                    <div class="order-type-cards">
                        <div class="type-card" onclick="selectOrderType('dine_in', this)">
                            <div class="icon">🍽️</div>
                            <div class="label">Dine-In</div>
                            <div class="desc">Makan di tempat</div>
                        </div>
                        <div class="type-card" onclick="selectOrderType('take_away', this)">
                            <div class="icon">📦</div>
                            <div class="label">Take Away</div>
                            <div class="desc">Bawa pulang</div>
                        </div>
                    </div>
                </div>
                
                <div class="section-box hidden" id="mejaGroup">
                    <div class="form-group">
                        <label>Pilih Meja</label>
                        <select name="id_meja" id="selectMeja" class="form-control">
                            <option value="">Pilih nomor meja...</option>
                            <?php if (!empty($meja_list)): ?>
                                <?php foreach ($meja_list as $m): ?>
                                    <option value="<?= htmlspecialchars($m['id_meja']); ?>">
                                        Meja <?= htmlspecialchars($m['nomor_meja']); ?>
                                        <?php if (!empty($m['kode_unik'])): ?>
                                            (<?= htmlspecialchars($m['kode_unik']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada meja tersedia</option>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($meja_list)): ?>
                            <small style="color: #f44336; font-size: 12px; margin-top: 5px; display: block;">
                                ⚠️ Semua meja sedang terisi
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="section-box">
                    <div class="section-title">Pilih Menu</div>
                    
                    <div class="search-box">
                        <input type="text" placeholder="Cari menu..." id="searchMenu">
                    </div>
                    
                    <div class="category-tabs">
                        <button type="button" class="tab-btn active" onclick="switchCategory(this, 'makanan')">
                            <span class="icon">🍽️</span> Makanan
                            <span class="badge"><?= count($menu_by_category['makanan']); ?></span>
                        </button>
                        <button type="button" class="tab-btn" onclick="switchCategory(this, 'minuman')">
                            <span class="icon">🍹</span> Minuman
                            <span class="badge"><?= count($menu_by_category['minuman']); ?></span>
                        </button>
                        <button type="button" class="tab-btn" onclick="switchCategory(this, 'cemilan')">
                            <span class="icon">🍰</span> Cemilan
                            <span class="badge"><?= count($menu_by_category['cemilan']); ?></span>
                        </button>
                    </div>
                    
                    <div class="menu-grid" id="menuContainer">
                        <?php foreach ($menu_by_category['makanan'] as $menu): ?>
                            <div class="menu-card" data-kategori="makanan">
                                <div class="image">No Image</div>
                                <div class="name"><?= htmlspecialchars($menu['nama_menu']); ?></div>
                                <div class="price">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></div>
                                <button type="button" class="btn-tambah" onclick="addToCart(<?= $menu['id_menu']; ?>, '<?= addslashes($menu['nama_menu']); ?>', <?= $menu['harga']; ?>)">
                                    + Tambah
                                </button>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($menu_by_category['minuman'] as $menu): ?>
                            <div class="menu-card" data-kategori="minuman" style="display: none;">
                                <div class="image">No Image</div>
                                <div class="name"><?= htmlspecialchars($menu['nama_menu']); ?></div>
                                <div class="price">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></div>
                                <button type="button" class="btn-tambah" onclick="addToCart(<?= $menu['id_menu']; ?>, '<?= addslashes($menu['nama_menu']); ?>', <?= $menu['harga']; ?>)">
                                    + Tambah
                                </button>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($menu_by_category['cemilan'] as $menu): ?>
                            <div class="menu-card" data-kategori="cemilan" style="display: none;">
                                <div class="image">No Image</div>
                                <div class="name"><?= htmlspecialchars($menu['nama_menu']); ?></div>
                                <div class="price">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></div>
                                <button type="button" class="btn-tambah" onclick="addToCart(<?= $menu['id_menu']; ?>, '<?= addslashes($menu['nama_menu']); ?>', <?= $menu['harga']; ?>)">
                                    + Tambah
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="right-section hidden" id="cartSection">
            <div class="cart-box">
                <div class="cart-header">
                    <div class="title">🛒 Keranjang</div>
                    <div class="badge" id="cartBadge">0</div>
                    <div class="clear" onclick="clearCart()" style="display: none;" id="clearBtn">Kosongkan</div>
                </div>
                
                <div class="cart-items" id="cartList">
                    <div class="cart-empty">Belum ada item.</div>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">Rp 0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="amount" id="totalHarga">Rp 0</span>
                    </div>
                    
                    <div class="payment-section">
                        <div class="section-title">Metode Pembayaran</div>
                        <div class="payment-methods">
                            <div class="payment-card" onclick="selectPayment('qris', this)">
                                <div class="icon">📱</div>
                                <div class="info">
                                    <div class="name">QRIS</div>
                                    <div class="desc">Scan QR Code</div>
                                </div>
                            </div>
                            <div class="payment-card" onclick="selectPayment('cash', this)">
                                <div class="icon">💵</div>
                                <div class="info">
                                    <div class="name">Cash</div>
                                    <div class="desc">Tunai</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" form="formPesanan" class="btn-submit" id="btnSubmit" disabled>
                        Buat Pesanan
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let cart = [];
        
        function selectOrderType(jenis, element) {
            document.querySelectorAll('.type-card').forEach(card => card.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('jenis_pesanan').value = jenis;
            
            const mejaGroup = document.getElementById('mejaGroup');
            if (jenis === 'dine_in') {
                mejaGroup.classList.remove('hidden');
            } else {
                mejaGroup.classList.add('hidden');
            }
            
            checkFormValidity();
        }
        
        function switchCategory(button, kategori) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            document.querySelectorAll('.menu-card').forEach(card => {
                card.style.display = card.getAttribute('data-kategori') === kategori ? 'block' : 'none';
            });
        }
        
        function addToCart(id, nama, harga) {
            let item = cart.find(x => x.id_menu === id);
            if (item) {
                item.jumlah += 1;
            } else {
                cart.push({ id_menu: id, nama, harga, jumlah: 1, catatan: "" });
            }
            renderCart();
        }
        
        function updateJumlah(id, delta) {
            let item = cart.find(x => x.id_menu === id);
            if (item) {
                item.jumlah += delta;
                if (item.jumlah <= 0) cart = cart.filter(x => x.id_menu !== id);
            }
            renderCart();
        }
        
        function updateCatatan(id, catatan) {
            let item = cart.find(x => x.id_menu === id);
            if (item) {
                item.catatan = catatan;
                document.getElementById("cart_data").value = JSON.stringify(cart);
            }
        }
        
        function removeItem(id) {
            cart = cart.filter(x => x.id_menu !== id);
            renderCart();
        }
        
        function clearCart() {
            if (confirm('Kosongkan keranjang?')) {
                cart = [];
                renderCart();
            }
        }
        
        function renderCart() {
            const cartList = document.getElementById("cartList");
            const totalHarga = document.getElementById("totalHarga");
            const subtotal = document.getElementById("subtotal");
            const cartBadge = document.getElementById("cartBadge");
            const clearBtn = document.getElementById("clearBtn");
            const cartSection = document.getElementById("cartSection");
            
            if (cart.length === 0) {
                cartList.innerHTML = '<div class="cart-empty">Belum ada item.</div>';
                totalHarga.textContent = "Rp 0";
                subtotal.textContent = "Rp 0";
                cartBadge.textContent = "0";
                clearBtn.style.display = "none";
                cartSection.classList.add("hidden");
                checkFormValidity();
                return;
            }

            
            cartSection.classList.remove("hidden");

            let total = 0;
            cartList.innerHTML = "";
            
            cart.forEach(item => {
                total += item.harga * item.jumlah;
                cartList.innerHTML += `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <div>
                                <div class="cart-item-name">${item.nama}</div>
                                ${item.catatan ? `<div class="cart-item-note">Catatan: ${item.catatan}</div>` : ''}
                            </div>
                            <button type="button" class="cart-item-remove" onclick="removeItem(${item.id_menu})">×</button>
                        </div>
                        <div class="cart-item-controls">
                            <div class="qty-controls">
                                <button type="button" class="qty-btn" onclick="updateJumlah(${item.id_menu}, -1)">−</button>
                                <span class="qty-value">${item.jumlah}</span>
                                <button type="button" class="qty-btn" onclick="updateJumlah(${item.id_menu}, 1)">+</button>
                            </div>
                            <div class="item-price">Rp ${(item.harga * item.jumlah).toLocaleString()}</div>
                        </div>
                        <input type="text" 
                               class="cart-item-note-input" 
                               placeholder="Tambah catatan (opsional)..." 
                               value="${item.catatan || ''}"
                               onchange="updateCatatan(${item.id_menu}, this.value)">
                    </div>
                `;
            });
            
            totalHarga.textContent = "Rp " + total.toLocaleString();
            subtotal.textContent = "Rp " + total.toLocaleString();
            cartBadge.textContent = cart.length;
            clearBtn.style.display = "block";
            document.getElementById("cart_data").value = JSON.stringify(cart);
            checkFormValidity();
        }
        
        function selectPayment(metode, element) {
            document.querySelectorAll('.payment-card').forEach(card => card.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('metode_bayar').value = metode;
            checkFormValidity();
        }
        
        function checkFormValidity() {
            const jenisPesanan = document.getElementById('jenis_pesanan').value;
            const metodeBayar = document.getElementById('metode_bayar').value;
            const btnSubmit = document.getElementById('btnSubmit');
            
            if (cart.length > 0 && jenisPesanan && metodeBayar) {
                btnSubmit.disabled = false;
            } else {
                btnSubmit.disabled = true;
            }
        }
        
       
        document.getElementById('searchMenu').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.menu-card').forEach(card => {
                const nama = card.querySelector('.name').textContent.toLowerCase();
                const currentCategory = card.getAttribute('data-kategori');
                const activeCategory = document.querySelector('.tab-btn.active').textContent.toLowerCase();
                
                if (nama.includes(searchTerm) && activeCategory.includes(currentCategory)) {
                    card.style.display = 'block';
                } else if (searchTerm === '' && activeCategory.includes(currentCategory)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>