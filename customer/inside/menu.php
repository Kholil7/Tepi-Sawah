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
    <link rel="stylesheet" href="../../css/customer/menu.css">
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

        <!-- Cart Float Button (Mobile) -->
        <button class="cart-float-btn" id="cartFloatBtn" onclick="toggleCart()">
            🛒
            <span class="cart-float-badge" id="cartFloatBadge">0</span>
        </button>

        <!-- Cart Sidebar -->
        <div class="cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h2>Keranjang</h2>
                <span class="cart-count" id="cartCount">0</span>
            </div>

            <div class="cart-items" id="cartItems">
                <div class="empty-cart">Keranjang kosong</div>
            </div>

            <div class="cart-footer">
                <div class="cart-total">
                    <span>Total</span>
                    <span class="total-price" id="totalPrice">Rp 0</span>
                </div>
                <button class="checkout-btn" onclick="checkout()">Pesan Sekarang</button>
            </div>
        </div>
    </div>

    <script>
        let cart = [];

        function addToCart(menu) {
            const existingItem = cart.find(item => item.id === menu.id);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({...menu, quantity: 1});
            }
            updateCart();
        }

        function removeFromCart(menuId) {
            const itemIndex = cart.findIndex(item => item.id === menuId);
            if (itemIndex !== -1) {
                if (cart[itemIndex].quantity > 1) {
                    cart[itemIndex].quantity--;
                } else {
                    cart.splice(itemIndex, 1);
                }
            }
            updateCart();
        }

        function deleteFromCart(menuId) {
            cart = cart.filter(item => item.id !== menuId);
            updateCart();
        }

        function toggleCart() {
            const cartSidebar = document.getElementById('cartSidebar');
            cartSidebar.classList.toggle('active');
        }

        function updateCart() {
            const cartSidebar = document.getElementById('cartSidebar');
            const cartFloatBtn = document.getElementById('cartFloatBtn');
            const cartItems = document.getElementById('cartItems');
            const cartCount = document.getElementById('cartCount');
            const cartFloatBadge = document.getElementById('cartFloatBadge');
            const totalPrice = document.getElementById('totalPrice');

            if (cart.length === 0) {
                cartSidebar.classList.remove('active');
                cartFloatBtn.classList.remove('active');
                cartItems.innerHTML = '<div class="empty-cart">Keranjang kosong</div>';
                cartCount.textContent = '0';
                cartFloatBadge.textContent = '0';
                totalPrice.textContent = 'Rp 0';
                return;
            }

            cartFloatBtn.classList.add('active');
            let totalItems = 0, total = 0;

            cartItems.innerHTML = cart.map(item => {
                totalItems += item.quantity;
                total += item.harga * item.quantity;
                
                const imagePath = item.gambar ? `../../assets/uploads/${item.gambar}` : '';
                
                return `
                    <div class="cart-item">
                        <div class="cart-item-image">
                            ${item.gambar ? `<img src="${imagePath}" alt="${item.nama}">` : '<div class="no-image">No Image</div>'}
                        </div>
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.nama}</div>
                            <div class="cart-item-price">Rp ${item.harga.toLocaleString('id-ID')}</div>
                            <div class="cart-item-controls">
                                <button onclick="removeFromCart(${item.id})">−</button>
                                <span class="cart-item-quantity">${item.quantity}</span>
                                <button onclick='addToCart(${JSON.stringify(item)})'>+</button>
                            </div>
                        </div>
                    </div>`;
            }).join('');

            cartCount.textContent = totalItems;
            cartFloatBadge.textContent = totalItems;
            totalPrice.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        function checkout() {
            if (cart.length === 0) {
                alert('Keranjang kosong!');
                return;
            }
            const totalItems = cart.reduce((sum, i) => sum + i.quantity, 0);
            const totalPrice = cart.reduce((sum, i) => sum + (i.harga * i.quantity), 0);
            
            alert(`Pesanan berhasil!\nTotal item: ${totalItems}\nTotal harga: Rp ${totalPrice.toLocaleString('id-ID')}`);
            
            // Reset cart setelah checkout
            cart = [];
            updateCart();
        }

        // Close cart when clicking outside (desktop)
        document.addEventListener('click', function(event) {
            const cartSidebar = document.getElementById('cartSidebar');
            const cartFloatBtn = document.getElementById('cartFloatBtn');
            
            if (cartSidebar.classList.contains('active') && 
                !cartSidebar.contains(event.target) && 
                !cartFloatBtn.contains(event.target) &&
                !event.target.classList.contains('add-btn')) {
                cartSidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>