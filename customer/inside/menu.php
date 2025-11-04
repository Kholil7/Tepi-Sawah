<?php
require '../../database/connect.php';

$id_meja = isset($_GET['meja']) ? $_GET['meja'] : 5;
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';

try {
    if ($kategori == 'semua') {
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
} catch(Exception $e) {
    $menus = [];
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Meja <?php echo $id_meja; ?></title>
    <link rel="stylesheet" href="../../css/customer/menu.css">
</head>
<body>
    <div class="main-wrapper">
        <div class="container">
            <header class="header">
                <button class="back-btn" onclick="window.history.back()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="header-title">
                    <h1>Menu</h1>
                    <p>Meja <?php echo $id_meja; ?></p>
                </div>
            </header>

            <nav class="tab-navigation">
                <a href="?meja=<?php echo $id_meja; ?>&kategori=semua" 
                   class="tab-item <?php echo $kategori == 'semua' ? 'active' : ''; ?>">
                    Semua
                </a>
                <a href="?meja=<?php echo $id_meja; ?>&kategori=makanan" 
                   class="tab-item <?php echo $kategori == 'makanan' ? 'active' : ''; ?>">
                    Makanan
                </a>
                <a href="?meja=<?php echo $id_meja; ?>&kategori=minuman" 
                   class="tab-item <?php echo $kategori == 'minuman' ? 'active' : ''; ?>">
                    Minuman
                </a>
                <a href="?meja=<?php echo $id_meja; ?>&kategori=lainnya" 
                   class="tab-item <?php echo $kategori == 'lainnya' ? 'active' : ''; ?>">
                    Camilan
                </a>
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
                                    <div class="no-image">No Image</div>
                                <?php endif; ?>
                                
                                <?php if ($menu['status_menu'] == 'nonaktif'): ?>
                                    <div class="status-badge">Tidak Tersedia</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="menu-info">
                                <h3 class="menu-name"><?php echo htmlspecialchars($menu['nama_menu']); ?></h3>
                                <div class="menu-footer">
                                    <span class="menu-price">Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></span>
                                    
                                    <?php if ($menu['status_menu'] == 'aktif'): ?>
                                        <button class="add-btn" onclick='addToCart(<?php echo json_encode([
                                            "id" => $menu["id_menu"],
                                            "nama" => $menu["nama_menu"],
                                            "harga" => $menu["harga"],
                                            "gambar" => $menu["gambar"]
                                        ]); ?>)'>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M10 5V15M5 10H15" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <button class="add-btn disabled" disabled>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M10 5V15M5 10H15" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Tidak ada menu tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h2>Keranjang</h2>
                <span class="cart-count" id="cartCount">0</span>
            </div>
            
            <div class="cart-items" id="cartItems">
            </div>
            
            <div class="cart-footer">
                <div class="cart-total">
                    <span>Total</span>
                    <span class="total-price" id="totalPrice">Rp 0</span>
                </div>
                <button class="checkout-btn" onclick="checkout()">
                    Pesan Sekarang
                </button>
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
                cart.push({
                    id: menu.id,
                    nama: menu.nama,
                    harga: menu.harga,
                    gambar: menu.gambar,
                    quantity: 1
                });
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

        function updateCart() {
            const cartSidebar = document.getElementById('cartSidebar');
            const cartItems = document.getElementById('cartItems');
            const cartCount = document.getElementById('cartCount');
            const totalPrice = document.getElementById('totalPrice');
            
            if (cart.length === 0) {
                cartSidebar.classList.remove('active');
                cartItems.innerHTML = '<div class="empty-cart">Keranjang kosong</div>';
                cartCount.textContent = '0';
                totalPrice.textContent = 'Rp 0';
                return;
            }
            
            cartSidebar.classList.add('active');
            
            let totalItems = 0;
            let total = 0;
            
            cartItems.innerHTML = cart.map(item => {
                totalItems += item.quantity;
                total += item.harga * item.quantity;
                
                return `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4>${item.nama}</h4>
                            <p class="cart-item-price">Rp ${item.harga.toLocaleString('id-ID')}</p>
                        </div>
                        <div class="cart-item-controls">
                            <button class="cart-btn-minus" onclick="removeFromCart(${item.id})">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M4 8H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <span class="cart-item-quantity">${item.quantity}</span>
                            <button class="cart-btn-plus" onclick="addToCart(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M8 4V12M4 8H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
            
            cartCount.textContent = totalItems;
            totalPrice.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        function checkout() {
            if (cart.length === 0) {
                alert('Keranjang kosong!');
                return;
            }
            
            console.log('Checkout:', cart);
            alert('Pesanan berhasil! Total item: ' + cart.reduce((sum, item) => sum + item.quantity, 0));
        }
    </script>
</body>
</html>