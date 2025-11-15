<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../database/connect.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$nama = $_SESSION['nama'] ?? $_SESSION['nama_user'] ?? 'Pengguna';

// Ambil data meja
$query = $conn->query("SELECT * FROM meja ORDER BY nomor_meja ASC");
$meja = $query->fetch_all(MYSQLI_ASSOC);

$total_meja = count($meja);
$kosong = count(array_filter($meja, fn($m) => $m['status_meja'] === 'kosong'));
$terisi = count(array_filter($meja, fn($m) => $m['status_meja'] === 'terisi'));
$menunggu = count(array_filter($meja, fn($m) => $m['status_meja'] === 'menunggu_pembayaran'));

if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Kasir - Tepi Sawah</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== GLOBAL ===== */
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
    overflow-x: hidden;
}

/* ===== MOBILE MENU BUTTON ===== */
.mobile-menu-btn {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1001;
    background: #ff9f00;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 15px;
    cursor: pointer;
    font-size: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.mobile-menu-btn:hover {
    background: #e68a00;
    transform: scale(1.1);
}

/* ===== MAIN CONTENT ===== */
.main-content {
    margin-left: 260px;
    padding: 30px;
    transition: all 0.4s ease;
    min-height: 100vh;
    background: #f8fafc;
}

/* SESUAIKAN DENGAN SIDEBAR COLLAPSED */
body.sidebar-collapsed .main-content {
    margin-left: 85px;
}

/* ===== STATS ===== */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #3b82f6;
}

