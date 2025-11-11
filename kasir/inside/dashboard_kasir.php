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
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  display: flex;
  font-family: 'Segoe UI', sans-serif;
  background: #f8f9fa;
  transition: margin-left 0.4s ease, width 0.4s ease;
  overflow-x: hidden;
}

/* ===== SIDEBAR ===== */
.sidebar {
  width: 260px;
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  background: #fff;
  box-shadow: 2px 0 10px rgba(0,0,0,0.08);
  transition: width 0.4s ease, background 0.3s ease;
  z-index: 100;
  overflow: hidden;
}

.sidebar.active {
  width: 80px;
}

/* ===== MAIN CONTENT ===== */
.main-content {
  flex: 1;
  margin-left: 260px;
  padding: 25px;
  transition: margin-left 0.4s ease, transform 0.4s ease, opacity 0.4s ease;
}

body.sidebar-collapsed .main-content {
  margin-left: 80px;
  transform: translateX(-10px);
  opacity: 0.95;
}


/* ===== HEADER ===== */
.page-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 30px;
  border-radius: 12px;
  margin-bottom: 25px;
  box-shadow: 0 4px 20px rgba(102,126,234,0.3);
  transition: all 0.4s ease;
}
.page-header h1 {
  font-size: 26px;
  margin-bottom: 6px;
}

/* ===== QUICK BUTTONS ===== */
.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
  gap: 15px;
  margin-bottom: 25px;
}
.quick-btn {
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  text-align: center;
  text-decoration: none;
  color: #1e293b;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
}
.quick-btn:hover {
  transform: translateY(-4px);
  box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}
.quick-btn i {
  font-size: 30px;
  margin-bottom: 8px;
  color: #3b82f6;
}
.quick-btn.success i { color: #10b981; }
.quick-btn.warning i { color: #f59e0b; }
.quick-btn.danger i { color: #ef4444; }

/* ===== STATS ===== */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
  gap: 15px;
  margin-bottom: 20px;
}
.stat-card {
  background: #fff;
  border-radius: 10px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  transition: transform 0.3s;
}
.stat-card:hover {
  transform: translateY(-4px);
}
.stat-card h3 { font-size: 16px; color: #64748b; margin-bottom: 6px; }
.stat-card p { font-size: 28px; font-weight: 700; color: #1e293b; }
.green { color: #16a34a; }
.blue { color: #2563eb; }
.orange { color: #f59e0b; }

/* ===== LEGEND ===== */
.legend {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  align-items: center;
  flex-wrap: wrap;
}
.legend-item { display: flex; align-items: center; font-size: 14px; color: #475569; }
.color-box {
  width: 16px; height: 16px;
  border-radius: 4px;
  margin-right: 6px;
}
.bg-green { background: #bbf7d0; border: 1px solid #16a34a; }
.bg-blue { background: #bfdbfe; border: 1px solid #2563eb; }
.bg-orange { background: #fde68a; border: 1px solid #f59e0b; }

/* ===== MEJA GRID ===== */
.meja-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 15px;
}
.meja-card {
  background: #fff;
  border-radius: 10px;
  padding: 25px;
  text-align: center;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
  cursor: pointer;
  opacity: 0;
  animation: fadeInUp 0.5s ease forwards;
}
@keyframes fadeInUp {
  from { transform: translateY(10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.meja-card.kosong { background: #f0fdf4; border-color: #86efac; }
.meja-card.terisi { background: #fef3c7; border-color: #fcd34d; }
.meja-card.menunggu_pembayaran { background: #fee2e2; border-color: #fca5a5; }
.meja-card div:first-child { font-size: 26px; font-weight: 700; }
.meja-card div:last-child { font-size: 13px; color: #475569; margin-top: 6px; }
</style>
</head>
<body>

<?php include '../../sidebar/sidebar_kasir.php'; ?>

<div class="main-content" id="mainContent">
  <div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Dashboard Kasir</h1>
    <p>Selamat datang, <?= htmlspecialchars($nama ?? 'Pengguna', ENT_QUOTES, 'UTF-8') ?>! • <?= date('d F Y, H:i') ?></p>
  </div>

  <!-- QUICK ACTIONS -->
  <div class="quick-actions">
    <a href="input_pesanan.php" class="quick-btn success"><i class="fas fa-plus-circle"></i><h3>Input Pesanan</h3></a>
    <a href="pesanan_aktif.php" class="quick-btn"><i class="fas fa-list-check"></i><h3>Pesanan Aktif</h3></a>
    <a href="pembayaran.php" class="quick-btn warning"><i class="fas fa-cash-register"></i><h3>Pembayaran</h3></a>
    <a href="transaksi_harian.php" class="quick-btn danger"><i class="fas fa-receipt"></i><h3>Transaksi</h3></a>
  </div>

  <!-- STATS -->
  <div class="stats-container">
    <div class="stat-card"><h3>Total Meja</h3><p><?= $total_meja ?></p></div>
    <div class="stat-card"><h3>Kosong</h3><p class="green"><?= $kosong ?></p></div>
    <div class="stat-card"><h3>Terisi</h3><p class="blue"><?= $terisi ?></p></div>
    <div class="stat-card"><h3>Menunggu Bayar</h3><p class="orange"><?= $menunggu ?></p></div>
  </div>

  <div class="legend">
    <div class="legend-item"><div class="color-box bg-green"></div> Kosong</div>
    <div class="legend-item"><div class="color-box bg-blue"></div> Terisi</div>
    <div class="legend-item"><div class="color-box bg-orange"></div> Menunggu Bayar</div>
  </div>

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
  const sidebar = document.querySelector('.sidebar');
  const mainContent = document.getElementById('mainContent');
  const toggleBtn = document.querySelector('#toggleSidebar');

  // Fungsi untuk update state body
  function updateSidebarState() {
    if (sidebar.classList.contains('active')) {
      document.body.classList.add('sidebar-collapsed');
    } else {
      document.body.classList.remove('sidebar-collapsed');
    }
  }

  // Klik tombol toggle
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      updateSidebarState();
    });
  }

  // Observasi perubahan class sidebar (agar realtime)
  const observer = new MutationObserver(updateSidebarState);
  observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

  updateSidebarState();

  // Efek animasi masuk dashboard
  mainContent.style.opacity = '0';
  mainContent.style.transform = 'translateY(10px)';
  setTimeout(() => {
    mainContent.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    mainContent.style.opacity = '1';
    mainContent.style.transform = 'translateY(0)';
  }, 100);

  // Auto-refresh setiap 30 detik
  setTimeout(() => location.reload(), 30000);
});
</script>

</body>
</html>
