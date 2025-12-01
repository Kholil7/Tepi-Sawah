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

function generateMenuID() {
    return 'MNU' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
}

if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $id_menu = generateMenuID();
    $nama = clean($_POST['nama_menu']);
    $kategori = strtolower(clean($_POST['kategori']));
    $harga = floatval($_POST['harga']);
    $gambar = $_FILES['gambar']['name'] ?? '';

    if ($nama && $kategori && $harga && $gambar) {
        $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $uploadDir = '../../assets/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $newName = time() . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($gambar, PATHINFO_FILENAME)) . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadPath)) {
                $stmt = $conn->prepare("INSERT INTO menu (id_menu, nama_menu, kategori, harga, gambar) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $id_menu, $nama, $kategori, $harga, $newName);
                if ($stmt->execute()) {
                    $_SESSION['popup'] = ['type' => 'success', 'message' => 'Menu berhasil ditambahkan!'];
                    header("Location: tambah_menu.php");
                    exit;
                } else {
                    $_SESSION['popup'] = ['type' => 'error', 'message' => 'Gagal menyimpan ke database.'];
                }
                $stmt->close();
            } else {
                $_SESSION['popup'] = ['type' => 'error', 'message' => 'Gagal mengunggah gambar.'];
            }
        } else {
            $_SESSION['popup'] = ['type' => 'error', 'message' => 'Format gambar tidak didukung! (jpg, jpeg, png, webp)'];
        }
    } else {
        $_SESSION['popup'] = ['type' => 'error', 'message' => 'Semua field wajib diisi!'];
    }
    ob_end_flush();
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = clean($_POST['id_menu']);
    $nama = clean($_POST['nama_menu']);
    $kategori = strtolower(clean($_POST['kategori']));
    $harga = floatval($_POST['harga']);
    $gambar = $_FILES['gambar']['name'] ?? '';

    if ($id && $nama && $kategori && $harga) {
        $uploadDir = '../../assets/uploads/';
        $newName = null;

        if ($gambar) {
            $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $newName = time() . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($gambar, PATHINFO_FILENAME)) . '.' . $ext;
                $uploadPath = $uploadDir . $newName;
                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadPath)) {
                    $_SESSION['popup'] = ['type' => 'error', 'message' => 'Gagal mengunggah gambar baru.'];
                    $newName = null;
                }
            } else {
                $_SESSION['popup'] = ['type' => 'error', 'message' => 'Format gambar tidak didukung!'];
                $newName = null;
            }
        }

        if ($newName) {
            $old = $conn->query("SELECT gambar FROM menu WHERE id_menu = '$id'")->fetch_assoc()['gambar'];
            if ($old && file_exists($uploadDir . $old)) {
                unlink($uploadDir . $old);
            }

            $stmt = $conn->prepare("UPDATE menu SET nama_menu=?, kategori=?, harga=?, gambar=? WHERE id_menu=?");
            $stmt->bind_param("ssdss", $nama, $kategori, $harga, $newName, $id);
        } else {
            $stmt = $conn->prepare("UPDATE menu SET nama_menu=?, kategori=?, harga=? WHERE id_menu=?");
            $stmt->bind_param("ssds", $nama, $kategori, $harga, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['popup'] = ['type' => 'success', 'message' => 'Menu berhasil diperbarui!'];
            header("Location: tambah_menu.php");
            exit;
        } else {
            $_SESSION['popup'] = ['type' => 'error', 'message' => 'Gagal memperbarui menu.'];
        }
        $stmt->close();
    } else {
        $_SESSION['popup'] = ['type' => 'error', 'message' => 'Field belum lengkap!'];
    }
    ob_end_flush();
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
body{margin:0;font-family:'Segoe UI',sans-serif;background:#f9fafb;color:#333;display:flex;min-height:100vh;overflow-x:hidden;}
aside{width:250px;background:#fff;border-right:1px solid #e5e7eb;transition:width .3s ease;flex-shrink:0;position:fixed;top:0;left:0;bottom:0;z-index:1000;}
main{flex-grow:1;margin-left:250px;transition:margin-left .3s ease;padding:90px 40px 60px;background:#f3f4f6;box-sizing:border-box;min-height:100vh;}
aside.collapsed{width:70px;}
aside.collapsed + main{margin-left:70px;}
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
.modal.show{opacity:1;display:flex;}
.modal-content{background:#fff;padding:24px;border-radius:16px;width:100%;max-width:500px;position:relative;animation:fadeIn .25s ease;}
@keyframes fadeIn{from{opacity:0;transform:scale(.95);}to{opacity:1;transform:scale(1);}}
.close-btn{position:absolute;top:10px;right:16px;font-size:22px;color:#555;cursor:pointer;}
.close-btn:hover{color:red;}
.modal-content h2{text-align:center;color:#1e3a8a;margin-top:0;}
.modal-content label{display:block;margin-top:10px;font-weight:600;}
.modal-content input,.modal-content select{width:100%;padding:10px;margin-top:4px;border:1px solid #ccc;border-radius:6px;}
.modal-content .actions{margin-top:16px;display:flex;justify-content:flex-end;gap:10px;}
.modal-content button{padding:10px 14px;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
.save{background:#2563eb;color:#fff;}
.popup{position:fixed;top:20px;right:20px;background:#fff;padding:20px 24px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.15);z-index:10000;min-width:300px;max-width:400px;display:none;animation:slideIn .4s ease;}
@keyframes slideIn{from{transform:translateX(400px);opacity:0;}to{transform:translateX(0);opacity:1;}}
.popup.show{display:block;}
.popup.success{border-left:4px solid blue;}
.popup.error{border-left:4px solid #ef4444;}
.popup-header{display:flex;align-items:center;gap:12px;margin-bottom:8px;}
.popup-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;}
.popup.success .popup-icon{background:#d1fae5;color:#10b981;}
.popup.error .popup-icon{background:#fee2e2;color:#ef4444;}
.popup-title{font-weight:700;font-size:16px;margin:0;}
.popup.success .popup-title{color:#10b981;}
.popup.error .popup-title{color:#ef4444;}
.popup-message{color:#6b7280;font-size:14px;margin:0;line-height:1.5;}
.popup-close{position:absolute;top:12px;right:12px;background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;padding:0;width:24px;height:24px;display:flex;align-items:center;justify-content:center;}
.popup-close:hover{color:#374151;}
</style>
</head>
<body>
<?php include '../../sidebar/sidebar.php'; ?>

<!-- Popup Notification -->
<div id="popup" class="popup">
  <button class="popup-close" onclick="closePopup()">&times;</button>
  <div class="popup-header">
    <div class="popup-icon">
      <i id="popup-icon-el"></i>
    </div>
    <h3 class="popup-title" id="popup-title"></h3>
  </div>
  <p class="popup-message" id="popup-message"></p>
</div>

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
      if ($menus->num_rows > 0):
        while($row = $menus->fetch_assoc()):
      ?>
        <div class="card" 
          data-id="<?= $row['id_menu'] ?>" 
          data-nama="<?= htmlspecialchars($row['nama_menu']) ?>" 
          data-kategori="<?= htmlspecialchars($row['kategori']) ?>" 
          data-harga="<?= htmlspecialchars($row['harga']) ?>">
          <img src="../../assets/uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_menu']) ?>">
          <h3><?= htmlspecialchars($row['nama_menu']) ?></h3>
          <span class="kategori"><?= ucfirst($row['kategori']) ?></span>
          <div class="harga">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div>
          <button class="editBtn">Edit</button>
        </div>
      <?php 
        endwhile; 
      else: 
      ?>
        <p>Tidak ada menu. Tambahkan menu pertama!</p>
      <?php endif; ?>
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
      <label>Nama Menu</label>
      <input name="nama_menu" required>
      <label>Kategori</label>
      <select name="kategori" required>
        <option value="">Pilih</option>
        <option value="makanan">Makanan</option>
        <option value="minuman">Minuman</option>
        <option value="cemilan">Cemilan</option>
      </select>
      <label>Harga</label>
      <input type="number" name="harga" min="0" required>
      <label>Gambar</label>
      <input type="file" name="gambar" accept="image/*" required>
      <div class="actions">
        <button type="submit" class="save">Simpan</button>
      </div>
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
      <label>Nama Menu</label>
      <input name="nama_menu" id="edit_nama" required>
      <label>Kategori</label>
      <select name="kategori" id="edit_kategori" required>
        <option value="makanan">Makanan</option>
        <option value="minuman">Minuman</option>
        <option value="cemilan">Cemilan</option>
      </select>
      <label>Harga</label>
      <input type="number" name="harga" id="edit_harga" min="0" required>
      <label>Ganti Gambar (Opsional)</label>
      <input type="file" name="gambar" accept="image/*">
      <div class="actions">
        <button type="submit" class="save">Perbarui Menu</button>
      </div>
    </form>
  </div>
</div>

<script>
// Popup System
function showPopup(type, message) {
    const popup = document.getElementById('popup');
    const title = document.getElementById('popup-title');
    const msg = document.getElementById('popup-message');
    const icon = document.getElementById('popup-icon-el');
    
    popup.className = 'popup ' + type;
    
    if (type === 'success') {
        title.textContent = 'Berhasil!';
        icon.className = 'fa fa-check';
    } else {
        title.textContent = 'Gagal!';
        icon.className = 'fa fa-times';
    }
    
    msg.textContent = message;
    popup.classList.add('show');
    
    setTimeout(() => closePopup(), 5000);
}

function closePopup() {
    const popup = document.getElementById('popup');
    popup.classList.remove('show');
}

// Check for popup from PHP
<?php if (isset($_SESSION['popup'])): ?>
    showPopup('<?= $_SESSION['popup']['type'] ?>', '<?= addslashes($_SESSION['popup']['message']) ?>');
    <?php unset($_SESSION['popup']); ?>
<?php endif; ?>

document.addEventListener('DOMContentLoaded', function() {
    // Modal
    document.getElementById('openModal').onclick = () => openModal('addModal');
    document.querySelectorAll('.close-btn').forEach(btn => {
        btn.onclick = () => closeModal(btn.closest('.modal').id);
    });

    function openModal(id) {
        const modal = document.getElementById(id);
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            const form = modal.querySelector('form');
            if (form) form.reset();
        }, 200);
    }

    window.onclick = e => {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    };

    // Edit Button
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.onclick = function() {
            const card = this.closest('.card');
            document.getElementById('edit_id').value = card.dataset.id;
            document.getElementById('edit_nama').value = card.dataset.nama;
            document.getElementById('edit_kategori').value = card.dataset.kategori;
            document.getElementById('edit_harga').value = card.dataset.harga;
            openModal('editModal');
        };
    });

    // Filter & Search
    const tabs = document.querySelectorAll('.tab');
    const cards = document.querySelectorAll('.card');
    const searchInput = document.getElementById('searchMenu');

    tabs.forEach(tab => {
        tab.onclick = () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const filter = tab.dataset.filter;
            filterCards(filter, searchInput.value);
        };
    });

    searchInput.addEventListener('input', function() {
        const activeFilter = document.querySelector('.tab.active').dataset.filter;
        filterCards(activeFilter, this.value);
    });

    function filterCards(category, search) {
        const lowerSearch = search.toLowerCase();
        cards.forEach(card => {
            const matchesCategory = (category === 'semua' || card.dataset.kategori === category);
            const matchesSearch = card.dataset.nama.toLowerCase().includes(lowerSearch);
            card.style.display = (matchesCategory && matchesSearch) ? 'block' : 'none';
        });
    }
});
</script>
</body>
</html>

<?php ob_end_flush(); ?>