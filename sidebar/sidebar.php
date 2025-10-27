<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['nama'])) {
  $_SESSION['nama'] = "Owner";
}
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
  background:#eef6ff;
  color:var(--blue);
  border-left:4px solid var(--blue);
  padding-left:12px;
}
.sidebar.collapsed .menu a span{display:none;}
.sidebar .logout{
  border-top:1px solid #f1f5f9;
  padding:12px;
}
.sidebar .logout a{
  display:flex;
  align-items:center;
  gap:12px;
  color:#111827;
  text-decoration:none;
  padding:10px 12px;
  border-radius:8px;
}

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

@media(max-width:1024px){
  .sidebar{
    transform:translateX(-100%);
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
    <a href="#" class="active"><i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span></a>
    <a href="#"><i class="fa-solid fa-chart-line"></i><span>Laporan Penjualan</span></a>
    <a href="#"><i class="fa-solid fa-cart-shopping"></i><span>Laporan Pembelian</span></a>
    <a href="#"><i class="fa-solid fa-utensils"></i><span>Menu</span></a>
    <a href="#"><i class="fa-solid fa-table-cells"></i><span>Meja</span></a>
    <a href="#"><i class="fa-solid fa-circle-xmark"></i><span>Pembatalan</span></a>
  </div>
  <!-- <div class="logout">
    <a href="../../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
  </div> -->
</nav>

<div class="overlay" id="overlay"></div>

<header class="header" id="header">
  <div class="left">
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
const overlay=document.getElementById('overlay');
function isMobile(){return window.innerWidth<=1024;}
heroToggle.addEventListener('click',()=>{
  if(isMobile()){
    sidebar.classList.toggle('active');
    overlay.classList.toggle('visible',sidebar.classList.contains('active'));
  }else{
    sidebar.classList.toggle('collapsed');
  }
});
overlay.addEventListener('click',()=>{
  sidebar.classList.remove('active');
  overlay.classList.remove('visible');
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
