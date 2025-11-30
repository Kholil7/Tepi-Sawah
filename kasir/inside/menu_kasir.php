<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require '../../database/connect.php';

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

function clean($v) {
    return trim(htmlspecialchars($v ?? ''));
}

if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $id_menu = clean($_POST['id_menu']);
    $current_status = clean($_POST['current_status']);
    
    $new_status = ($current_status === 'aktif') ? 'nonaktif' : 'aktif';
    
    $stmt = $conn->prepare("UPDATE menu SET status_menu = ? WHERE id_menu = ?");
    $stmt->bind_param("ss", $new_status, $id_menu);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal mengubah status']);
    }
    $stmt->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar Menu | Resto Owner</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* {margin:0;padding:0;box-sizing:border-box;}
body {
    font-family:'Inter','Segoe UI',sans-serif;
    min-height:100vh;
    padding:0;
    margin:0;
    display:flex;
    background: #f5f5f5;
}

aside {
    width:250px;
    flex-shrink:0;
}

main {
    flex:1;
    padding:20px;
    margin-left:250px;
    min-height:100vh;
    transition:margin-left 0.3s ease;
}

aside.collapsed ~ main {
    margin-left:70px;
}

.main-container {
    max-width:100%;
    background:#ffffff;
    border-radius:30px;
    overflow:hidden;
    border:2px solid #fed7aa;
}

