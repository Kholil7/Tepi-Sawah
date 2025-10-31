<?php
// mulai output buffering supaya tidak terjadi "Headers already sent"
ob_start();

// tampilkan semua error (hanya untuk dev; matikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// koneksi database
require '../../database/connect.php';

function clean($v){ return trim(htmlspecialchars($v ?? '')); }

// === TAMBAH MENU ===
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama = clean($_POST['nama_menu']);
    $kategori = strtolower(clean($_POST['kategori']));
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

            // redirect aman lewat JavaScript (kamu juga bisa pakai header() karena buffering aktif)
            echo "<script>alert('✅ Menu berhasil ditambahkan!'); window.location='tambah_menu.php';</script>";
            // flush buffer & exit
            ob_end_flush();
            exit;
        } else {
            echo "<script>alert('❌ Format gambar tidak didukung!');</script>";
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
            } else {
                echo "<script>alert('❌ Format gambar tidak didukung!');</script>";
            }
        } else {
            $stmt = $conn->prepare("UPDATE menu SET nama_menu=?, kategori=?, harga=?, status_menu=? WHERE id_menu=?");
            $stmt->bind_param("ssdsi", $nama, $kategori, $harga, $status, $id);
        }
        if (isset($stmt)) {
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('✅ Menu berhasil diperbarui!'); window.location='tambah_menu.php';</script>";
            ob_end_flush();
            exit;
        }
    } else {
        echo "<script>alert('⚠️ Field belum lengkap!');</script>";
    }
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
/* (masukkan semua CSS yang kamu pakai sebelumnya) */
body{margin:0;font-family:'Segoe UI',sans-serif;background:#f9fafb;color:#333;display:flex;min-height:100vh;}
aside{width:250px;flex-shrink:0;border-right:1px solid #e5e7eb;background:#fff;}
main{flex-grow:1;padding:100px 40px 60px;background:#f3f4f6;box-sizing:border-box; po}
.content-wrapper{background:#fff;border-radius:20px;padding:30px;box-shadow:0 4px 14px rgba(0,0,0,0.08);}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;background:linear-gradient(90deg,#e0e7ff,#f8fafc);padding:20px 30px;border-radius:14px;box-shadow:inset 0 2px 6px rgba(0,0,0,0.05);}
.topbar h1{margin:0;font-size:26px;color:#1e3a8a;font-weight:700;}
.topbar p{margin:4px 0 0;color:#475569;}
.btn-tambah{background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;border:none;padding:10px 22px;border-radius:10px;cursor:pointer;font-weight:600;font-size:15px;display:flex;align-items:center;gap:8px;box-shadow:0 4px 10px rgba(37,99,235,0.3);transition:.25s;}
.btn-tambah:hover{background:linear-gradient(135deg,#1d4ed8,#1e3a8a);transform:translateY(-2px);}
.tab{background:#f1f5f9;border:none;padding:10px 16px;border-radius:10px;cursor:pointer;color:#475569;font-size:14px;transition:.3s;margin-right:6px;}
.tab.active{background-color:#2563eb;color:white;box-shadow:0 2px 6px rgba(37,99,235,0.3);}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin-top:20px;}
.card{background:#fff;border-radius:16px;box-shadow:0 3px 10px rgba(0,0,0,0.08);padding:16px;text-align:center;transition:.2s;position:relative;}
.card:hover{transform:translateY(-3px);}
.card img{width:100%;height:160px;object-fit:cover;border-radius:12px;}
.card h3{margin:12px 0 4px;text-transform:capitalize;}
.card .kategori{display:inline-block;background:#f1f5f9;padding:4px 10px;border-radius:12px;font-size:13px;color:#475569;}
.card .harga{color:#f59e0b;font-weight:600;margin-top:6px;}
.card button{margin-top:10px;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;background:#f3f4f6;}
.card button:hover{background:#e5e7eb;}
.search-box{width:100%;border:1px solid #ddd;padding:12px 16px;border-radius:12px;font-size:15px;margin-top:20px;}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);justify-content:center;align-items:center;z-index:9999;padding:20px;opacity:0;transition:opacity .2s ease;}
.modal.show{opacity:1;}
.modal-content{background:#fff;padding:24px;border-radius:16px;width:100%;max-width:500px;position:relative;animation:fadeIn .25s ease;}
@keyframes fadeIn{from{opacity:0;transform:scale(.95);}to{opacity:1;transform:scale(1);} }
.close-btn{position:absolute;top:10px;right:16px;font-size:22px;color:#555;cursor:pointer;}
.close-btn:hover{color:red;}
.modal-content h2{text-align:center;color:#1e3a8a;margin-top:0;}
.modal-content label{display:block;margin-top:10px;font-weight:600;}
.modal-content input,.modal-content select{width:100%;padding:10px;margin-top:4px;border:1px solid #ccc;border-radius:6px;}
.modal-content .actions{margin-top:16px;display:flex;justify-content:flex-end;gap:10px;}
.modal-content button{padding:10px 14px;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
.save{background:#2563eb;color:#fff;}
.switch{position:relative;display:inline-block;width:50px;height:28px;}
.switch input{display:none;}
.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#ccc;transition:.3s;border-radius:34px;}
.slider:before{position:absolute;content:"";height:20px;width:20px;left:4px;bottom:4px;background-color:white;transition:.3s;border-radius:50%;}
input:checked + .slider{background-color:#2563eb;}
input:checked + .slider:before{transform:translateX(22px);}
.toggle-container{display:flex;align-items:center;gap:10px;margin-top:6px;}
.status-badge{position:absolute;top:10px;right:10px;background:#ef4444;color:#fff;padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600;}
</style>
</head>
<body>
<!-- include sidebar (boleh tetap di sini karena buffering mencegah error headers) -->
<?php include '../../sidebar/sidebar.php'; ?>

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
        <div class="card" 
          data-id="<?= $row['id_menu'] ?>" 
          data-nama="<?= htmlspecialchars($row['nama_menu']) ?>" 
          data-kategori="<?= htmlspecialchars($row['kategori']) ?>" 
          data-harga="<?= htmlspecialchars($row['harga']) ?>" 
          data-status="<?= htmlspecialchars($row['status_menu']) ?>">
          <?php if($row['status_menu']==='nonaktif'): ?>
            <div class="status-badge">Nonaktif</div>
          <?php endif; ?>
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
    <span class="close-btn">&times;</span>
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
      <label>Status Aktif</label>
      <div class="toggle-container">
        <label class="switch">
          <input type="checkbox" id="add_status_toggle" checked>
          <span class="slider round"></span>
        </label>
        <input type="hidden" name="status_menu" id="add_status" value="aktif">
      </div>
      <label>Gambar</label><input type="file" name="gambar" accept="image/*" required>
      <div class="actions"><button type="submit" class="save">Simpan</button></div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>Edit Menu</h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id_menu" id="edit_id">
      <label>Nama Menu</label><input name="nama_menu" id="edit_nama" required>
      <label>Kategori</label>
      <select name="kategori" id="edit_kategori" required>
        <option value="makanan">Makanan</option>
        <option value="minuman">Minuman</option>
        <option value="cemilan">Cemilan</option>
      </select>
      <label>Harga</label><input type="number" name="harga" id="edit_harga" required>
      <label>Status Aktif</label>
      <div class="toggle-container">
        <label class="switch">
          <input type="checkbox" id="edit_status_toggle">
          <span class="slider round"></span>
        </label>
        <input type="hidden" name="status_menu" id="edit_status" value="nonaktif">
      </div>
      <label>Ganti Gambar (Opsional)</label><input type="file" name="gambar" accept="image/*">
      <div class="actions"><button type="submit" class="save">Perbarui Menu</button></div>
    </form>
  </div>
</div>

<script>
// buka modal tambah
document.getElementById('openModal').onclick = () => openModal('addModal');

// tombol X di modal
document.querySelectorAll('.close-btn').forEach(btn => {
  btn.onclick = () => closeModal(btn.closest('.modal').id);
});

// toggle add
document.getElementById('add_status_toggle').addEventListener('change', function(){
  document.getElementById('add_status').value = this.checked ? 'aktif' : 'nonaktif';
});

// toggle edit
const statusToggle = document.getElementById('edit_status_toggle');
const statusHidden = document.getElementById('edit_status');
statusToggle.addEventListener('change', function() {
  statusHidden.value = this.checked ? 'aktif' : 'nonaktif';
});

// tombol edit per card
document.querySelectorAll('.editBtn').forEach(btn => {
  btn.onclick = function() {
    const card = this.closest('.card');
    document.getElementById('edit_id').value = card.dataset.id;
    document.getElementById('edit_nama').value = card.dataset.nama;
    document.getElementById('edit_kategori').value = card.dataset.kategori;
    document.getElementById('edit_harga').value = card.dataset.harga;
    const status = card.dataset.status;
    statusHidden.value = status;
    statusToggle.checked = (status === 'aktif');
    openModal('editModal');
  };
});

// fungsi buka/tutup modal
function openModal(id){
  const modal = document.getElementById(id);
  modal.style.display = 'flex';
  setTimeout(()=>modal.classList.add('show'),10);
}
function closeModal(id){
  const modal = document.getElementById(id);
  modal.classList.remove('show');
  setTimeout(()=>{
    modal.style.display='none';
    const form = modal.querySelector('form');
    if(form) form.reset();
  },200);
}
window.onclick = e => {
  if(e.target.classList.contains('modal')) closeModal(e.target.id);
};

// kategori filter & search (sama seperti sebelumnya)
const tabs=document.querySelectorAll('.tab');
const cards=document.querySelectorAll('.card');
tabs.forEach(tab=>{
  tab.onclick=()=>{
    tabs.forEach(t=>t.classList.remove('active'));
    tab.classList.add('active');
    const filter=tab.dataset.filter;
    cards.forEach(card=>{
      const kategori=card.dataset.kategori;
      card.style.display=(filter==='semua'||kategori===filter)?'block':'none';
    });
  };
});
document.getElementById('searchMenu').addEventListener('input',function(){
  const val=this.value.toLowerCase();
  cards.forEach(card=>{
    const nama=card.dataset.nama.toLowerCase();
    card.style.display=nama.includes(val)?'block':'none';
  });
});
</script>
</body>
</html>
<?php
// pastikan buffer dikirim ke browser
if (ob_get_level()) ob_end_flush();
