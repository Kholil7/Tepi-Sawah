<?php
// tambah_menu.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../database/connect.php';

// fungsi bantu
function clean($v) { return trim(htmlspecialchars($v ?? '')); }

$info_msg = '';
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_menu   = clean($_POST['nama_menu'] ?? '');
    $kategori    = clean($_POST['kategori'] ?? '');
    $harga_raw   = $_POST['harga'] ?? '';
    $status_menu = clean($_POST['status_menu'] ?? '');
    $uploaded_filename = null;

    // validasi wajib isi
    if ($nama_menu === '' || $kategori === '' || $harga_raw === '' || $status_menu === '') {
        $error_msg = "⚠️ Semua field wajib diisi sebelum menyimpan.";
    } elseif (!is_numeric($harga_raw)) {
        $error_msg = "⚠️ Harga harus berupa angka.";
    } else {
        $harga = (float) $harga_raw;

        // === upload gambar ===
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['gambar']['tmp_name'];
                $orig_name = $_FILES['gambar']['name'];

                if (is_uploaded_file($tmp_name)) {
                    $uploadDir = __DIR__ . "/../../assets/uploads/";
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg','jpeg','png'];
                    if (!in_array($ext, $allowed_ext)) {
                        $error_msg = "⚠️ Format gambar tidak valid. Hanya JPG, JPEG, dan PNG yang diperbolehkan.";
                    } else {
                        $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
                        $uploaded_filename = time() . '_' . $safeBase . '.' . $ext;
                        $targetPath = $uploadDir . $uploaded_filename;
                        if (!move_uploaded_file($tmp_name, $targetPath)) {
                            $error_msg = "⚠️ Gagal memindahkan file upload ke folder tujuan.";
                            $uploaded_filename = null;
                        }
                    }
                } else {
                    $error_msg = "⚠️ File upload tidak valid.";
                }
            } else {
                $error_msg = "⚠️ Terjadi kesalahan upload file.";
            }
        } else {
            $error_msg = "⚠️ Gambar wajib diunggah.";
        }

        // simpan ke DB jika tidak ada error
        if ($error_msg === '') {
            $sql = "INSERT INTO menu (nama_menu, kategori, harga, status_menu, gambar)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error_msg = "Gagal menyiapkan query: " . $conn->error;
            } else {
                $stmt->bind_param("ssdss", $nama_menu, $kategori, $harga, $status_menu, $uploaded_filename);
                if ($stmt->execute()) {
                    $info_msg = "✅ Menu berhasil disimpan.";
                    // reset field
                    $nama_menu = $kategori = $harga = $status_menu = '';
                    $uploaded_filename = null;
                } else {
                    $error_msg = "❌ Gagal menyimpan ke database: " . $stmt->error;
                    if ($uploaded_filename) @unlink($uploadDir . $uploaded_filename);
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tambah Menu</title>
<style>
body{margin:0;font-family:Segoe UI,Tahoma,sans-serif;background:#f9fafb;color:#333;display:flex;min-height:100vh;}
aside{width:250px;background:transparent;flex-shrink:0;border-right:1px solid #e5e7eb;}
main{flex-grow:1;padding:30px;display:flex;justify-content:center;align-items:flex-start;}
.form-container{background:#fff;padding:28px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);width:100%;max-width:720px;}
h1{text-align:center;color:#1e3a8a;margin-bottom:18px;}
label{display:block;font-weight:600;margin-bottom:6px;}
input[type="text"],input[type="number"],select{width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-bottom:14px;box-sizing:border-box;}
.upload-box{border:2px dashed #9ca3af;border-radius:10px;text-align:center;padding:26px;color:#6b7280;margin-bottom:18px;cursor:pointer;background:#fff;position:relative;}
.upload-box img{max-width:100%;max-height:200px;margin-top:10px;border-radius:10px;}
.buttons{display:flex;justify-content:flex-end;gap:10px;}
button{padding:10px 14px;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
.save{background:#2563eb;color:#fff;}
.cancel{background:#e5e7eb;}
.msg{padding:10px;border-radius:6px;margin-bottom:12px;}
.success{background:#ecfdf5;color:#065f46;border:1px solid #bbf7d0;}
.error{background:#fff1f2;color:#9f1239;border:1px solid #fecaca;}
@media(max-width:768px){body{flex-direction:column;}aside{width:100%;border-right:none;border-bottom:1px solid #e5e7eb;}main{padding:16px;}}
</style>
</head>
<body>
<aside>
  <?php include '../../sidebar/sidebar.php'; ?>
</aside>

<main>
  <section class="form-container">
    <h1>Tambah Menu</h1>

    <?php if ($info_msg): ?>
      <div class="msg success"><?= htmlspecialchars($info_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
      <div class="msg error"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <form id="formMenu" method="POST" enctype="multipart/form-data" action="">
      <div class="upload-box" onclick="document.getElementById('gambar').click();">
        <div id="uploadText">📤 Klik untuk memilih gambar</div>
        <div style="font-size:12px;color:#6b7280;">PNG / JPG / JPEG</div>
        <input type="file" name="gambar" id="gambar" accept="image/*" style="display:none;">
        <img id="preview" src="" alt="Preview" style="display:none;">
      </div>

      <label>Nama Menu</label>
      <input type="text" name="nama_menu" id="nama_menu" value="<?= isset($nama_menu) ? htmlspecialchars($nama_menu) : '' ?>">

      <label>Kategori</label>
      <select name="kategori" id="kategori">
        <option value="">-- Pilih Kategori --</option>
        <option value="makanan" <?= (isset($kategori) && $kategori==='makanan')?'selected':'' ?>>Makanan</option>
        <option value="minuman" <?= (isset($kategori) && $kategori==='minuman')?'selected':'' ?>>Minuman</option>
        <option value="cemilan" <?= (isset($kategori) && $kategori==='cemilan')?'selected':'' ?>>Cemilan</option>
      </select>

      <label>Harga</label>
      <input type="number" name="harga" id="harga" step="0.01" value="<?= isset($harga) ? htmlspecialchars($harga) : '' ?>">

      <label>Status Menu</label>
      <select name="status_menu" id="status_menu">
        <option value="">-- Pilih Status --</option>
        <option value="aktif" <?= (isset($status_menu) && strtolower($status_menu)==='aktif')?'selected':'' ?>>Aktif</option>
        <option value="nonaktif" <?= (isset($status_menu) && strtolower($status_menu)==='nonaktif')?'selected':'' ?>>Nonaktif</option>
      </select>

      <div class="buttons">
        <button type="reset" class="cancel">Batal</button>
        <button type="submit" class="save">Simpan Menu</button>
      </div>
    </form>
  </section>
</main>

<script>
// === preview gambar ===
const fileInput = document.getElementById('gambar');
const previewImg = document.getElementById('preview');
const uploadText = document.getElementById('uploadText');

fileInput.addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (ev) => {
      previewImg.src = ev.target.result;
      previewImg.style.display = 'block';
      uploadText.textContent = '✅ Gambar telah dipilih';
    };
    reader.readAsDataURL(file);
  } else {
    previewImg.style.display = 'none';
    uploadText.textContent = '📤 Klik untuk memilih gambar';
  }
});

// === validasi frontend sebelum submit ===
document.getElementById('formMenu').addEventListener('submit', (e) => {
  const nama = document.getElementById('nama_menu').value.trim();
  const kategori = document.getElementById('kategori').value.trim();
  const harga = document.getElementById('harga').value.trim();
  const status = document.getElementById('status_menu').value.trim();
  const gambar = document.getElementById('gambar').files.length;

  if (!nama || !kategori || !harga || !status || gambar === 0) {
    e.preventDefault();
    alert("⚠️ Semua field termasuk gambar wajib diisi sebelum menyimpan!");
  }
});
</script>
</body>
</html>