.header-section {
    background:linear-gradient(135deg,#f97316 0%,#ea580c 100%);
    padding:40px 50px;
    position:relative;
    overflow:hidden;
}

.header-section::before {
    content:'';
    position:absolute;
    top:-50%;
    right:-10%;
    width:500px;
    height:500px;
    background:rgba(255,255,255,0.1);
    border-radius:50%;
}

.header-content {
    position:relative;
    z-index:2;
}

.header-title {
    font-size:42px;
    font-weight:800;
    color:white;
    margin-bottom:10px;
    display:flex;
    align-items:center;
    gap:15px;
}

.header-subtitle {
    font-size:16px;
    color:rgba(255,255,255,0.9);
    font-weight:400;
}

.stats-bar {
    display:flex;
    gap:30px;
    margin-top:30px;
    padding-top:30px;
    border-top:1px solid rgba(255,255,255,0.2);
}

.stat-item {
    display:flex;
    align-items:center;
    gap:12px;
}

.stat-icon {
    width:50px;
    height:50px;
    background:rgba(255,255,255,0.2);
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    color:white;
}

.stat-info h4 {
    font-size:28px;
    font-weight:700;
    color:white;
}

.stat-info p {
    font-size:13px;
    color:rgba(255,255,255,0.8);
    text-transform:uppercase;
    letter-spacing:1px;
}

.control-panel {
    padding:30px 50px;
    background:#fff9f5;
    border-bottom:3px solid #fed7aa;
}

.control-row {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
}

.search-container {
    flex:1;
    max-width:500px;
    position:relative;
}

.search-icon {
    position:absolute;
    left:20px;
    top:50%;
    transform:translateY(-50%);
    color:#f97316;
    font-size:20px;
}

.search-input {
    width:100%;
    padding:16px 20px 16px 55px;
    border:3px solid #fed7aa;
    border-radius:20px;
    font-size:16px;
    transition:all 0.3s;
    background:white;
}

.search-input:focus {
    outline:none;
    border-color:#f97316;
}

.filter-tabs {
    display:flex;
    gap:15px;
    background:white;
    padding:8px;
    border-radius:20px;
    border:2px solid #fed7aa;
}

.filter-btn {
    padding:12px 28px;
    border:none;
    background:transparent;
    color:#ea580c;
    font-weight:600;
    font-size:15px;
    border-radius:14px;
    cursor:pointer;
    transition:all 0.3s;
    display:flex;
    align-items:center;
    gap:8px;
}

.filter-btn:hover {
    background:#fff7ed;
}

.filter-btn.active {
    background:linear-gradient(135deg,#f97316,#ea580c);
    color:white;
}

.menu-section {
    padding:40px 30px;
}

.menu-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
    gap:25px;
}

.menu-card {
    background:white;
    border-radius:20px;
    overflow:hidden;
    transition:all 0.3s;
    border:2px solid #ffedd5;
    display:flex;
    position:relative;
    width: 350px;
}

.menu-card:hover {
    transform:translateY(-5px);
    border-color:#f97316;
}

.card-image {
    width:120px;
    height:100%;
    flex-shrink:0;
    position:relative;
}

.card-image img {
    width:100%;
    height:100%;
    object-fit:cover;
}

.status-badge {
    position:absolute;
    top:10px;
    left:10px;
    width:12px;
    height:12px;
    border-radius:50%;
    border:2px solid white;
}

.status-badge.aktif {
    background:#10b981;
    animation:pulse 2s infinite;
}

.status-badge.nonaktif {
    background:#ef4444;
}

@keyframes pulse {
    0%, 100% { opacity:1; }
    50% { opacity:0.5; }
}

.card-content {
    padding:16px;
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
}

.card-header {
    margin-bottom:10px;
}

.card-title {
    font-size:17px;
    font-weight:700;
    color:#7c2d12;
    margin-bottom:6px;
    line-height:1.3;
}

.card-category {
    display:inline-flex;
    align-items:center;
    gap:5px;
    background:linear-gradient(135deg,#fff7ed,#ffedd5);
    color:#c2410c;
    padding:4px 10px;
    border-radius:20px;
    font-size:10px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.5px;
    border:1px solid #fed7aa;
}

.card-footer {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:auto;
    padding-top:12px;
    border-top:1px solid #fed7aa;
}

.card-price {
    font-size:20px;
    font-weight:800;
    color:#f97316;
    display:flex;
    align-items:center;
    gap:5px;
}

.toggle-wrapper {
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:6px;
}

.toggle-label {
    font-size:10px;
    font-weight:700;
    color:#c2410c;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

.switch {
    position:relative;
    width:50px;
    height:26px;
}

.switch input {
    opacity:0;
    width:0;
    height:0;
}

.slider {
    position:absolute;
    cursor:pointer;
    inset:0;
    background:#ef4444;
    transition:0.4s;
    border-radius:26px;
}

.slider:before {
    position:absolute;
    content:"";
    height:18px;
    width:18px;
    left:4px;
    bottom:4px;
    background:white;
    transition:0.4s;
    border-radius:50%;
}

.switch input:checked + .slider {
    background:linear-gradient(135deg,#10b981,#059669);
}

.switch input:checked + .slider:before {
    transform:translateX(24px);
}

.empty-state {
    text-align:center;
    padding:80px 20px;
    color:#c2410c;
}

.empty-state i {
    font-size:80px;
    color:#fdba74;
    margin-bottom:20px;
    opacity:0.6;
}

.empty-state h3 {
    font-size:24px;
    margin-bottom:10px;
}

.empty-state p {
    font-size:16px;
    color:#9a3412;
}

@media (max-width:768px) {
    .header-section {
        padding:30px 25px;
    }
    
    .header-title {
        font-size:32px;
    }
    
    .stats-bar {
        flex-direction:column;
        gap:15px;
    }
    
    .control-panel {
        padding:20px 25px;
    }
    
    .control-row {
        flex-direction:column;
    }
    
    .filter-tabs {
        width:100%;
        overflow-x:auto;
    }
    
    .menu-section {
        padding:30px 25px;
    }
    
    .menu-grid {
        grid-template-columns:1fr;
    }
    
    .menu-card {
        flex-direction:column;
    }
    
    .card-image {
        width:100%;
        height:180px;
    }
}

.notification {
    position:fixed;
    top:30px;
    right:30px;
    padding:18px 28px;
    border-radius:15px;
    color:white;
    font-weight:600;
    font-size:15px;
    z-index:10000;
    animation:slideIn 0.4s ease;
    display:flex;
    align-items:center;
    gap:12px;
}

.notification.success {
    background:linear-gradient(135deg,#10b981,#059669);
}

.notification.error {
    background:linear-gradient(135deg,#ef4444,#dc2626);
}

@keyframes slideIn {
    from {
        transform:translateX(400px);
        opacity:0;
    }
    to {
        transform:translateX(0);
        opacity:1;
    }
}

@keyframes slideOut {
    from {
        transform:translateX(0);
        opacity:1;
    }
    to {
        transform:translateX(400px);
        opacity:0;
    }
}
</style>
</head>
<body>
<?php include '../../sidebar/sidebar_kasir.php'; ?>

<main>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">
                Menu Tepi Sawah
            </h1>
            <p class="header-subtitle">Kelola menu saat ini.</p>
            
            <div class="stats-bar">
                <?php
                $total = $conn->query("SELECT COUNT(*) as total FROM menu")->fetch_assoc()['total'];
                $aktif = $conn->query("SELECT COUNT(*) as total FROM menu WHERE status_menu='aktif'")->fetch_assoc()['total'];
                $nonaktif = $total - $aktif;
                ?>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <h4><?= $total ?></h4>
                        <p>Total Menu</p>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4><?= $aktif ?></h4>
                        <p>Menu Aktif</p>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4><?= $nonaktif ?></h4>
                        <p>Nonaktif</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="control-panel">
        <div class="control-row">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchMenu" class="search-input" placeholder="Cari nama menu...">
            </div>
            
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="semua">
                    <i class="fas fa-th"></i> Semua
                </button>
                <button class="filter-btn" data-filter="makanan">
                    <i class="fas fa-pizza-slice"></i> Makanan
                </button>
                <button class="filter-btn" data-filter="minuman">
                    <i class="fas fa-coffee"></i> Minuman
                </button>
                <button class="filter-btn" data-filter="cemilan">
                    <i class="fas fa-cookie-bite"></i> Cemilan
                </button>
            </div>
        </div>
    </div>

    <div class="menu-section">
        <div class="menu-grid" id="menuGrid">
            <?php
            $menus = $conn->query("SELECT * FROM menu ORDER BY status_menu DESC, id_menu DESC");
            if ($menus->num_rows > 0):
                while($row = $menus->fetch_assoc()):
                    $is_checked = $row['status_menu'] === 'aktif' ? 'checked' : '';
                    $status_class = $row['status_menu'];
            ?>
                <div class="menu-card" 
                    data-id="<?= $row['id_menu'] ?>" 
                    data-nama="<?= htmlspecialchars($row['nama_menu']) ?>" 
                    data-kategori="<?= htmlspecialchars($row['kategori']) ?>" 
                    data-status="<?= htmlspecialchars($row['status_menu']) ?>">
                    
                    <div class="card-image">
                        <div class="status-badge <?= $status_class ?>"></div>
                        <img src="../../assets/uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_menu']) ?>">
                    </div>
                    
                    <div class="card-content">
                        <div class="card-header">
                            <h3 class="card-title"><?= htmlspecialchars($row['nama_menu']) ?></h3>
                            <span class="card-category">
                                <i class="fas fa-tag"></i>
                                <?= ucfirst($row['kategori']) ?>
                            </span>
                        </div>
                        
                        <div class="card-footer">
                            <div class="card-price">
                                <i class="fas fa-coins"></i>
                                Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                            </div>
                            
                            <div class="toggle-wrapper">
                                <span class="toggle-label">Status</span>
                                <label class="switch" data-id="<?= $row['id_menu'] ?>" data-current-status="<?= $row['status_menu'] ?>">
                                    <input type="checkbox" <?= $is_checked ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile; 
            else: 
            ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <h3>Belum Ada Menu</h3>
                    <p>Daftar menu masih kosong</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.switch').forEach(toggle => {
        const checkbox = toggle.querySelector('input[type="checkbox"]');
        
        checkbox.addEventListener('change', function() {
            const id = toggle.dataset.id;
            const currentStatus = toggle.dataset.currentStatus;
            
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id_menu', id);
            formData.append('current_status', currentStatus);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toggle.dataset.currentStatus = data.new_status;
                    
                    const card = toggle.closest('.menu-card');
                    card.dataset.status = data.new_status;
                    
                    const statusBadge = card.querySelector('.status-badge');
                    statusBadge.className = `status-badge ${data.new_status}`;
                    
                    showNotification(
                        `<i class="fas fa-check-circle"></i> Status berhasil diubah menjadi ${data.new_status.toUpperCase()}`,
                        'success'
                    );
                } else {
                    this.checked = !this.checked;
                    showNotification('<i class="fas fa-exclamation-circle"></i> Gagal mengubah status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !this.checked;
                showNotification('<i class="fas fa-times-circle"></i> Terjadi kesalahan', 'error');
            });
        });
    });

    function showNotification(message, type) {
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) existingNotification.remove();
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.4s ease';
            setTimeout(() => notification.remove(), 400);
        }, 3000);
    }

    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.menu-card');
    const searchInput = document.getElementById('searchMenu');

    filterBtns.forEach(btn => {
        btn.onclick = () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.dataset.filter;
            filterCards(filter, searchInput.value);
        };
    });

    searchInput.addEventListener('input', function() {
        const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
        filterCards(activeFilter, this.value);
    });

    function filterCards(category, search) {
        const lowerSearch = search.toLowerCase();
        cards.forEach(card => {
            const matchesCategory = (category === 'semua' || card.dataset.kategori === category);
            const matchesSearch = card.dataset.nama.toLowerCase().includes(lowerSearch);
            card.style.display = (matchesCategory && matchesSearch) ? 'flex' : 'none';
        });
    }
});
</script>
</body>
</html>

<?php ob_end_flush(); ?>