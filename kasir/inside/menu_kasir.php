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
<?php $version = filemtime('../../css/kasir/menu.css'); ?>
<link rel="stylesheet" type="text/css" href="../../css/kasir/menu.css?v=<?php echo $version; ?>">
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