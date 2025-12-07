<?php
require '../../database/connect.php';
$param_meja_id = isset($_GET['meja']) ? (int)$_GET['meja'] : null;
$param_kode     = isset($_GET['kode']) ? $_GET['kode'] : (isset($_GET['kode_unik']) ? $_GET['kode_unik'] : null);
$kategori       = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';
function getMejaByKode($kode_unik, $conn) {
    if (empty($kode_unik)) return null;
    $stmt = $conn->prepare("SELECT * FROM meja WHERE kode_unik = ?");
    $stmt->bind_param("s", $kode_unik);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
$mejaData = null;
try {
    if (!empty($param_kode)) {
        $mejaData = getMejaByKode($param_kode, $conn);
    } elseif (!empty($param_meja_id)) {
        $stmt_meja = $conn->prepare("SELECT * FROM meja WHERE id_meja = ?");
        $stmt_meja->bind_param('i', $param_meja_id);
        $stmt_meja->execute();
        $result_meja = $stmt_meja->get_result();
        $mejaData = $result_meja->fetch_assoc();
    }
} catch (Exception $e) {
    $mejaData = null;
}
if ($mejaData) {
    $nomor_meja = isset($mejaData['nomor_meja']) ? $mejaData['nomor_meja'] : (isset($mejaData['nama_meja']) ? $mejaData['nama_meja'] : 'Meja');
    $id_meja    = isset($mejaData['id_meja']) ? $mejaData['id_meja'] : null;
    $kode_param = isset($mejaData['kode_unik']) ? $mejaData['kode_unik'] : $param_kode;
} else {
    $nomor_meja = 'Tidak diketahui';
    $id_meja    = $param_meja_id ?: null;
    $kode_param = $param_kode ?: null;
}
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
}
function buildKategoriLink($kategoriValue, $id_meja, $kode_param) {
    $params = [];
    if (!empty($kode_param)) {
        $params[] = "kode=" . urlencode($kode_param);
    } elseif (!empty($id_meja)) {
        $params[] = "meja=" . intval($id_meja);
    }
    $params[] = "kategori=" . urlencode($kategoriValue);
    return '?' . implode('&', $params);
}
$sql_qris = "SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = 'qris_image_path'";
$result_qris = mysqli_query($conn, $sql_qris);
$data_qris = mysqli_fetch_assoc($result_qris);
$qris_path = $data_qris['nilai_pengaturan'] ?? '../../assets/uploads/payment_qris/qris.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Meja <?php echo htmlspecialchars($nomor_meja); ?></title>
    <link rel="stylesheet" href="../../css/customer/menu.css">
    <style>
        .search-container {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
            width: 200px;
            outline: none;
            transition: all 0.3s;
        }
        .search-input:focus {
            border-color: #FF6B00;
            box-shadow: 0 0 5px rgba(255, 107, 0, 0.2);
        }
        .search-btn {
            background: #FF6B00;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .search-btn:hover {
            background: #e55f00;
        }
        .header {
            position: relative;
        }
        .menu-card.hidden {
            display: none;
        }
    </style>
