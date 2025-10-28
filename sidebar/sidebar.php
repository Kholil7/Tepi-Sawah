<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['nama'])) {
  $_SESSION['nama'] = "Owner";
}

// Tangkap nama file aktif (tanpa path)
$current = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --blue:#3B82F6;
  --sidebar-w:240px;
  --sidebar-w-collapsed:80px;
  --header-h:60px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;
  background:#f8fafc;
  color:#0f172a;
  min-height:100vh;
  overflow-x:hidden;
}
.sidebar{
  position:fixed;
  top:0;left:0;
  height:100vh;
  width:var(--sidebar-w);
  background:#fff;
  border-right:1px solid #e6eefc;
  box-shadow:2px 0 8px rgba(15,23,42,0.04);
  display:flex;
  flex-direction:column;
  transition:width .25s ease,transform .25s ease;
  z-index:1000;
}
.sidebar.collapsed{width:var(--sidebar-w-collapsed);}
.sidebar .hero{
  height:var(--header-h);
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 16px;
  border-bottom:1px solid #f1f5f9;
}
.sidebar .hero .title{
  color:var(--blue);
  font-weight:600;
  font-size:16px;
  transition:opacity .2s ease;
}
.sidebar.collapsed .hero .title{opacity:0;pointer-events:none;}
.sidebar .hero .hamburger{
  font-size:18px;
  color:var(--blue);
  cursor:pointer;
  background:none;
  border:none;
}
.sidebar .menu{
  flex:1;
  padding:8px 0;
  overflow:auto;
}
.sidebar .menu a{
  display:flex;
  align-items:center;
  gap:12px;
  padding:10px 16px;
  color:#0f172a;
  text-decoration:none;
  border-radius:8px;
  margin:4px 8px;
  transition:.2s;
}
.sidebar .menu a:hover{
  background:#eef6ff;
  color:var(--blue);
}
.sidebar .menu a.active{
  background:#eef6ff !important;
  color:var(--blue) !important;
  border-left:4px solid var(--blue);
  padding-left:12px;
  font-weight:600;
}
.sidebar.collapsed .menu a span{display:none;}

.header{
  position:fixed;
  top:0;
  left:var(--sidebar-w);
  right:0;
  height:var(--header-h);
  background:#fff;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 16px;
  border-bottom:1px solid #eef2ff;
  box-shadow:0 2px 6px rgba(2,6,23,0.03);
  z-index:900;
  transition:left .25s ease;
}
.sidebar.collapsed ~ .header{left:var(--sidebar-w-collapsed);}
.header .left i{
  color:var(--blue);
  font-size:18px;
  cursor:pointer;
  margin-right:12px;
}
.header .right{
  display:flex;
  align-items:center;
  gap:14px;
  font-size:14px;
}
.header .user{display:flex;align-items:center;gap:6px;}

.main-content{
  margin-top:var(--header-h);
  margin-left:var(--sidebar-w);
  padding:20px;
  transition:margin-left .25s ease;
}
.sidebar.collapsed ~ .main-content{margin-left:var(--sidebar-w-collapsed);}

.overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.35);
  z-index:950;
  display:none;
}
.overlay.visible{display:block;}

@media(min-width:769px) {
  #mobileToggle{display: none;}
}
@media(max-width:1024px){
  .sidebar{
    transform:translateX(-100%);
    width:var(--sidebar-w);
  }
  .sidebar.active{transform:translateX(0);}
  .header{left:0!important;}
  .main-content{margin-left:0!important;}
}
</style>

<nav class="sidebar" id="sidebar">
  <div class="hero">
    <div class="title">Resto Owner</div>
    <button class="hamburger" id="heroToggle"><i class="fa-solid fa-bars"></i></button>
  </div>
  <div class="menu">
    <a href="dashboard.php" class="<?= str_contains($current, 'dashboard') ? 'active' : '' ?>"><i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span></a>
    <a href="ff.php" class="<?= str_contains($current, 'ff') ? 'active' : '' ?>"><i class="fa-solid fa-utensils"></i><span>Menu</span></a>
    <a href="../inside/tambah_menu.php" class="<?= str_contains($current, 'tambah_menu') ? 'active' : '' ?>"><i class="fa-solid fa-square-plus"></i><span>Input Menu</span></a>
    <a href="pembelian_bahan.php" class="<?= str_contains($current, 'pembelian_bahan') ? 'active' : '' ?>"><i class="fa-solid fa-file-circle-plus"></i><span>Input Pembelian Bahan</span></a>
    <a href="meja.php" class="<?= str_contains($current, 'meja') ? 'active' : '' ?>"><i class="fa-solid fa-table-cells"></i><span>Meja</span></a>
    <a href="pembatalan.php" class="<?= str_contains($current, 'pembatalan') ? 'active' : '' ?>"><i class="fa-solid fa-circle-xmark"></i><span>Pembatalan</span></a>
    <hr style="margin:8px 0;border:none;border-top:1px solid #f1f5f9;">
    <a href="laporan_penjualan.php" class="<?= str_contains($current, 'laporan_penjualan') ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i><span>Laporan Penjualan</span></a>
    <a href="laporan_pembelian.php" class="<?= str_contains($current, 'laporan_pembelian') ? 'active' : '' ?>"><i class="fa-solid fa-cart-shopping"></i><span>Laporan Pembelian</span></a>
  </div>
</nav>

<div class="overlay" id="overlay"></div>

<header class="header" id="header">
  <div class="left">
    <i class="fa-solid fa-bars" id="mobileToggle"></i>
    <i class="fa-solid fa-bell" title="Notifikasi"></i>
    <i class="fa-solid fa-right-from-bracket" title="Logout" onclick="location.href='../../logout.php'"></i>
  </div>
  <div class="right">
    <div id="datetime"></div>
    <div class="user"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($_SESSION['nama']); ?></div>
  </div>
</header>

<script>
const sidebar=document.getElementById('sidebar');
const heroToggle=document.getElementById('heroToggle');
const mobileToggle=document.getElementById('mobileToggle');
const overlay=document.getElementById('overlay');
const title=sidebar.querySelector('.hero .title');

function isMobile(){return window.innerWidth<=1024;}
function toggleSidebar(){
  if(isMobile()){
    sidebar.classList.toggle('active');
    const isActive=sidebar.classList.contains('active');
    overlay.classList.toggle('visible',isActive);
    title.style.display='block';
  }else{
    sidebar.classList.toggle('collapsed');
    const collapsed=sidebar.classList.contains('collapsed');
    title.style.display=collapsed?'none':'block';
    heroToggle.style.marginLeft=collapsed?'0':'auto';
    heroToggle.style.marginRight=collapsed?'auto':'0';
  }
}
heroToggle.addEventListener('click',toggleSidebar);
if(mobileToggle)mobileToggle.addEventListener('click',toggleSidebar);
overlay.addEventListener('click',()=>{
  sidebar.classList.remove('active');
  overlay.classList.remove('visible');
  if(isMobile()){title.style.display='block';}
});
window.addEventListener('resize',()=>{
  if(!isMobile()){
    sidebar.classList.remove('active');
    overlay.classList.remove('visible');
    title.style.display=sidebar.classList.contains('collapsed')?'none':'block';
  }else{title.style.display='block';}
});
function updateDateTime(){
  const now=new Date();
  const days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const day=days[now.getDay()];
  const time=now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
  document.getElementById('datetime').textContent=`${day}, ${time}`;
}
updateDateTime();
setInterval(updateDateTime,1000);
</script>
