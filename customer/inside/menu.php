<?php
require '../../database/connect.php';

/**
 * Ambil data meja berdasarkan kode unik
 */
function getMejaByKode($kode_unik, $conn) {
    if (empty($kode_unik)) return null;

    $stmt = $conn->prepare("SELECT * FROM meja WHERE kode_unik = ?");
    $stmt->bind_param("s", $kode_unik);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// === Ambil parameter dari URL (mendukung beberapa nama param: kode, kode_unik, meja) ===
$param_meja_id = isset($_GET['meja']) ? (int)$_GET['meja'] : null;
$param_kode     = isset($_GET['kode']) ? $_GET['kode'] : (isset($_GET['kode_unik']) ? $_GET['kode_unik'] : null);
$kategori       = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';

// === Ambil data meja berdasarkan parameter yang tersedia ===
$mejaData = null;
try {
    if (!empty($param_kode)) {
        // pakai kode unik
        $mejaData = getMejaByKode($param_kode, $conn);
    } elseif (!empty($param_meja_id)) {
        // pakai id_meja
        $stmt_meja = $conn->prepare("SELECT * FROM meja WHERE id_meja = ?");
        $stmt_meja->bind_param('i', $param_meja_id);
        $stmt_meja->execute();
        $result_meja = $stmt_meja->get_result();
        $mejaData = $result_meja->fetch_assoc();
    }
} catch (Exception $e) {
    // kalau error query, log/atur default
    $mejaData = null;
}

// Tentukan nilai tampilan meja dan juga nilai parameter yang akan dipakai pada link
if ($mejaData) {
    // ganti 'nomor_meja' / 'nama_meja' sesuai kolom tabelmu
    $nomor_meja = isset($mejaData['nomor_meja']) ? $mejaData['nomor_meja'] : (isset($mejaData['nama_meja']) ? $mejaData['nama_meja'] : 'Meja');
    $id_meja    = isset($mejaData['id_meja']) ? $mejaData['id_meja'] : null;
    $kode_param = isset($mejaData['kode_unik']) ? $mejaData['kode_unik'] : $param_kode;
} else {
    $nomor_meja = 'Tidak diketahui';
    $id_meja    = $param_meja_id ?: null;
    $kode_param = $param_kode ?: null;
}

// === Ambil data menu berdasarkan kategori ===
try {
    if ($kategori === 'semua') {
        $query = "SELECT * FROM menu ORDER BY nama_menu ASC";
        $stmt = $conn->prepare($query);
    } else {
        $query = "SELECT * FROM menu WHERE kategori = ? ORDER BY nama_menu ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $kategori);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $menus = $result->fetch_all(MYSQLI_ASSOC);
    if (!is_array($menus)) $menus = [];
} catch (Exception $e) {
    $menus = [];
    // opsional: echo "Error: " . $e->getMessage();
}

// Helper: buat link kategori, pakai parameter 'kode' (sesuai home) jika tersedia, fallback ke 'meja'
function buildKategoriLink($kategoriValue, $id_meja, $kode_param) {
    $params = [];
    if (!empty($kode_param)) {
        // gunakan nama param 'kode' karena home mengirim ?kode=...
        $params[] = "kode=" . urlencode($kode_param);
    } elseif (!empty($id_meja)) {
        $params[] = "meja=" . intval($id_meja);
    }
    $params[] = "kategori=" . urlencode($kategoriValue);
    return '?' . implode('&', $params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Meja <?php echo htmlspecialchars($nomor_meja); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
            padding-bottom: 80px;
        }

        .main-wrapper {
            width: 100%;
        }

        .container {
            padding: 0;
        }

        /* Header */
        .header {
            background: white;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .back-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .header-title h1 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .header-title p {
            font-size: 14px;
            color: #666;
            margin-top: 2px;
        }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            gap: 8px;
            padding: 16px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            background: white;
        }

        .tab-navigation::-webkit-scrollbar {
            display: none;
        }

        .tab-item {
            padding: 10px 24px;
            border-radius: 25px;
            text-decoration: none;
            background: white;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .tab-item:hover {
            background: #f5f5f5;
        }

        .tab-item.active {
            background: #FF6B00;
            color: white;
            border-color: #FF6B00;
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            padding: 16px;
        }

        .menu-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        .menu-image {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f0f0f0;
        }

        .menu-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .menu-card:hover .menu-image img {
            transform: scale(1.05);
        }

        .no-image {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
        }

        .status-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .menu-info {
            padding: 16px;
        }

        .menu-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .menu-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-price {
            font-size: 16px;
            font-weight: 600;
            color: #FF6B00;
        }

        .add-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #FF6B00;
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 107, 0, 0.3);
        }

        .add-btn:hover {
            background: #E55D00;
            transform: scale(1.1);
        }

        .add-btn:active {
            transform: scale(0.95);
        }

        .add-btn.disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        .add-btn.disabled:hover {
            transform: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            font-size: 16px;
        }

        /* Cart Float Button (Mobile) */
        .cart-float-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #FF6B00;
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(255, 107, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 150;
            transition: all 0.3s ease;
        }

        .cart-float-btn.active {
            display: flex;
        }

        .cart-float-btn:hover {
            transform: scale(1.1);
        }

        .cart-float-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #E55D00;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            border: 2px solid white;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 300;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        /* Checkout Modal */
        .checkout-modal {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .checkout-header {
            padding: 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }

        .checkout-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .close-modal:hover {
            background: #f5f5f5;
            color: #333;
        }

        .checkout-content {
            padding: 24px;
        }

        /* Table Info */
        .table-info {
            background: #FFF5EE;
            border: 1px solid #FFE4CC;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-icon {
            width: 48px;
            height: 48px;
            background: #FF6B00;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .table-details h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .table-details p {
            font-size: 14px;
            color: #666;
        }

        /* Order Items */
        .order-section {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }

        .order-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f5f5;
            flex-shrink: 0;
        }

        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .order-item-price {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .order-item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-item-quantity {
            font-size: 14px;
            color: #FF6B00;
            font-weight: 600;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f5f5f5;
            border-radius: 20px;
            padding: 4px 8px;
        }

        .quantity-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: white;
            color: #FF6B00;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .quantity-btn:hover {
            background: #FF6B00;
            color: white;
            transform: scale(1.1);
        }

        .quantity-btn:active {
            transform: scale(0.95);
        }

        .quantity-number {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        /* Notes Section */
        .notes-section {
            margin-bottom: 24px;
        }

        .notes-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
            transition: border-color 0.2s;
        }

        .notes-input:focus {
            outline: none;
            border-color: #FF6B00;
        }

        /* Payment Method */
        .payment-section {
            margin-bottom: 24px;
        }

        .payment-options {
            display: grid;
            gap: 12px;
        }

        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-option:hover {
            border-color: #FF6B00;
            background: #FFF5EE;
        }

        .payment-option.selected {
            border-color: #FF6B00;
            background: #FFF5EE;
        }

        .payment-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 50%;
            position: relative;
            transition: all 0.2s;
        }

        .payment-option.selected .payment-radio {
            border-color: #FF6B00;
        }

        .payment-radio::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            width: 10px;
            height: 10px;
            background: #FF6B00;
            border-radius: 50%;
            transition: transform 0.2s;
        }

        .payment-option.selected .payment-radio::after {
            transform: translate(-50%, -50%) scale(1);
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .payment-info {
            flex: 1;
        }

        .payment-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }

        .payment-desc {
            font-size: 12px;
            color: #666;
        }

        /* Order Summary */
        .order-summary {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 8px;
            border-top: 1px solid #e0e0e0;
            font-weight: 600;
            font-size: 16px;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            color: #333;
            font-weight: 600;
        }

        .summary-row:last-child .summary-value {
            color: #FF6B00;
        }

        /* Submit Button */
        .submit-order-btn {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            background: #FF6B00;
            color: white;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
        }

        .submit-order-btn:hover {
            background: #E55D00;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 107, 0, 0.4);
        }

        .submit-order-btn:active {
            transform: translateY(0);
        }

        .submit-order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 12px;
            }

            .menu-image {
                height: 160px;
            }

            .menu-name {
                font-size: 14px;
            }

            .menu-price {
                font-size: 14px;
            }

            .add-btn {
                width: 36px;
                height: 36px;
                font-size: 20px;
            }

            .checkout-modal {
                max-width: 100%;
                border-radius: 20px 20px 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="container">
            <header class="header">
                <button class="back-btn" onclick="window.history.back()" aria-label="Kembali">
                    ←
                </button>
                <div class="header-title">
                    <h1>Menu</h1>
                    <p>Meja <?php echo htmlspecialchars($nomor_meja); ?></p>
                </div>
            </header>

            <nav class="tab-navigation" aria-label="Kategori Menu">
                <a href="<?php echo buildKategoriLink('semua', $id_meja, $kode_param); ?>" 
                   class="tab-item <?php echo $kategori == 'semua' ? 'active' : ''; ?>">Semua</a>

                <a href="<?php echo buildKategoriLink('makanan', $id_meja, $kode_param); ?>" 
                   class="tab-item <?php echo $kategori == 'makanan' ? 'active' : ''; ?>">Makanan</a>

                <a href="<?php echo buildKategoriLink('minuman', $id_meja, $kode_param); ?>" 
                   class="tab-item <?php echo $kategori == 'minuman' ? 'active' : ''; ?>">Minuman</a>

                <a href="<?php echo buildKategoriLink('lainnya', $id_meja, $kode_param); ?>" 
                   class="tab-item <?php echo $kategori == 'lainnya' ? 'active' : ''; ?>">Camilan</a>
            </nav>

            <div class="menu-grid">
                <?php if (count($menus) > 0): ?>
                    <?php foreach ($menus as $menu): ?>
                        <div class="menu-card">
                            <div class="menu-image">
                                <?php if (!empty($menu['gambar'])): ?>
                                    <img src="../../assets/uploads/<?php echo htmlspecialchars($menu['gambar']); ?>" 
                                         alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>">
                                <?php else: ?>
                                    <div class="no-image">Tidak ada gambar</div>
                                <?php endif; ?>

                                <?php if (isset($menu['status_menu']) && $menu['status_menu'] == 'nonaktif'): ?>
                                    <div class="status-badge">Tidak Tersedia</div>
                                <?php endif; ?>
                            </div>

                            <div class="menu-info">
                                <h3 class="menu-name"><?php echo htmlspecialchars($menu['nama_menu']); ?></h3>
                                <div class="menu-footer">
                                    <span class="menu-price">Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></span>

                                    <?php if (isset($menu['status_menu']) && $menu['status_menu'] == 'aktif'): ?>
                                        <button class="add-btn" onclick='addToCart(<?php echo json_encode([
                                            "id" => $menu["id_menu"],
                                            "nama" => $menu["nama_menu"],
                                            "harga" => $menu["harga"],
                                            "gambar" => $menu["gambar"]
                                        ]); ?>)'>+</button>
                                    <?php else: ?>
                                        <button class="add-btn disabled" disabled>+</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Tidak ada menu tersedia untuk kategori ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cart Float Button -->
        <button class="cart-float-btn" id="cartFloatBtn" onclick="openCheckout()">
            🛒
            <span class="cart-float-badge" id="cartFloatBadge">0</span>
        </button>

        <!-- Checkout Modal -->
        <div class="modal-overlay" id="checkoutModal">
            <div class="checkout-modal">
                <div class="checkout-header">
                    <h2>Detail Pesanan</h2>
                    <button class="close-modal" onclick="closeCheckout()">×</button>
                </div>

                <div class="checkout-content">
                    <!-- Table Info -->
                    <div class="table-info">
                        <div class="table-icon">🪑</div>
                        <div class="table-details">
                            <h3>Meja <?php echo htmlspecialchars($nomor_meja); ?></h3>
                            <p>Pesanan Anda akan diantar ke meja ini</p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="order-section">
                        <h3 class="section-title">Pesanan Anda</h3>
                        <div id="checkoutItems"></div>
                    </div>

                    <!-- Notes -->
                    <div class="notes-section">
                        <h3 class="section-title">Catatan (Opsional)</h3>
                        <textarea 
                            class="notes-input" 
                            id="orderNotes" 
                            placeholder="Contoh: Tidak pakai cabai, level pedas sedang, dll..."></textarea>
                    </div>

                    <!-- Payment Method -->
                    <div class="payment-section">
                        <h3 class="section-title">Metode Pembayaran</h3>
                        <div class="payment-options">
                            <div class="payment-option" data-payment="cash" onclick="selectPayment('cash')">
                                <div class="payment-radio"></div>
                                <div class="payment-icon">💵</div>
                                <div class="payment-info">
                                    <div class="payment-name">Cash</div>
                                    <div class="payment-desc">Bayar di kasir setelah makan</div>
                                </div>
                            </div>
                            <div class="payment-option" data-payment="qris" onclick="selectPayment('qris')">
                                <div class="payment-radio"></div>
                                <div class="payment-icon">📱</div>
                                <div class="payment-info">
                                    <div class="payment-name">QRIS</div>
                                    <div class="payment-desc">Bayar sekarang dengan scan QRIS</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value" id="checkoutSubtotal">Rp 0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total Item</span>
                            <span class="summary-value" id="checkoutTotalItems">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total Pembayaran</span>
                            <span class="summary-value" id="checkoutTotal">Rp 0</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button class="submit-order-btn" id="submitOrderBtn" onclick="submitOrder()" disabled>
                        Pilih Metode Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let selectedPayment = null;
        const mejaNumber = '<?php echo htmlspecialchars($nomor_meja); ?>';

        function addToCart(menu) {
            const existingItem = cart.find(item => item.id === menu.id);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({...menu, quantity: 1});
            }
            updateCartButton();
        }



        function updateCartButton() {
            const cartFloatBtn = document.getElementById('cartFloatBtn');
            const cartFloatBadge = document.getElementById('cartFloatBadge');

            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            if (totalItems > 0) {
                cartFloatBtn.classList.add('active');
                cartFloatBadge.textContent = totalItems;
            } else {
                cartFloatBtn.classList.remove('active');
                closeCheckout();
            }
        }

        function openCheckout() {
            if (cart.length === 0) {
                alert('Keranjang kosong! Silakan pilih menu terlebih dahulu.');
                return;
            }
            
            const modal = document.getElementById('checkoutModal');
            modal.classList.add('active');
            updateCheckoutModal();
        }

        function closeCheckout() {
            const modal = document.getElementById('checkoutModal');
            modal.classList.remove('active');
        }

        function updateCheckoutModal() {
            const checkoutItems = document.getElementById('checkoutItems');
            const checkoutSubtotal = document.getElementById('checkoutSubtotal');
            const checkoutTotalItems = document.getElementById('checkoutTotalItems');
            const checkoutTotal = document.getElementById('checkoutTotal');

            let totalItems = 0;
            let subtotal = 0;

            checkoutItems.innerHTML = cart.map(item => {
                totalItems += item.quantity;
                const itemTotal = item.harga * item.quantity;
                subtotal += itemTotal;
                
                const imagePath = item.gambar ? `../../assets/uploads/${item.gambar}` : '';
                
                return `
                    <div class="order-item">
                        <div class="order-item-image">
                            ${item.gambar ? `<img src="${imagePath}" alt="${item.nama}">` : '<div class="no-image">No Image</div>'}
                        </div>
                        <div class="order-item-details">
                            <div class="order-item-name">${item.nama}</div>
                            <div class="order-item-price">Rp ${item.harga.toLocaleString('id-ID')}</div>
                            <div class="order-item-footer">
                                <div class="order-item-quantity">Rp ${itemTotal.toLocaleString('id-ID')}</div>
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="decreaseQuantity(${item.id})">−</button>
                                    <span class="quantity-number">${item.quantity}</span>
                                    <button class="quantity-btn" onclick="increaseQuantity(${item.id})">+</button>
                                </div>
                            </div>
                        </div>
                    </div>`;
            }).join('');

            checkoutSubtotal.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            checkoutTotalItems.textContent = totalItems + ' item';
            checkoutTotal.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            
            updateCartButton();
        }

        function increaseQuantity(menuId) {
            const item = cart.find(i => i.id === menuId);
            if (item) {
                item.quantity++;
                updateCheckoutModal();
            }
        }

        function decreaseQuantity(menuId) {
            const itemIndex = cart.findIndex(i => i.id === menuId);
            if (itemIndex !== -1) {
                if (cart[itemIndex].quantity > 1) {
                    cart[itemIndex].quantity--;
                } else {
                    // Konfirmasi sebelum menghapus item
                    if (confirm(`Hapus ${cart[itemIndex].nama} dari keranjang?`)) {
                        cart.splice(itemIndex, 1);
                    }
                }
                updateCheckoutModal();
            }
        }

        function selectPayment(method) {
            selectedPayment = method;
            
            // Update visual selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelector(`[data-payment="${method}"]`).classList.add('selected');
            
            // Update button
            const submitBtn = document.getElementById('submitOrderBtn');
            submitBtn.disabled = false;
            
            if (method === 'cash') {
                submitBtn.textContent = 'Konfirmasi Pesanan - Bayar di Kasir';
            } else if (method === 'qris') {
                submitBtn.textContent = 'Lanjut ke Pembayaran QRIS';
            }
        }

        function submitOrder() {
            if (!selectedPayment) {
                alert('Silakan pilih metode pembayaran terlebih dahulu!');
                return;
            }

            const notes = document.getElementById('orderNotes').value;
            const totalAmount = cart.reduce((sum, item) => sum + (item.harga * item.quantity), 0);
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

            // Disable button saat proses
            const submitBtn = document.getElementById('submitOrderBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses pesanan...';

            // Data pesanan
            const orderData = {
                meja: mejaNumber,
                id_meja: <?php echo json_encode($id_meja); ?>,
                kode_param: <?php echo json_encode($kode_param); ?>,
                items: cart,
                notes: notes,
                payment_method: selectedPayment,
                total_amount: totalAmount,
                total_items: totalItems
            };

            // Debug: log data yang akan dikirim
            console.log('Sending order data:', orderData);

            // Kirim data ke server menggunakan fetch API
            fetch('../include/pesanan_f.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    if (selectedPayment === 'cash') {
                        alert(`Pesanan berhasil dikonfirmasi!\n\nNomor Pesanan: ${data.order_id}\nMeja: ${mejaNumber}\nTotal: Rp ${totalAmount.toLocaleString('id-ID')}\nJumlah Item: ${totalItems}\n\nSilakan bayar di kasir setelah selesai makan.`);
                        
                        // Reset cart dan tutup modal
                        resetOrder();
                        
                    } else if (selectedPayment === 'qris') {
                        // Redirect ke halaman pembayaran QRIS
                        window.location.href = `payment_qris.php?order_id=${data.order_id}`;
                    }
                } else {
                    alert('Gagal memproses pesanan: ' + (data.message || 'Terjadi kesalahan'));
                    submitBtn.disabled = false;
                    if (selectedPayment === 'cash') {
                        submitBtn.textContent = 'Konfirmasi Pesanan - Bayar di Kasir';
                    } else {
                        submitBtn.textContent = 'Lanjut ke Pembayaran QRIS';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.');
                submitBtn.disabled = false;
                if (selectedPayment === 'cash') {
                    submitBtn.textContent = 'Konfirmasi Pesanan - Bayar di Kasir';
                } else {
                    submitBtn.textContent = 'Lanjut ke Pembayaran QRIS';
                }
            });
        }

        function resetOrder() {
            cart = [];
            selectedPayment = null;
            document.getElementById('orderNotes').value = '';
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            const submitBtn = document.getElementById('submitOrderBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Pilih Metode Pembayaran';
            closeCheckout();
            updateCartButton();
        }

        // Close modal when clicking outside
        document.getElementById('checkoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCheckout();
            }
        });
    </script>
</body>
</html>