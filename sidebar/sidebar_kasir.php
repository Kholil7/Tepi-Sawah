<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 260px;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      height: 100vh;
      transition: all 0.3s ease;
      overflow-y: auto;
      z-index: 1000;
    }

    .sidebar.collapsed {
      width: 85px;
    }

    .sidebar-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.8rem 1.2rem 0.8rem;
      border-bottom: 1px solid #eee;
    }

    .sidebar-header .title h2 {
      font-size: 1rem;
      color: #ff9f00;
      font-weight: 600;
      line-height: 1.2;
    }

    .sidebar-header .title span {
      color: #000;
    }

    .sidebar-header .title p {
      font-size: 0.75rem;
      color: gray;
    }

    #toggle-btn {
      background: none;
      border: none;
      font-size: 1.3rem;
      cursor: pointer;
      color: #444;
      transition: transform 0.3s ease, color 0.3s ease;
    }

    #toggle-btn:hover {
      color: #ff9f00;
      transform: scale(1.2);
    }

    .menu {
      display: flex;
      flex-direction: column;
      padding-top: 1rem;
    }

    .menu a {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px 16px;
      color: #333;
      text-decoration: none;
      border-radius: 10px;
      margin: 4px 8px;
      transition: all 0.3s ease;
    }

    .menu a:hover {
      background: rgba(255, 159, 0, 0.15);
    }

    .menu a.active {
      background: #ff9f00;
      color: #fff;
      font-weight: 600;
    }

    .menu a.active i {
      color: #fff;
    }

    .menu-footer {
      margin-top: auto;
      padding: 5px 0 8px;
      background: #fff;
      border-top: 1px solid #eee;
    }

    .logout-divider {
      border: none;
      border-top: 2px solid #000;
      margin: 8px 16px;
    }

    .logout-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px 16px;
      cursor: pointer;
      color: #e74c3c;
      font-weight: 500;
      border-radius: 10px;
      transition: all 0.3s ease;
      margin: 0 8px;
    }

    .logout-item:hover {
      background: rgba(255, 159, 0, 0.15);
    }

    .sidebar.collapsed .title,
    .sidebar.collapsed span {
      display: none;
    }

    .sidebar.collapsed .logout-item span {
      display: none;
    }

    .sidebar.collapsed .menu a {
      justify-content: center;
    }

    .sidebar.collapsed .logout-item {
      justify-content: center;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 260px;
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .sidebar.collapsed {
        transform: translateX(-100%);
      }
    }
  </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="title">
        <h2>Resto<span>Cashier</span></h2>
        <p>Kasir Dashboard</p>
      </div>
      <button id="toggle-btn"><i class="fa-solid fa-angles-left"></i></button>
    </div>

    <div class="menu">
      <a href="../inside/dashboard_kasir.php" class="<?= str_contains(basename($_SERVER['PHP_SELF']), 'index') ? 'active' : '' ?>">
        <i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span>
      </a>

      <a href="../inside/InputPesananBaru.php" class="<?= str_contains(basename($_SERVER['PHP_SELF']), 'InputPesananBaru') ? 'active' : '' ?>">
        <i class="fa-solid fa-plus-circle"></i><span>Input Pesanan</span>
      </a>

      <a href="pesanan_aktif.php" class="<?= str_contains(basename($_SERVER['PHP_SELF']), 'pesanan_aktif') ? 'active' : '' ?>">
        <i class="fa-solid fa-list"></i><span>Pesanan Aktif</span>
      </a>

      <a href="meja_kasir.php" class="<?= str_contains(basename($_SERVER['PHP_SELF']), 'meja') ? 'active' : '' ?>">
        <i class="fa-solid fa-table-cells"></i><span>Meja</span>
      </a>

      <a href="menu.php" class="<?= str_contains(basename($_SERVER['PHP_SELF']), 'menu') ? 'active' : '' ?>">
        <i class="fa-solid fa-utensils"></i><span>Menu</span>
      </a>

      <a href="../inside/pembayaran-pesanan.php" class="<?= str_contains(basename($_SERVER['PHP_SELF']), 'pembayaran-pesanan.php') ? 'active' : '' ?>">
        <i class="fa-solid fa-credit-card"></i><span>Pembayaran</span>
      </a>

      <a href="transaksi_harian.php" class="<?= str_contains(basename($_SERVER['PHP_SELF']), 'transaksi_harian') ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i><span>Transaksi Harian</span>
      </a>

      <a href="pembatalan.php" class="<?= str_contains(basename($_SERVER['PHP_SELF']), 'pembatalan') ? 'active' : '' ?>">
        <i class="fa-solid fa-xmark-circle"></i><span>Pembatalan</span>
      </a>
    </div>

    <div class="menu-footer">
      <hr class="logout-divider">
      <div class="logout-item">
        <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
      </div>
    </div>
  </div>

<script>
  const sidebar = document.querySelector('.sidebar');
  const toggleBtn = document.querySelector('#toggle-btn');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
      mainContent.classList.toggle('collapsed');
    }
    const topbar = document.querySelector('.topbar');
    if (topbar) {
      topbar.classList.toggle('collapsed');
    }
  });

  const menuToggle = document.querySelector('.menu-toggle');
  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });
  }
</script>
</body>
</html>
