<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../database/connect.php';

function clean($v){ return trim(htmlspecialchars($v ?? '')); }

// === TAMBAH MENU ===
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama = clean($_POST['nama_menu']);
    $kategori = strtolower(clean($_POST['kategori'])); // enum wajib lowercase
    $harga = floatval($_POST['harga']);
    $status = clean($_POST['status_menu']);
    $gambar = $_FILES['gambar']['name'] ?? '';

    if ($nama && $kategori && $harga && $status && $gambar) {
        $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            $uploadDir = '../../assets/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $newName = time().'_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($gambar, PATHINFO_FILENAME)).'.'.$ext;
            move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadDir.$newName);

            $stmt = $conn->prepare("INSERT INTO menu (nama_menu, kategori, harga, status_menu, gambar) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdss", $nama, $kategori, $harga, $status, $newName);
            $stmt->execute();
            $stmt->close();

            echo "<script>alert('✅ Menu berhasil ditambahkan!'); window.location='tambah_menu.php';</script>";
            exit;
        } else {
            echo "<script>alert('❌ Format gambar tidak didukung! Hanya jpg, jpeg, png, webp');</script>";
        }
    } else {
        echo "<script>alert('⚠️ Semua field wajib diisi!');</script>";
    }
}

// === EDIT MENU ===
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id_menu']);
    $nama = clean($_POST['nama_menu']);
    $kategori = strtolower(clean($_POST['kategori']));
    $harga = floatval($_POST['harga']);
    $status = clean($_POST['status_menu']);
    $gambar = $_FILES['gambar']['name'] ?? '';

    if ($id && $nama && $kategori && $harga && $status) {
        if ($gambar) {
            $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                $uploadDir = '../../assets/uploads/';
                $newName = time().'_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($gambar, PATHINFO_FILENAME)).'.'.$ext;
                move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadDir.$newName);

                $stmt = $conn->prepare("UPDATE menu SET nama_menu=?, kategori=?, harga=?, status_menu=?, gambar=? WHERE id_menu=?");
                $stmt->bind_param("ssdssi", $nama, $kategori, $harga, $status, $newName, $id);
            }
        } else {
            $stmt = $conn->prepare("UPDATE menu SET nama_menu=?, kategori=?, harga=?, status_menu=? WHERE id_menu=?");
            $stmt->bind_param("ssdsi", $nama, $kategori, $harga, $status, $id);
        }
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('✅ Menu berhasil diperbarui!'); window.location='tambah_menu.php';</script>";
        exit;
    } else {
        echo "<script>alert('⚠️ Field belum lengkap!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Menu | Resto Owner</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{margin:0;font-family:'Segoe UI',sans-serif;background:#f9fafb;color:#333;display:flex;min-height:100vh;}
aside{width:250px;flex-shrink:0;border-right:1px solid #e5e7eb;background:#fff;}
main{flex-grow:1;padding:100px 40px 60px;background:#f3f4f6;box-sizing:border-box;}
.content-wrapper{background:#fff;border-radius:20px;padding:30px;box-shadow:0 4px 14px rgba(0,0,0,0.08);}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;background:linear-gradient(90deg,#e0e7ff,#f8fafc);padding:20px 30px;border-radius:14px;box-shadow:inset 0 2px 6px rgba(0,0,0,0.05);}
.topbar h1{margin:0;font-size:26px;color:#1e3a8a;font-weight:700;}
.topbar p{margin:4px 0 0;color:#475569;}
.btn-tambah{background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;border:none;padding:10px 22px;border-radius:10px;cursor:pointer;font-weight:600;font-size:15px;display:flex;align-items:center;gap:8px;box-shadow:0 4px 10px rgba(37,99,235,0.3);transition:.25s;}
.btn-tambah:hover{background:linear-gradient(135deg,#1d4ed8,#1e3a8a);transform:translateY(-2px);}
.tab{background:#f1f5f9;border:none;padding:10px 16px;border-radius:10px;cursor:pointer;color:#475569;font-size:14px;transition:.3s;}
.tab.active{background-color:#2563eb;color:white;box-shadow:0 2px 6px rgba(37,99,235,0.3);}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin-top:20px;}
.card{background:#fff;border-radius:16px;box-shadow:0 3px 10px rgba(0,0,0,0.08);padding:16px;text-align:center;transition:.2s;}
.card:hover{transform:translateY(-3px);}
.card img{width:100%;height:160px;object-fit:cover;border-radius:12px;}
.card h3{margin:12px 0 4px;text-transform:capitalize;}
.card .kategori{display:inline-block;background:#f1f5f9;padding:4px 10px;border-radius:12px;font-size:13px;color:#475569;}
.card .harga{color:#f59e0b;font-weight:600;margin-top:6px;}
.card button{margin-top:10px;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;background:#f3f4f6;}
.card button:hover{background:#e5e7eb;}
.search-box{width:100%;border:1px solid #ddd;padding:12px 16px;border-radius:12px;font-size:15px;margin-top:20px;}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);justify-content:center;align-items:center;z-index:9999;padding:20px;}
.modal-content{background:#fff;padding:24px;border-radius:16px;width:100%;max-width:500px;position:relative;animation:fadeIn .25s ease;}
@keyframes fadeIn{from{opacity:0;transform:scale(.95);}to{opacity:1;transform:scale(1);} }
.close-btn{position:absolute;top:10px;right:16px;font-size:22px;color:#555;cursor:pointer;}
.modal-content h2{text-align:center;color:#1e3a8a;margin-top:0;}
.modal-content label{display:block;margin-top:10px;font-weight:600;}
.modal-content input,.modal-content select{width:100%;padding:10px;margin-top:4px;border:1px solid #ccc;border-radius:6px;}
.modal-content .actions{margin-top:16px;display:flex;justify-content:flex-end;gap:10px;}
.modal-content button{padding:10px 14px;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
.save{background:#2563eb;color:#fff;}
.cancel{background:#e5e7eb;}
</style>
</head>
<body>
<aside><?php include '../../sidebar/sidebar.php'; ?></aside>

<main>
  <div class="content-wrapper">
    <div class="topbar">
      <div>
        <h1>Daftar Menu</h1>
        <p>Kelola menu makanan & minuman dengan mudah</p>
      </div>
      <button id="openModal" class="btn-tambah"><i class="fa fa-plus"></i> Tambah Menu</button>
    </div>

    <div class="tabs">
      <button class="tab active" data-filter="semua">Semua</button>
      <button class="tab" data-filter="makanan">Makanan</button>
      <button class="tab" data-filter="minuman">Minuman</button>
      <button class="tab" data-filter="cemilan">Cemilan</button>
    </div>

    <input type="text" id="searchMenu" class="search-box" placeholder="Cari menu...">

    <div class="grid" id="menuGrid">
      <?php
      $menus = $conn->query("SELECT * FROM menu ORDER BY id_menu DESC");
      while($row = $menus->fetch_assoc()):
      ?>
        <div class="card" data-nama="<?= htmlspecialchars($row['nama_menu']) ?>" data-kategori="<?= htmlspecialchars($row['kategori']) ?>">
          <img src="../../assets/uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="">
          <h3><?= htmlspecialchars($row['nama_menu']) ?></h3>
          <span class="kategori"><?= ucfirst($row['kategori']) ?></span>
          <div class="harga">Rp <?= number_format($row['harga'],0,',','.') ?></div>
          <button class="editBtn">Edit</button>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</main>

<!-- Modal Tambah -->
<div class="modal" id="addModal">
  <div class="modal-content">
    <span class="close-btn" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
    <h2>Tambah Menu</h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="tambah">
      <label>Nama Menu</label><input name="nama_menu" required>
      <label>Kategori</label>
      <select name="kategori" required>
        <option value="">Pilih</option>
        <option value="makanan">Makanan</option>
        <option value="minuman">Minuman</option>
        <option value="cemilan">Cemilan</option>
      </select>
      <label>Harga</label><input type="number" name="harga" required>
      <label>Status</label>
      <select name="status_menu" required>
        <option value="aktif">Aktif</option>
        <option value="nonaktif">Nonaktif</option>
      </select>
      <label>Gambar</label><input type="file" name="gambar" accept="image/*" required>
      <div class="actions"><button type="submit" class="save">Simpan</button></div>
    </form>
  </div>
</div>

<script>
document.getElementById('openModal').onclick=()=>document.getElementById('addModal').style.display='flex';
window.onclick=e=>{if(e.target.classList.contains('modal'))e.target.style.display='none';};

// filter kategori
const tabs=document.querySelectorAll('.tab'),cards=document.querySelectorAll('.card');
tabs.forEach(tab=>{
  tab.onclick=()=>{
    tabs.forEach(t=>t.classList.remove('active'));tab.classList.add('active');
    const filter=tab.dataset.filter;
    cards.forEach(c=>c.style.display=(filter==='semua'||c.dataset.kategori===filter)?'block':'none');
  };
});

// pencarian
document.getElementById('searchMenu').oninput=function(){
  const val=this.value.toLowerCase();
  cards.forEach(c=>{
    const nama=c.dataset.nama.toLowerCase();
    c.style.display=nama.includes(val)?'block':'none';
  });
};
</script>
</body>
</html>