.stat-card:nth-child(2)::before { background: #10b981; }
.stat-card:nth-child(3)::before { background: #f59e0b; }
.stat-card:nth-child(4)::before { background: #ef4444; }

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.stat-card h3 {
    font-size: 15px;
    color: #64748b;
    margin-bottom: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card p {
    font-size: 36px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
    margin: 0;
}

.green { color: #059669; }
.blue { color: #2563eb; }
.orange { color: #d97706; }

/* ===== LEGEND ===== */
.legend {
    display: flex;
    gap: 25px;
    margin-bottom: 25px;
    align-items: center;
    justify-content: center;
    padding: 15px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #f1f5f9;
}

.legend-item {
    display: flex;
    align-items: center;
    font-size: 14px;
    color: #475569;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.legend-item:hover {
    background: #f1f5f9;
    transform: scale(1.05);
}

.color-box {
    width: 18px;
    height: 18px;
    border-radius: 6px;
    margin-right: 10px;
    border: 2px solid;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.bg-green { 
    background: #dcfce7; 
    border-color: #16a34a; 
}

.bg-blue { 
    background: #dbeafe; 
    border-color: #2563eb; 
}

.bg-orange { 
    background: #fef3c7; 
    border-color: #d97706; 
}

/* ===== MEJA GRID ===== */
.meja-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 10px;
}

.meja-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 30px 20px;
    text-align: center;
    border: 2px solid;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    opacity: 0;
    animation: fadeInUp 0.6s ease forwards;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
}

.meja-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: inherit;
    opacity: 0.3;
}

@keyframes fadeInUp {
    from { 
        transform: translateY(15px); 
        opacity: 0; 
    }
    to { 
        transform: translateY(0); 
        opacity: 1; 
    }
}

.meja-card.kosong { 
    background: linear-gradient(135deg, #f0fdf4, #ffffff);
    border-color: #22c55e;
    color: #166534;
}

.meja-card.terisi { 
    background: linear-gradient(135deg, #fffbeb, #ffffff);
    border-color: #f59e0b;
    color: #92400e;
}

.meja-card.menunggu_pembayaran { 
    background: linear-gradient(135deg, #fef2f2, #ffffff);
    border-color: #ef4444;
    color: #991b1b;
}

.meja-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.meja-card div:first-child {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.meja-card div:last-child {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 1024px) {
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .meja-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }
}

@media (max-width: 768px) {
    .mobile-menu-btn {
        display: block;
    }
    
    .main-content {
        margin-left: 0 !important;
        padding: 80px 20px 20px 20px;
        transition: none !important;
    }
    
    body.sidebar-collapsed .main-content {
        margin-left: 0 !important;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .legend {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .meja-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .meja-card {
        padding: 25px 15px;
    }
    
    .meja-card div:first-child {
        font-size: 24px;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 70px 15px 15px 15px;
    }
    
    .meja-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .stat-card {
        padding: 20px 15px;
    }
    
    .stat-card p {
        font-size: 32px;
    }
    
    .legend {
        padding: 12px;
    }
    
    .legend-item {
        padding: 6px 12px;
        font-size: 13px;
    }
    
    .mobile-menu-btn {
        top: 10px;
        left: 10px;
        padding: 10px 12px;
        font-size: 16px;
    }
}
</style>
</head>
<body>

<?php include '../../sidebar/sidebar_kasir.php'; ?>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<div class="main-content" id="mainContent">
    <!-- STATS -->
    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Meja</h3>
            <p><?= $total_meja ?></p>
        </div>
        <div class="stat-card">
            <h3>Kosong</h3>
            <p class="green"><?= $kosong ?></p>
        </div>
        <div class="stat-card">
            <h3>Terisi</h3>
            <p class="blue"><?= $terisi ?></p>
        </div>
        <div class="stat-card">
            <h3>Menunggu Bayar</h3>
            <p class="orange"><?= $menunggu ?></p>
        </div>
    </div>

    <!-- LEGEND -->
    <div class="legend">
        <div class="legend-item">
            <div class="color-box bg-green"></div> 
            <span>Kosong</span>
        </div>
        <div class="legend-item">
            <div class="color-box bg-blue"></div> 
            <span>Terisi</span>
        </div>
        <div class="legend-item">
            <div class="color-box bg-orange"></div> 
            <span>Menunggu Bayar</span>
        </div>
    </div>

    <!-- MEJA GRID -->
    <div class="meja-grid">
        <?php foreach ($meja as $m): ?>
            <div class="meja-card <?= htmlspecialchars($m['status_meja']) ?>">
                <div>#<?= htmlspecialchars($m['nomor_meja']) ?></div>
                <div><?= ucwords(str_replace('_', ' ', $m['status_meja'])) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainContent = document.getElementById('mainContent');
    const sidebar = document.querySelector('.sidebar');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const toggleBtn = document.querySelector('#toggle-btn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Cek apakah mobile
    function isMobile() {
        return window.innerWidth <= 768;
    }

    // Fungsi untuk mobile menu
    function toggleMobileMenu() {
        if (!isMobile()) return;
        
        sidebar.classList.toggle('mobile-open');
        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active');
        }
        console.log('📱 Mobile menu toggled:', sidebar.classList.contains('mobile-open'));
    }

    // Sync dengan sidebar collapsed state untuk desktop
    function updateMainContentState() {
        if (!isMobile() && sidebar.classList.contains('collapsed')) {
            document.body.classList.add('sidebar-collapsed');
            console.log('✅ Sidebar collapsed - Main content melebar');
        } else {
            document.body.classList.remove('sidebar-collapsed');
            console.log('📱 Sidebar expanded - Main content menyempit');
        }
        
        console.log('Main content margin-left:', getComputedStyle(mainContent).marginLeft);
    }

    // Event listeners
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }

    // Observasi perubahan class sidebar untuk desktop
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class' && !isMobile()) {
                console.log('🎬 Sidebar class changed:', sidebar.className);
                updateMainContentState();
            }
        });
    });
    
    if (sidebar) {
        observer.observe(sidebar, { 
            attributes: true, 
            attributeFilter: ['class'] 
        });
    }

    // Handle resize
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            // Reset mobile state di desktop
            sidebar.classList.remove('mobile-open');
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('active');
            }
        }
        updateMainContentState();
    });

    // Inisialisasi state
    updateMainContentState();

    // Efek animasi masuk dashboard
    if (mainContent) {
        mainContent.style.opacity = '0';
        mainContent.style.transform = 'translateY(10px)';
        setTimeout(() => {
            mainContent.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            mainContent.style.opacity = '1';
            mainContent.style.transform = 'translateY(0)';
        }, 100);
    }

    // Animasi untuk kartu meja
    const mejaCards = document.querySelectorAll('.meja-card');
    mejaCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    // Debug function
    window.debugSidebar = function() {
        console.log('=== DEBUG SIDEBAR STATE ===');
        console.log('Window width:', window.innerWidth);
        console.log('Is mobile:', isMobile());
        console.log('Sidebar class:', sidebar.className);
        console.log('Sidebar collapsed:', sidebar.classList.contains('collapsed'));
        console.log('Sidebar mobile-open:', sidebar.classList.contains('mobile-open'));
        console.log('Body sidebar-collapsed:', document.body.classList.contains('sidebar-collapsed'));
        console.log('Main content margin-left:', getComputedStyle(mainContent).marginLeft);
        console.log('===========================');
    };

    // Auto-refresh setiap 30 detik
    setTimeout(() => {
        console.log('🔄 Auto-refresh dashboard');
        location.reload();
    }, 30000);
});
</script>

</body>
</html>