<?php
require_once '../include/check_auth.php'; 
require_once '../../database/connect.php'; 

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();

function generateRandomCode($length = 11) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

$query_meja = "SELECT id_meja, nomor_meja, kode_unik, status_meja 
                FROM meja 
                WHERE status_meja = 'kosong' AND kode_unik != 'TAKE_AWAY'
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

$query_menu = "SELECT id_menu, nama_menu, harga, kategori, gambar FROM menu ORDER BY kategori, nama_menu";
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
        $metode_bayar = strtolower(trim(mysqli_real_escape_string($conn, $_POST['metode_bayar'] ?? '')));
        $id_meja = $jenis_pesanan === 'dine_in' ? $_POST['id_meja'] : null;
        
        $nominal_bayar = $_POST['nominal_bayar_hidden'] ?? 0;
        $nominal_bayar = (float)$nominal_bayar; 
        $nomor_meja = $_POST['nomor_meja_hidden'] ?? 'TAKE AWAY';


        if ($jenis_pesanan === 'take_away') {
            $qTA = mysqli_query($conn, "SELECT id_meja FROM meja WHERE kode_unik='TAKE_AWAY' LIMIT 1");
            $TA = mysqli_fetch_assoc($qTA);
            $id_meja = $TA['id_meja'];
        }

        $cart_items = json_decode($_POST['cart_data'] ?? '[]', true);

        if (empty($cart_items)) throw new Exception("Keranjang masih kosong!");
        if ($jenis_pesanan === 'dine_in' && (!$id_meja || empty($_POST['id_meja']))) throw new Exception("Harap pilih meja!");

        $allowed_methods = ['qris', 'cash'];
        if (!in_array($metode_bayar, $allowed_methods)) throw new Exception("Metode bayar tidak valid!");

        $id_pesanan = generateRandomCode(11);

        $query_pesanan = "
            INSERT INTO pesanan (id_pesanan, id_meja, waktu_pesan, jenis_pesanan, total_harga, metode_bayar, aktif)
            VALUES ('$id_pesanan', '$id_meja', NOW(), '$jenis_pesanan', 0, '$metode_bayar', 1)
        ";

        if (!mysqli_query($conn, $query_pesanan)) {
            throw new Exception("Gagal menyimpan pesanan utama. MySQL Error: " . mysqli_error($conn));
        }

        $total_harga = 0;
        $struk_items = []; 

        foreach ($cart_items as $item) {
            $id_detail = generateRandomCode(11);
            $id_menu = mysqli_real_escape_string($conn, $item['id_menu']);
            $jumlah = (int)$item['jumlah'];
            $catatan = mysqli_real_escape_string($conn, $item['catatan']);

            $harga_res = mysqli_query($conn, "SELECT nama_menu, harga FROM menu WHERE id_menu='$id_menu'");
            $menu = mysqli_fetch_assoc($harga_res);
            if (!$menu) throw new Exception("Menu tidak ditemukan!");
            
            $nama_menu = $menu['nama_menu'];
            $harga = $menu['harga']; 

            $subtotal = $harga * $jumlah;
            $total_harga += $subtotal;

            $struk_items[] = [
                'id_menu' => $id_menu,
                'nama' => $nama_menu,
                'jumlah' => $jumlah,
                'harga_satuan' => $harga,
                'subtotal' => $subtotal,
                'catatan' => $catatan
            ];


            if (!mysqli_query($conn, "
                INSERT INTO detail_pesanan (id_detail, id_pesanan, id_menu, jumlah, harga_satuan, subtotal, catatan_item)
                VALUES ('$id_detail', '$id_pesanan', '$id_menu', '$jumlah', '$harga', '$subtotal', '$catatan')
            ")) {
                 throw new Exception("Gagal menyimpan detail pesanan. MySQL Error: " . mysqli_error($conn));
            }
        }
        
        if ($metode_bayar === 'cash' && $nominal_bayar < $total_harga) {
             throw new Exception("Nominal bayar kurang dari total harga! Nominal bayar: " . number_format($nominal_bayar) . ", Total: " . number_format($total_harga));
        }
        
        $kembalian = $metode_bayar === 'cash' ? ($nominal_bayar - $total_harga) : 0;
        $nominal_bayar_db = $metode_bayar === 'qris' ? $total_harga : $nominal_bayar;

        mysqli_query($conn, "UPDATE pesanan SET total_harga='$total_harga' WHERE id_pesanan='$id_pesanan'");

        if ($jenis_pesanan === 'dine_in' && $id_meja) {
            mysqli_query($conn, "UPDATE meja SET status_meja='terisi' WHERE id_meja='$id_meja'");
        }

        $id_pembayaran = generateRandomCode(11);

        $insert_pembayaran_query = "
            INSERT INTO pembayaran 
            (id_pembayaran, id_pesanan, metode, status, bukti_pembayaran, waktu_pembayaran, bayar, kembalian)
            VALUES 
            ('$id_pembayaran', '$id_pesanan', '$metode_bayar', 'sudah_bayar', NULL, NOW(), '$nominal_bayar_db', '$kembalian')
        ";

        if (!mysqli_query($conn, $insert_pembayaran_query)) {
             throw new Exception("Gagal menyimpan data pembayaran. Pastikan kolom 'bayar' dan 'kembalian' ada di tabel pembayaran. MySQL Error: " . mysqli_error($conn));
        }
        
        mysqli_commit($conn);
        
        $_SESSION['success_message'] = "Pesanan berhasil dibuat! ID: $id_pesanan";
        $_SESSION['order_data'] = [
            'id_pesanan' => $id_pesanan,
            'jenis_pesanan' => $jenis_pesanan === 'dine_in' ? 'Dine-In' : 'Take Away',
            'meja' => $nomor_meja,
            'tanggal' => date('Y-m-d H:i:s'),
            'items' => $struk_items,
            'total_harga' => $total_harga,
            'metode_bayar' => $metode_bayar,
            'nominal_bayar' => $nominal_bayar_db, 
            'kembalian' => $kembalian
        ];
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Gagal membuat pesanan: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
$order_data = isset($_SESSION['order_data']) ? $_SESSION['order_data'] : null;

unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
unset($_SESSION['order_data']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $version = filemtime('../../css/kasir/input-pesanan.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/kasir/input-pesanan.css?v=<?php echo $version; ?>">
    <title>Input Pesanan Baru</title>
</head>
<body>
    <?php include '../../sidebar/sidebar_kasir.php'; ?>
    
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-content">
            <div class="modal-icon" id="modalIcon">
                <span id="modalIconText"></span>
            </div>
            <div class="modal-title" id="modalTitle"></div>
            <div class="modal-message" id="modalMessage"></div>
            <div id="receiptContent" style="display: none;"></div> 
            
            <div class="modal-actions">
                <button class="modal-btn print-btn hidden" id="printBtn" onclick="printReceipt()">
                    🖨️ Cetak Struk
                </button>
                <button class="modal-btn" id="modalBtn" onclick="closeModal()"></button>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="left-section">
                <h2>Input Pesanan Baru</h2>
                <p class="subtitle">Kelola pesanan dari pelanggan</p>
                
                <form method="POST" id="formPesanan">
                    <input type="hidden" name="cart_data" id="cart_data">
                    <input type="hidden" name="jenis_pesanan" id="jenis_pesanan" value="">
                    <input type="hidden" name="metode_bayar" id="metode_bayar" value="">
                    <input type="hidden" name="nominal_bayar_hidden" id="nominal_bayar_hidden" value="">
                    <input type="hidden" name="nomor_meja_hidden" id="nomor_meja_hidden" value="">

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
                            <select name="id_meja" id="selectMeja" class="form-control" onchange="updateNomorMeja()">
                                <option value="">Pilih nomor meja...</option>
                                <?php if (!empty($meja_list)): ?>
                                    <?php foreach ($meja_list as $m): ?>
                                        <option value="<?= htmlspecialchars($m['id_meja']); ?>" data-nomor="<?= htmlspecialchars($m['nomor_meja']); ?>">
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
                    
                    <div class="section-box hidden" id="menuSelectionGroup">
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
                            <?php 
                                $all_menus_json = [];
                                foreach (['makanan', 'minuman', 'cemilan'] as $cat) {
                                    foreach ($menu_by_category[$cat] as $menu) {
                                        $display = $cat === 'makanan' ? 'block' : 'none';
                                        $image_path = !empty($menu['gambar']) && file_exists('../../uploads/' . $menu['gambar']) ? '../../uploads/' . htmlspecialchars($menu['gambar']) : '';
                                        $no_image_text = empty($image_path) ? '<span class="no-image-text">No Image</span>' : '';
                                        $nama_menu_html = htmlspecialchars($menu['nama_menu']);
                                        $menu_id_html = htmlspecialchars($menu['id_menu']);
                                        $menu_harga = $menu['harga'];
                                        $formatted_price = number_format($menu_harga, 0, ',', '.');
                                        
                                        $image_display_style = empty($no_image_text) ? 'block' : 'none'; 
                                        
                                        echo "<div class='menu-card' data-kategori='{$cat}' data-nama='{$nama_menu_html}' style='display: {$display};'>
                                            <div class='image'>
                                                {$no_image_text}
                                                <img src='{$image_path}' alt='{$nama_menu_html}' style='display: {$image_display_style};'>
                                            </div>
                                            <div class='name'>{$nama_menu_html}</div>
                                            <div class='price'>Rp {$formatted_price}</div>
                                            <button type='button' class='btn-tambah' onclick=\"addToCart('{$menu_id_html}', '{$nama_menu_html}', {$menu_harga})\">
                                                + Tambah
                                            </button>
                                        </div>";
                                        
                                        $all_menus_json[] = [
                                            'id_menu' => $menu['id_menu'],
                                            'nama_menu' => $menu['nama_menu'],
                                            'harga' => $menu['harga'],
                                            'kategori' => $cat
                                        ];
                                    }
                                }
                            ?>
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
                        
                        <div class="cash-calculator hidden" id="cashCalculator">
                            <div class="form-group">
                                <label for="nominalBayar">Nominal Bayar (Cash)</label>
                                <input type="text" id="nominalBayar" class="form-control" placeholder="Rp 0" oninput="calculateChange()">
                            </div>
                            <div class="summary-row change-row">
                                <span>Kembalian</span>
                                <span class="amount" id="kembalian">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" form="formPesanan" class="btn-submit" id="btnSubmit" disabled>
                            Buat Pesanan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const allMenus = <?= json_encode($all_menus_json, JSON_NUMERIC_CHECK); ?>;
        let cart = [];
        let totalPesanan = 0;

        function checkSidebarState() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                if (sidebar.classList.contains('collapsed')) {
                    document.body.classList.add('sidebar-collapsed');
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                }
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            checkSidebarState();
            
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        checkSidebarState();
                    }
                });
            });
            
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                observer.observe(sidebar, { attributes: true });
            }
            
            <?php if ($order_data): ?>
                showReceiptModal(<?= json_encode($order_data, JSON_NUMERIC_CHECK); ?>);
            <?php elseif ($error_message): ?>
                showModal('error', 'Pesanan Gagal!', '<?= addslashes($error_message); ?>');
            <?php endif; ?>
        });

        function formatRupiah(number) {
            if (number === undefined || number === null) return "Rp 0";
            return "Rp " + Math.abs(number).toLocaleString('id-ID');
        }
        
        function formatNumber(number) {
            if (number === undefined || number === null) return "0";
            return Math.abs(number).toLocaleString('id-ID');
        }

        function showModal(type, title, message) {
            const modal = document.getElementById('modalOverlay');
            const modalIcon = document.getElementById('modalIcon');
            const modalIconText = document.getElementById('modalIconText');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const modalBtn = document.getElementById('modalBtn');
            const receiptContent = document.getElementById('receiptContent');
            const printBtn = document.getElementById('printBtn'); 

            receiptContent.style.display = 'none';
            modalMessage.style.display = 'block';
            printBtn.classList.add('hidden'); 
            
            if (type === 'success') {
                modalIcon.className = 'modal-icon success';
                modalIconText.textContent = '✓';
                modalBtn.className = 'modal-btn success';
                modalBtn.textContent = 'OK';
            } else {
                modalIcon.className = 'modal-icon error';
                modalIconText.textContent = '✕';
                modalBtn.className = 'modal-btn error';
                modalBtn.textContent = 'Tutup';
            }
            
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modal.classList.add('active');
        }

        function showReceiptModal(data) {
            const modal = document.getElementById('modalOverlay');
            const modalIcon = document.getElementById('modalIcon');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const modalBtn = document.getElementById('modalBtn');
            const receiptContent = document.getElementById('receiptContent');
            const printBtn = document.getElementById('printBtn'); 

            modalIcon.className = 'modal-icon success';
            modalIcon.innerHTML = '<span>🧾</span>'; 
            modalTitle.textContent = 'Pesanan Berhasil!';
            modalMessage.style.display = 'none';
            modalBtn.className = 'modal-btn success';
            modalBtn.textContent = 'Selesai';
            receiptContent.style.display = 'block';
            printBtn.classList.remove('hidden'); 
            
            let itemsHtml = data.items.map(item => `
                <div class="receipt-item">
                    <span>${item.nama} x${item.jumlah}</span>
                    <span>${formatRupiah(item.subtotal)}</span>
                </div>
            `).join('');

            let changeRow = '';
            if (data.metode_bayar === 'cash') {
                changeRow = `
                    <div class="receipt-summary-row"><span>Bayar</span><span>${formatRupiah(data.nominal_bayar)}</span></div>
                    <div class="receipt-summary-row"><span>Kembalian</span><span>${formatRupiah(data.kembalian)}</span></div>
                `;
            } else {
                changeRow = `<div class="receipt-summary-row"><span>Metode Bayar</span><span>${data.metode_bayar.toUpperCase()}</span></div>`;
            }

            receiptContent.innerHTML = `
                <div class="receipt-box printable-area">
                    <div class="receipt-header">
                        <h4>Lesehan Tepi Sawah</h4>
                        <p>ID Pesanan: ${data.id_pesanan}</p>
                        <p>Tipe: ${data.jenis_pesanan}</p>
                        ${data.jenis_pesanan === 'Dine-In' ? `<p>Meja: ${data.meja}</p>` : ''}
                        <p>Tanggal: ${data.tanggal}</p>
                        <p>Kasir: <?php echo htmlspecialchars($username); ?></p>
                    </div>
                    <div class="receipt-items-list">${itemsHtml}</div>
                    <div class="receipt-summary">
                        <div class="receipt-summary-row total"><span>TOTAL</span><span>${formatRupiah(data.total_harga)}</span></div>
                        ${changeRow}
                    </div>
                    <div class="receipt-footer">
                        <p>Terima kasih atas kunjungan Anda!</p>
                    </div>
                </div>
            `;
            modal.classList.add('active');
        }
        
        function closeModal() {
            const modal = document.getElementById('modalOverlay');
            const printBtn = document.getElementById('printBtn');
            
            modal.classList.remove('active');
            printBtn.classList.add('hidden'); 
            document.getElementById('receiptContent').style.display = 'none'; 
            document.getElementById('modalIcon').innerHTML = '<span id="modalIconText"></span>';
        }

        function printReceipt() {
            const content = document.querySelector('.printable-area').innerHTML;
            
            const printWindow = window.open('', '', 'height=600,width=400');

            printWindow.document.write('<html><head><title>Struk Pesanan</title>');
            
            printWindow.document.write(`
                <style>
                    @media print {
                        body { font-family: 'Courier New', monospace; font-size: 10px; margin: 0; padding: 10px; }
                        .receipt-box { width: 100%; }
                        .receipt-header, .receipt-footer { text-align: center; margin-bottom: 5px; }
                        .receipt-header h4 { margin: 0 0 5px 0; font-size: 12px; }
                        .receipt-items-list { border-top: 1px dashed #000; padding-top: 5px; }
                        .receipt-summary { border-top: 1px dashed #000; border-bottom: 1px dashed #000; margin: 5px 0; padding: 5px 0; }
                        .receipt-item, .receipt-summary-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
                        .receipt-summary-row.total span { font-weight: bold; font-size: 11px; }
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
        }

        
        function selectOrderType(jenis, element) {
            document.querySelectorAll('.type-card').forEach(card => card.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('jenis_pesanan').value = jenis;
            
            const mejaGroup = document.getElementById('mejaGroup');
            const menuSelectionGroup = document.getElementById('menuSelectionGroup');

            if (jenis === 'dine_in') {
                mejaGroup.classList.remove('hidden');
                document.getElementById('nomor_meja_hidden').value = '';
            } else {
                mejaGroup.classList.add('hidden');
                document.getElementById('selectMeja').value = ''; 
                document.getElementById('nomor_meja_hidden').value = 'TAKE AWAY';
            }
            
            menuSelectionGroup.classList.remove('hidden');
            checkFormValidity();
        }

        function updateNomorMeja() {
            const select = document.getElementById('selectMeja');
            const selectedOption = select.options[select.selectedIndex];
            document.getElementById('nomor_meja_hidden').value = selectedOption.getAttribute('data-nomor') || '';
            checkFormValidity();
        }
        
        function switchCategory(button, kategori) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            document.querySelectorAll('.menu-card').forEach(card => {
                const isMatch = card.getAttribute('data-kategori') === kategori;
                const isSearchActive = document.getElementById('searchMenu').value.trim() !== '';

                if (isMatch && !isSearchActive) {
                    card.style.display = 'block';
                } else if (!isSearchActive) {
                    card.style.display = 'none';
                }
            });
            document.getElementById('searchMenu').value = ''; 
        }
        
        function addToCart(id, nama, harga) {
            let item = cart.find(x => x.id_menu === id);
            if (item) {
                item.jumlah += 1;
            } else {
                cart.push({ id_menu: id, nama, harga: harga, jumlah: 1, catatan: "" });
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
        
        function updateCatatan(id, inputElement) {
            let item = cart.find(x => x.id_menu === id);
            if (item) {
                item.catatan = inputElement.value;
            }
            renderCart(); 
        }
        
        function removeItem(id) {
            cart = cart.filter(x => x.id_menu !== id);
            renderCart();
        }
        
        function clearCart() {
            if (confirm('Kosongkan keranjang?')) {
                cart = [];
                document.getElementById('nominalBayar').value = '';
                calculateChange(); 
                renderCart();
            }
        }
        
        function renderCart() {
            const cartList = document.getElementById("cartList");
            const totalHargaEl = document.getElementById("totalHarga");
            const subtotalEl = document.getElementById("subtotal");
            const cartBadge = document.getElementById("cartBadge");
            const clearBtn = document.getElementById("clearBtn");
            const cartSection = document.getElementById("cartSection");
            
            totalPesanan = cart.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);

            if (cart.length === 0) {
                cartList.innerHTML = '<div class="cart-empty">Belum ada item.</div>';
                totalHargaEl.textContent = "Rp 0";
                subtotalEl.textContent = "Rp 0";
                cartBadge.textContent = "0";
                clearBtn.style.display = "none";
                cartSection.classList.add("hidden");
                totalPesanan = 0;
                calculateChange();
                checkFormValidity();
                return;
            }

            cartSection.classList.remove("hidden");

            cartList.innerHTML = "";
            
            cart.forEach(item => {
                const subtotal = item.harga * item.jumlah;
                cartList.innerHTML += `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <div>
                                <div class="cart-item-name">${item.nama}</div>
                                ${item.catatan ? `<div class="cart-item-note">Catatan: ${item.catatan}</div>` : ''}
                            </div>
                            <button type="button" class="cart-item-remove" onclick="removeItem('${item.id_menu}')">×</button>
                        </div>
                        <div class="cart-item-controls">
                            <div class="qty-controls">
                                <button type="button" class="qty-btn" onclick="updateJumlah('${item.id_menu}', -1)" ${item.jumlah <= 1 ? 'disabled' : ''}>−</button>
                                <span class="qty-value">${item.jumlah}</span>
                                <button type="button" class="qty-btn" onclick="updateJumlah('${item.id_menu}', 1)">+</button>
                            </div>
                            <div class="item-price">${formatRupiah(subtotal)}</div>
                        </div>
                        <input type="text" 
                               class="cart-item-note-input" 
                               placeholder="Tambah catatan (opsional)..." 
                               value="${item.catatan || ''}"
                               onchange="updateCatatan('${item.id_menu}', this)">
                    </div>
                `;
            });
            
            totalHargaEl.textContent = formatRupiah(totalPesanan);
            subtotalEl.textContent = formatRupiah(totalPesanan);
            cartBadge.textContent = cart.reduce((sum, item) => sum + item.jumlah, 0);
            clearBtn.style.display = "block";
            document.getElementById("cart_data").value = JSON.stringify(cart);

            calculateChange();
            checkFormValidity();
        }

        function calculateChange() {
            const nominalBayarInput = document.getElementById('nominalBayar');
            const kembalianEl = document.getElementById('kembalian');
            
            let value = nominalBayarInput.value.replace(/[^0-9]/g, '');
            let nominalBayar = parseFloat(value) || 0;

            if (value) {
                nominalBayarInput.value = formatRupiah(nominalBayar);
            } else {
                nominalBayarInput.value = '';
            }

            const kembalian = nominalBayar - totalPesanan;

            if (kembalian >= 0) {
                kembalianEl.textContent = formatRupiah(kembalian);
                kembalianEl.style.color = '#4CAF50'; 
            } else {
                kembalianEl.textContent = "-" + formatRupiah(kembalian);
                kembalianEl.style.color = '#f44336'; 
            }

            document.getElementById('nominal_bayar_hidden').value = nominalBayar;
            checkFormValidity();
        }
        
        function selectPayment(metode, element) {
            document.querySelectorAll('.payment-card').forEach(card => card.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('metode_bayar').value = metode;
            
            const cashCalculator = document.getElementById('cashCalculator');
            if (metode === 'cash') {
                cashCalculator.classList.remove('hidden');
                calculateChange(); 
            } else {
                cashCalculator.classList.add('hidden');
                document.getElementById('nominalBayar').value = '';
                document.getElementById('kembalian').textContent = 'Rp 0';
                document.getElementById('nominal_bayar_hidden').value = totalPesanan; 
            }

            checkFormValidity();
        }
        
        function checkFormValidity() {
            const jenisPesanan = document.getElementById('jenis_pesanan').value;
            const metodeBayar = document.getElementById('metode_bayar').value;
            const idMeja = document.getElementById('selectMeja').value;
            const btnSubmit = document.getElementById('btnSubmit');
            let isCashValid = true;
            
            if (metodeBayar === 'cash') {
                const nominalBayar = parseFloat(document.getElementById('nominal_bayar_hidden').value) || 0;
                isCashValid = nominalBayar >= totalPesanan;
            }

            let isMejaValid = true;
            if (jenisPesanan === 'dine_in' && idMeja === '') {
                isMejaValid = false;
            }

            if (cart.length > 0 && jenisPesanan && metodeBayar && isMejaValid && isCashValid) {
                btnSubmit.disabled = false;
            } else {
                btnSubmit.disabled = true;
            }
        }
        
        document.getElementById('searchMenu').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const activeCategoryElement = document.querySelector('.tab-btn.active');
            
            let activeCategory = 'makanan';
            if (activeCategoryElement) {
                const buttonText = activeCategoryElement.textContent.trim().toLowerCase();
                if (buttonText.includes('minuman')) {
                    activeCategory = 'minuman';
                } else if (buttonText.includes('cemilan')) {
                    activeCategory = 'cemilan';
                }
            }
            
            document.querySelectorAll('.menu-card').forEach(card => {
                const nama = card.getAttribute('data-nama').toLowerCase();
                const currentCategory = card.getAttribute('data-kategori');
                
                if (currentCategory === activeCategory && nama.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

    </script>
</body>
</html>