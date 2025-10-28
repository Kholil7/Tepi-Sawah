<?php
// tambah_menu.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../database/connect.php'; // pastikan koneksi benar

// fungsi bantu untuk clean input sederhana
function clean($v){
    return trim(htmlspecialchars($v ?? ''));
}

$info_msg = '';
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_menu   = clean($_POST['nama_menu'] ?? '');
    $kategori    = clean($_POST['kategori'] ?? '');
    $harga_raw   = $_POST['harga'] ?? '';
    $status_menu = clean($_POST['status_menu'] ?? '');

    // validasi sederhana
    if ($nama_menu === '' || $kategori === '' || $harga_raw === '' || $status_menu === '') {
        $error_msg = "Semua field harus diisi.";
    } else {
        // pastikan harga numeric
        if (!is_numeric($harga_raw)) {
            $error_msg = "Harga harus berupa angka.";
        } else {
            $harga = (float) $harga_raw;

            // === handling upload gambar ===
            $uploaded_filename = null; // akan disimpan ke DB jika berhasil upload

            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
                // ada request upload file; periksa error upload
                if ($_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['gambar']['tmp_name'];
                    $orig_name = $_FILES['gambar']['name'];

                    // cek apakah benar file diupload
                    if (is_uploaded_file($tmp_name)) {
                        // buat folder upload (abs path)
                        $uploadDir = __DIR__ . "/../../assets/uploads/";
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        // buat nama file unik untuk menghindari bentrok
                        $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
                        $safeBase = pathinfo($orig_name, PATHINFO_FILENAME);
                        $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', $safeBase);
                        $uploaded_filename = time() . '_' . $safeBase . '.' . $ext;
                        $targetPath = $uploadDir . $uploaded_filename;

                        if (!move_uploaded_file($tmp_name, $targetPath)) {
                            $error_msg = "Gagal memindahkan file upload ke folder tujuan.";
                            $uploaded_filename = null;
                        }
                    } else {
                        $error_msg = "File upload tidak valid.";
                    }
                } else {
                    // tangani error upload lain
                    $error_map = [
                        UPLOAD_ERR_INI_SIZE => 'File terlalu besar (INi).',
                        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (form).',
                        UPLOAD_ERR_PARTIAL => 'Upload terpotong.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporer tidak ada.',
                        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
                        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi.'
                    ];
                    $code = $_FILES['gambar']['error'];
                    $error_msg = $error_map[$code] ?? "Error upload file: kode $code";
                }
            }

            // jika tidak ada error sampai sini -> simpan ke DB
            if ($error_msg === '') {
                // pastikan status_menu sesuai ENUM di DB; sesuaikan kalau DB-mu 'Aktif'/'Nonaktif'
                // di contoh ini saya pakai 'aktif' dan 'nonaktif'
                $allowed_status = ['aktif', 'nonaktif'];
                if (!in_array(strtolower($status_menu), $allowed_status)) {
                    $error_msg = "Nilai status tidak valid.";
                } else {
                    // simpan: kolom nama_menu, kategori, harga, status_menu, gambar
                    $sql = "INSERT INTO menu (nama_menu, kategori, harga, status_menu, gambar)
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        $error_msg = "Gagal menyiapkan query: " . $conn->error;
                    } else {
                        // tipe: s (nama_menu), s (kategori), d (harga), s (status_menu), s (gambar)
                        // jika $uploaded_filename null, simpan NULL ke DB => bind sebagai string kosong atau NULL
                        $gambar_for_db = $uploaded_filename ?? null;
                        // bind_param tidak menerima null langsung untuk tipe 's', kita bisa set ke empty string or handle with query using ? -> use null by adjusting param
                        // simplest: send empty string if null
                        $gambar_bind = $gambar_for_db ?? '';
                        $stmt->bind_param("ssdss", $nama_menu, $kategori, $harga, $status_menu, $gambar_bind);

                        if ($stmt->execute()) {
                            $info_msg = "Menu berhasil disimpan.";
                            // reset fields jika perlu
                            $nama_menu = $kategori = $harga = $status_menu = '';
                        } else {
                            $error_msg = "Gagal simpan ke database: " . $stmt->error;
                            // rollback file jika sudah terupload tapi DB gagal? opsional:
                            if ($uploaded_filename) {
                                @unlink($uploadDir . $uploaded_filename);
                            }
                        }
                        $stmt->close();
                    }
                }
            } // end if no error
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
/* gaya singkat, sama seperti sebelumnya */
body { margin:0; font-family:Segoe UI, Tahoma, sans-serif; background:#f9fafb; color:#333; display:flex; min-height:100vh; }
aside { width:250px; background:transparent; flex-shrink:0; border-right:1px solid #e5e7eb; }
main { flex-grow:1; padding:30px; display:flex; justify-content:center; align-items:flex-start; }
.form-container { background:#fff; padding:28px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); width:100%; max-width:720px; }
h1 { text-align:center; color:#1e3a8a; margin-bottom:18px; }
label{ display:block; font-weight:600; margin-bottom:6px; }
input[type="text"], input[type="number"], select { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; margin-bottom:14px; box-sizing:border-box;}
.upload-box { border:2px dashed #9ca3af; border-radius:10px; text-align:center; padding:26px; color:#6b7280; margin-bottom:18px; cursor:pointer; background:#fff;}
.buttons{ display:flex; justify-content:flex-end; gap:10px; }
button{ padding:10px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:600;}
.save{ background:#2563eb; color:#fff; } .cancel{ background:#e5e7eb; }
.msg{ padding:10px; border-radius:6px; margin-bottom:12px;}
.success{ background:#ecfdf5; color:#065f46; border:1px solid #bbf7d0;}
.error{ background:#fff1f2; color:#9f1239; border:1px solid #fecaca;}
@media (max-width:768px){ body{flex-direction:column;} aside{width:100%;border-right:none;border-bottom:1px solid #e5e7eb;} main{padding:16px;} }
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

        <form method="POST" enctype="multipart/form-data" action="">
            <div class="upload-box" onclick="document.getElementById('gambar').click();">
                <div>📤 Klik untuk memilih gambar (maks 1 file)</div>
                <div style="font-size:12px;color:#6b7280;">PNG / JPG / JPEG</div>
                <input type="file" name="gambar" id="gambar" accept="image/*" style="display:none;">
            </div>

            <label>Nama Menu</label>
            <input type="text" name="nama_menu" value="<?= isset($nama_menu) ? htmlspecialchars($nama_menu) : '' ?>" required>

            <label>Kategori</label>
            <select name="kategori" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="makanan" <?= (isset($kategori) && $kategori==='makanan')?'selected':'' ?>>Makanan</option>
                <option value="minuman" <?= (isset($kategori) && $kategori==='minuman')?'selected':'' ?>>Minuman</option>
                <option value="cemilan" <?= (isset($kategori) && $kategori==='cemilan')?'selected':'' ?>>Cemilan</option>
            </select>

            <label>Harga</label>
            <input type="number" name="harga" step="0.01" value="<?= isset($harga) ? htmlspecialchars($harga) : '' ?>" required>

            <label>Status Menu</label>
            <select name="status_menu" required>
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
</body>
</html>