</head>
<script src="../geofence/geofence.js"></script>
<body>
    <div class="main-wrapper">
        <div class="container">
            <header class="header">
                <button class="back-btn" onclick="window.history.back()" aria-label="Kembali">←</button>
                <div class="header-title">
                    <h1>Menu</h1>
                    <p>Meja <?php echo htmlspecialchars($nomor_meja); ?></p>
                </div>
                <div class="search-container">
                    <button class="search-btn" onclick="searchMenu()">🔍</button>
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari menu..." onkeyup="searchMenu()">
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
                        <div class="menu-card" data-menu-name="<?php echo strtolower(htmlspecialchars($menu['nama_menu'])); ?>">
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

        <button class="cart-float-btn" id="cartFloatBtn" onclick="openCheckout()">
            🛒
            <span class="cart-float-badge" id="cartFloatBadge">0</span>
        </button>

        <div class="modal-overlay" id="checkoutModal">
            <div class="checkout-modal">
                <div class="checkout-header">
                    <h2>Detail Pesanan</h2>
                    <button class="close-modal" onclick="closeCheckout()">×</button>
                </div>
                <div class="checkout-content">
                    <div class="table-info">
                        <div class="table-icon">🪑</div>
                        <div class="table-details">
                            <h3>Meja <?php echo htmlspecialchars($nomor_meja); ?></h3>
                            <p>Pesanan Anda akan diantar ke meja ini</p>
                        </div>
                    </div>
                    <div class="order-section">
                        <h3 class="section-title">Pesanan Anda</h3>
                        <div id="checkoutItems"></div>
                    </div>
                    <div class="notes-section">
                        <h3 class="section-title">Catatan (Opsional)</h3>
                        <textarea class="notes-input" id="orderNotes" placeholder="Contoh: Tidak pakai cabai, level pedas sedang, dll..."></textarea>
                    </div>
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
                    <button class="submit-order-btn" id="submitOrderBtn" onclick="submitOrder()" disabled>
                        Pilih Metode Pembayaran
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="successModal">
            <div class="checkout-modal">
                <div class="checkout-header">
                    <h2>✅ Pesanan Berhasil!</h2>
                    <button class="close-modal" onclick="closeSuccessModal()">×</button>
                </div>
                <div class="checkout-content">
                    <div class="success-info">
                        <div class="success-icon">🎉</div>
                        <h3>Pesanan Anda Telah Dikonfirmasi</h3>
                        <p>Silakan tunggu, pesanan Anda sedang diproses</p>
                    </div>
                    <div class="order-details">
                        <h4>Detail Pesanan</h4>
                        <div class="detail-row">
                            <span>Nomor Pesanan:</span>
                            <strong id="successOrderId">-</strong>
                        </div>
                        <div class="detail-row">
                            <span>Meja:</span>
                            <strong id="successTableNumber">-</strong>
                        </div>
                        <div class="detail-row">
                            <span>Total Pembayaran:</span>
                            <strong id="successTotalAmount" style="color: #FF6B00;">-</strong>
                        </div>
                        <div class="detail-row">
                            <span>Jumlah Item:</span>
                            <strong id="successTotalItems">-</strong>
                        </div>
                        <div class="detail-row">
                            <span>Metode Pembayaran:</span>
                            <strong>Cash (Bayar di Kasir)</strong>
                        </div>
                    </div>
                    <div class="success-note">
                        <p>💡 <strong>Catatan:</strong> Silakan bayar di kasir setelah selesai makan</p>
                    </div>
                    <button class="submit-order-btn" onclick="closeSuccessModal()">Tutup</button>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="qrisModal">
            <div class="checkout-modal">
                <div class="checkout-header">
                    <h2>Pembayaran QRIS</h2>
                </div>
                <div class="checkout-content">
                    <div class="qris-container">
                        <h3>Scan QRIS untuk Membayar</h3>
                    <div class="qris-image-wrapper">
                    <?php if ($qris_path && file_exists($qris_path)): ?>
                        <img src="<?php echo $qris_path; ?>?v=<?php echo time(); ?>" alt="QRIS Code" class="qris-image">
                    <?php else: ?>
                        <p>QRIS belum tersedia</p>
                    <?php endif; ?>
                </div>
                        <div class="qris-amount">
                            <span>Total Pembayaran:</span>
                            <strong id="qrisTotalAmount">Rp 0</strong>
                        </div>

                        <div class="upload-section">
                            <h4>Upload Bukti Pembayaran</h4>
                            <p class="upload-note">Silakan upload screenshot atau foto bukti transfer Anda</p>
                            <div class="file-upload-wrapper">
                                <input type="file" id="paymentProof" accept="image/*" onchange="previewPaymentProof(this)">
                                <label for="paymentProof" class="file-upload-label">
                                    <span id="fileUploadText">📷 Pilih File Gambar</span>
                                </label>
                            </div>
                            <div id="imagePreview" class="image-preview"></div>
                        </div>

                        <button class="submit-order-btn" id="uploadProofBtn" onclick="uploadPaymentProof()" disabled>
                            Upload Bukti Pembayaran
                        </button>

                        <div class="qris-status" id="qrisStatusSection" style="display: none;">
                            <div class="status-indicator" id="qrisStatusIndicator">
                    <div id="qrisStatusIcon" class="loading-spinner"></div>
            </div>
                <p id="qrisStatusText">Menunggu konfirmasi kasir...</p>
            </div>

            <div class="qris-note">
                <p>⏳ Setelah mengupload bukti pembayaran, mohon tunggu kasir mengkonfirmasi pembayaran Anda</p>
        </div>

            <button class="submit-order-btn" id="qrisContinueBtn" onclick="completeQrisPayment()" disabled style="display: none;">
            Lanjutkan
        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="qrisSuccessModal">
            <div class="checkout-modal">
                <div class="checkout-header">
                    <h2>✅ Pembayaran Berhasil!</h2>
                </div>
                <div class="checkout-content">
                    <div class="success-info">
                        <div class="success-icon">💳</div>
                        <h3>Pembayaran Telah Dikonfirmasi</h3>
                        <p>Terima kasih! Pesanan Anda sedang diproses</p>
                    </div>
                    <div class="order-details">
                        <h4>Detail Pesanan</h4>
                        <div class="detail-row">
                            <span>Nomor Pesanan:</span>
                            <strong id="qrisSuccessOrderId">-</strong>
                        </div>
                        <div class="detail-row">
                            <span>Meja:</span>
                            <strong id="qrisSuccessTableNumber">-</strong>
                        </div>
                        <div class="detail-row">
                            <span>Total Pembayaran:</span>
                            <strong id="qrisSuccessTotalAmount" style="color: #FF6B00;">-</strong>
                        </div>
                        <div class="detail-row">
                            <span>Status Pembayaran:</span>
                            <strong style="color: #28a745;">Sudah Dibayar (QRIS)</strong>
                        </div>
                    </div>
                    <div class="receipt-section">
                        <button class="download-receipt-btn" id="downloadReceiptBtn" onclick="downloadReceipt()">
                            📄 Download Struk
                        </button>
                        <p class="receipt-note">⚠️ <strong>Wajib:</strong> Download dan tunjukkan struk ini ke kasir sebagai bukti pembayaran setelah selesai makan</p>
                    </div>
                    <button class="submit-order-btn" id="closeQrisSuccessBtn" onclick="attemptCloseQrisSuccess()" disabled>
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
let cart = [];
let selectedPayment = null;
let currentOrderId = null;
let checkPaymentInterval = null;
let receiptDownloaded = false;
let selectedProofFile = null;
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
    document.getElementById('checkoutModal').classList.add('active');
    updateCheckoutModal();
}
function closeCheckout() {
    document.getElementById('checkoutModal').classList.remove('active');
}
function updateCheckoutModal() {
    const checkoutItems = document.getElementById('checkoutItems');
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
                            <button class="quantity-btn" onclick="decreaseQuantity('${item.id}')">−</button>
                            <span class="quantity-number">${item.quantity}</span>
                            <button class="quantity-btn" onclick="increaseQuantity('${item.id}')">+</button>
                        </div>
                    </div>
                </div>
            </div>`;
    }).join('');
    document.getElementById('checkoutSubtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('checkoutTotalItems').textContent = totalItems + ' item';
    document.getElementById('checkoutTotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
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
            if (confirm(`Hapus ${cart[itemIndex].nama} dari keranjang?`)) {
                cart.splice(itemIndex, 1);
            }
        }
        updateCheckoutModal();
    }
}
function selectPayment(method) {
    selectedPayment = method;
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
    });
    document.querySelector(`[data-payment="${method}"]`).classList.add('selected');
    const submitBtn = document.getElementById('submitOrderBtn');
    submitBtn.disabled = false;
    submitBtn.textContent = method === 'cash' ? 'Konfirmasi Pesanan - Bayar di Kasir' : 'Lanjut ke Pembayaran QRIS';
}
function submitOrder() {
    if (!selectedPayment) {
        alert('Silakan pilih metode pembayaran terlebih dahulu!');
        return;
    }
    if (checkPaymentInterval) {
        clearInterval(checkPaymentInterval);
        checkPaymentInterval = null;
    }
    currentOrderId = null;
    selectedProofFile = null;
    const notes = document.getElementById('orderNotes').value;
    const totalAmount = cart.reduce((sum, item) => sum + (item.harga * item.quantity), 0);
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const submitBtn = document.getElementById('submitOrderBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Memproses pesanan...';
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
    fetch('../include/pesanan_f.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(orderData)
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                currentOrderId = data.order_id;
                if (selectedPayment === 'cash') {
                    showSuccessModal(data.order_id, totalAmount, totalItems);
                } else if (selectedPayment === 'qris') {
                    showQrisModal(data.order_id, totalAmount);
                }
            } else {
                alert('Gagal memproses pesanan: ' + (data.message || 'Terjadi kesalahan'));
                submitBtn.disabled = false;
                submitBtn.textContent = selectedPayment === 'cash' ? 'Konfirmasi Pesanan - Bayar di Kasir' : 'Lanjut ke Pembayaran QRIS';
            }
        } catch (parseError) {
            alert('Terjadi kesalahan saat memproses respons server. Silakan coba lagi.');
            submitBtn.disabled = false;
            submitBtn.textContent = selectedPayment === 'cash' ? 'Konfirmasi Pesanan - Bayar di Kasir' : 'Lanjut ke Pembayaran QRIS';
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.');
        submitBtn.disabled = false;
        submitBtn.textContent = selectedPayment === 'cash' ? 'Konfirmasi Pesanan - Bayar di Kasir' : 'Lanjut ke Pembayaran QRIS';
    });
}
function showSuccessModal(orderId, totalAmount, totalItems) {
    closeCheckout();
    document.getElementById('successOrderId').textContent = orderId;
    document.getElementById('successTableNumber').textContent = mejaNumber;
    document.getElementById('successTotalAmount').textContent = 'Rp ' + totalAmount.toLocaleString('id-ID');
    document.getElementById('successTotalItems').textContent = totalItems + ' item';
    document.getElementById('successModal').classList.add('active');
}
function closeSuccessModal() {
    document.getElementById('successModal').classList.remove('active');
    resetOrder();
}
function showQrisModal(orderId, totalAmount) {
    closeCheckout();
    currentOrderId = orderId;
    document.getElementById('qrisTotalAmount').textContent = 'Rp ' + totalAmount.toLocaleString('id-ID');
    document.getElementById('qrisModal').classList.add('active');
    selectedProofFile = null;
    document.getElementById('paymentProof').value = '';
    document.getElementById('fileUploadText').textContent = '📷 Pilih File Gambar';
    document.getElementById('imagePreview').innerHTML = '';
    const uploadBtn = document.getElementById('uploadProofBtn');
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Upload Bukti Pembayaran';
    uploadBtn.style.background = '#FF6B00';
    const continueBtn = document.getElementById('qrisContinueBtn');
    continueBtn.disabled = true;
    continueBtn.style.display = 'none';
    const statusSection = document.getElementById('qrisStatusSection');
    const statusIcon = document.getElementById('qrisStatusIcon');
    const statusText = document.getElementById('qrisStatusText');
    statusSection.style.display = 'none';
    if (statusIcon) {
        statusIcon.className = 'loading-spinner';
        statusIcon.innerHTML = '';
    }
    if (statusText) {
        statusText.textContent = 'Menunggu konfirmasi kasir...';
        statusText.style.color = '#666';
    }
    if (checkPaymentInterval) {
        clearInterval(checkPaymentInterval);
        checkPaymentInterval = null;
    }
}
function previewPaymentProof(input) {
    const uploadBtn = document.getElementById('uploadProofBtn');
    const preview = document.getElementById('imagePreview');
    const fileText = document.getElementById('fileUploadText');
    if (input.files && input.files[0]) {
        selectedProofFile = input.files[0];
        if (!selectedProofFile.type.match('image.*')) {
            alert('Hanya file gambar yang diperbolehkan!');
            input.value = '';
            selectedProofFile = null;
            uploadBtn.disabled = true;
            return;
        }
        if (selectedProofFile.size > 5 * 1024 * 1024) {
            alert('Ukuran file maksimal 5MB!');
            input.value = '';
            selectedProofFile = null;
            uploadBtn.disabled = true;
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview Bukti Pembayaran">`;
            fileText.textContent = '✓ ' + selectedProofFile.name;
            uploadBtn.disabled = false;
        };
        reader.readAsDataURL(selectedProofFile);
    } else {
        selectedProofFile = null;
        preview.innerHTML = '';
        fileText.textContent = '📷 Pilih File Gambar';
        uploadBtn.disabled = true;
    }
}
function uploadPaymentProof() {
    if (!selectedProofFile) {
        alert('Silakan pilih file bukti pembayaran terlebih dahulu!');
        return;
    }
    const uploadBtn = document.getElementById('uploadProofBtn');
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Mengupload...';
    const formData = new FormData();
    formData.append('payment_proof', selectedProofFile);
    formData.append('order_id', currentOrderId);
    fetch('../include/upload_payment_proof.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            uploadBtn.textContent = '✓ Bukti Berhasil Diupload';
            uploadBtn.style.background = '#28a745';
            document.getElementById('qrisStatusSection').style.display = 'block';
            document.getElementById('qrisContinueBtn').style.display = 'block';
            startPaymentStatusCheck(currentOrderId);
        } else {
            alert('Gagal mengupload bukti pembayaran: ' + (data.message || 'Terjadi kesalahan'));
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload Bukti Pembayaran';
            uploadBtn.style.background = '#FF6B00';
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan saat mengupload. Silakan coba lagi.');
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload Bukti Pembayaran';
        uploadBtn.style.background = '#FF6B00';
    });
}
function startPaymentStatusCheck(orderId) {
    if (checkPaymentInterval) {
        clearInterval(checkPaymentInterval);
        checkPaymentInterval = null;
    }
    const statusIcon = document.getElementById('qrisStatusIcon');
    const statusText = document.getElementById('qrisStatusText');
    if (statusIcon) {
        statusIcon.className = 'loading-spinner';
        statusIcon.innerHTML = '';
    }
    if (statusText) {
        statusText.textContent = 'Menunggu konfirmasi kasir...';
        statusText.style.color = '#666';
    }
    checkPaymentInterval = setInterval(function() {
        fetch(`../include/check_payment_status.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.status === 'confirmed') {
                        clearInterval(checkPaymentInterval);
                        checkPaymentInterval = null;
                        const statusIconNow = document.getElementById('qrisStatusIcon');
                        const statusTextNow = document.getElementById('qrisStatusText');
                        if (statusIconNow) {
                            statusIconNow.className = 'success-check';
                            statusIconNow.innerHTML = '✓';
                        }
                        if (statusTextNow) {
                            statusTextNow.textContent = 'Pembayaran dikonfirmasi!';
                            statusTextNow.style.color = '#28a745';
                        }
                        completeQrisPayment();
                        setTimeout(() => {
                            downloadReceipt();
                        }, 500);
                    } else if (data.status === 'rejected') {
                        clearInterval(checkPaymentInterval);
                        checkPaymentInterval = null;
                        const statusIconNow = document.getElementById('qrisStatusIcon');
                        const statusTextNow = document.getElementById('qrisStatusText');
                        if (statusIconNow) {
                            statusIconNow.className = 'error-mark';
                            statusIconNow.innerHTML = '✗';
                        }
                        if (statusTextNow) {
                            statusTextNow.textContent = 'Pembayaran ditolak oleh kasir';
                            statusTextNow.style.color = '#dc3545';
                        }
                        setTimeout(() => {
                            document.getElementById('qrisModal').classList.remove('active');
                            alert('Pembayaran ditolak oleh kasir. Silakan coba lagi atau hubungi kasir.');
                            resetOrder();
                        }, 2000);
                    }
                }
            })
            .catch(error => {
            });
    }, 3000);
}
function cancelQrisPayment() {
    if (checkPaymentInterval) {
        clearInterval(checkPaymentInterval);
        checkPaymentInterval = null;
    }
    document.getElementById('qrisModal').classList.remove('active');
    const submitBtn = document.getElementById('submitOrderBtn');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Lanjut ke Pembayaran QRIS';
}
function completeQrisPayment() {
    if (checkPaymentInterval) {
        clearInterval(checkPaymentInterval);
        checkPaymentInterval = null;
    }
    document.getElementById('qrisModal').classList.remove('active');
    const totalAmount = cart.reduce((sum, item) => sum + (item.harga * item.quantity), 0);
    document.getElementById('qrisSuccessOrderId').textContent = currentOrderId;
    document.getElementById('qrisSuccessTableNumber').textContent = mejaNumber;
    document.getElementById('qrisSuccessTotalAmount').textContent = 'Rp ' + totalAmount.toLocaleString('id-ID');
    receiptDownloaded = false;
    document.getElementById('closeQrisSuccessBtn').disabled = true;
    document.getElementById('closeQrisSuccessBtn').textContent = 'Download Struk Terlebih Dahulu';
    document.getElementById('qrisSuccessModal').classList.add('active');
}
function downloadReceipt() {
    const totalAmount = cart.reduce((sum, item) => sum + (item.harga * item.quantity), 0);
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    let receiptContent = `
=========================================
           STRUK PEMBAYARAN
=========================================

Nomor Pesanan: ${currentOrderId}
Meja: ${mejaNumber}
Tanggal: ${new Date().toLocaleString('id-ID')}

-----------------------------------------
DETAIL PESANAN:
-----------------------------------------
`;
    cart.forEach(item => {
        const itemTotal = item.harga * item.quantity;
        receiptContent += `${item.nama}\n`;
        receiptContent += `  ${item.quantity} x Rp ${item.harga.toLocaleString('id-ID')} = Rp ${itemTotal.toLocaleString('id-ID')}\n\n`;
    });
    receiptContent += `-----------------------------------------
Total Item: ${totalItems}
Total Pembayaran: Rp ${totalAmount.toLocaleString('id-ID')}

Metode Pembayaran: QRIS
Status: LUNAS

=========================================
  Terima kasih atas kunjungan Anda!
  Tunjukkan struk ini ke kasir
=========================================
`;
    const blob = new Blob([receiptContent], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Struk_Pesanan_${currentOrderId}.txt`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    receiptDownloaded = true;
    const closeBtn = document.getElementById('closeQrisSuccessBtn');
    closeBtn.disabled = false;
    closeBtn.textContent = 'Selesai';
    const downloadBtn = document.getElementById('downloadReceiptBtn');
    downloadBtn.textContent = '✓ Struk Sudah Didownload';
    downloadBtn.style.background = '#28a745';
}
function attemptCloseQrisSuccess() {
    if (!receiptDownloaded) {
        alert('Anda harus mendownload struk terlebih dahulu sebelum menutup!');
        return;
    }
    document.getElementById('qrisSuccessModal').classList.remove('active');
    resetOrder();
}
function resetOrder() {
    if (checkPaymentInterval) {
        clearInterval(checkPaymentInterval);
        checkPaymentInterval = null;
    }
    cart = [];
    selectedPayment = null;
    currentOrderId = null;
    receiptDownloaded = false;
    selectedProofFile = null;
    document.getElementById('orderNotes').value = '';
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
    });
    const submitBtn = document.getElementById('submitOrderBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Pilih Metode Pembayaran';
    const uploadBtn = document.getElementById('uploadProofBtn');
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Upload Bukti Pembayaran';
        uploadBtn.style.background = '#FF6B00';
    }
    const continueBtn = document.getElementById('qrisContinueBtn');
    if (continueBtn) {
        continueBtn.disabled = true;
        continueBtn.style.display = 'none';
    }
    const downloadBtn = document.getElementById('downloadReceiptBtn');
    if (downloadBtn) {
        downloadBtn.textContent = 'Download Struk Pembayaran';
        downloadBtn.style.background = '#FF6B00';
    }
    closeCheckout();
    updateCartButton();
}
document.getElementById('checkoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCheckout();
    }
});

        function searchMenu() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const menuCards = document.querySelectorAll('.menu-card');
            
            menuCards.forEach(card => {
                const menuName = card.getAttribute('data-menu-name');
                if (menuName.includes(input)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

    //         window.onload = function() {
    //   checkGeofence(function(granted, distance) {
    //     console.log('Akses diberikan! Jarak: ' + distance + 'm');
    //   });
    // };
    </script>
</body>
</html>
