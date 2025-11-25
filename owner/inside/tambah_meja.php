<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();
include '../../sidebar/sidebar.php';
require_once '../../database/connect.php';
require_once '../../assets/phpqrcode/qrlib.php';

// Fungsi untuk mendapatkan Base URL secara otomatis
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . "://" . $host;
}

function tambahMeja($conn, $nomor_meja) {
    // Generate ID dan kode unik
    $id_meja   = 'MEJ' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
    $kode_unik = uniqid('MEJA_');
    $qrcode_dir = '../../assets/qrcode/';
    $qrcode_path = $qrcode_dir . $kode_unik . '.png';
    $status = 'kosong';

    // Validasi input
    $nomor_meja = trim($nomor_meja);
    if (empty($nomor_meja)) {
        return ['success' => false, 'msg' => 'Nomor meja tidak boleh kosong!'];
    }

    // Cek apakah nomor meja sudah ada (menggunakan prepared statement)
    $stmt = $conn->prepare("SELECT nomor_meja FROM meja WHERE nomor_meja = ?");
    $stmt->bind_param("s", $nomor_meja);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'msg' => 'Nomor meja sudah ada!'];
    }
    $stmt->close();

    // Buat direktori QR code jika belum ada
    if (!file_exists($qrcode_dir)) {
        mkdir($qrcode_dir, 0755, true);
    }

    // Generate URL dinamis berdasarkan domain yang digunakan
    $base_url = getBaseUrl();
    $qr_data = $base_url . "/restoran/order.php?meja=" . urlencode($kode_unik);
    
    // Generate QR Code
    try {
        QRcode::png($qr_data, $qrcode_path, QR_ECLEVEL_L, 6, 2);
    } catch (Exception $e) {
        return ['success' => false, 'msg' => 'Gagal membuat QR Code: ' . $e->getMessage()];
    }

    // Insert data menggunakan prepared statement
    $stmt = $conn->prepare("INSERT INTO meja (id_meja, nomor_meja, kode_unik, status_meja, qrcode_url, last_update) 
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $id_meja, $nomor_meja, $kode_unik, $status, $qrcode_path);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'msg' => 'Meja berhasil ditambahkan beserta QR Code-nya!'];
    } else {
        $stmt->close();
        return ['success' => false, 'msg' => 'Gagal menambahkan meja: ' . $conn->error];
    }
}

function editMeja($conn, $id_meja, $nomor_meja, $status_meja) {
    // Validasi input
    $nomor_meja = trim($nomor_meja);
    if (empty($nomor_meja)) {
        return ['success' => false, 'msg' => 'Nomor meja tidak boleh kosong!'];
    }

    // Validasi status meja
    $valid_status = ['kosong', 'terisi', 'menunggu_pembayaran', 'selesai'];
    if (!in_array($status_meja, $valid_status)) {
        return ['success' => false, 'msg' => 'Status meja tidak valid!'];
    }

    // Cek apakah nomor meja sudah digunakan meja lain
    $stmt = $conn->prepare("SELECT id_meja FROM meja WHERE nomor_meja = ? AND id_meja != ?");
    $stmt->bind_param("ss", $nomor_meja, $id_meja);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'msg' => 'Nomor meja sudah digunakan meja lain!'];
    }
    $stmt->close();

    // Update data menggunakan prepared statement
    $stmt = $conn->prepare("UPDATE meja SET nomor_meja = ?, status_meja = ?, last_update = NOW() WHERE id_meja = ?");
    $stmt->bind_param("sss", $nomor_meja, $status_meja, $id_meja);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'msg' => 'Data meja berhasil diperbarui!'];
    } else {
        $stmt->close();
        return ['success' => false, 'msg' => 'Gagal memperbarui data: ' . $conn->error];
    }
}

function hapusMeja($conn, $id_meja) {
    // Ambil path QR code sebelum dihapus
    $stmt = $conn->prepare("SELECT qrcode_url FROM meja WHERE id_meja = ?");
    $stmt->bind_param("s", $id_meja);
    $stmt->execute();
    $result = $stmt->get_result();
    $meja = $result->fetch_assoc();
    $stmt->close();

    if ($meja && file_exists($meja['qrcode_url'])) {
        unlink($meja['qrcode_url']); // Hapus file QR code
    }

    // Hapus data dari database
    $stmt = $conn->prepare("DELETE FROM meja WHERE id_meja = ?");
    $stmt->bind_param("s", $id_meja);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'msg' => 'Meja berhasil dihapus!'];
    } else {
        $stmt->close();
        return ['success' => false, 'msg' => 'Gagal menghapus meja: ' . $conn->error];
    }
}

function getAllMeja($conn) {
    $stmt = $conn->prepare("SELECT * FROM meja ORDER BY nomor_meja ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// Proses Tambah Meja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_meja'])) {
    // Validasi CSRF token (opsional, tapi sangat direkomendasikan)
    $nomor_meja = $_POST['nomor_meja'] ?? '';
    $res = tambahMeja($conn, $nomor_meja);
    
    $msg = htmlspecialchars($res['msg'], ENT_QUOTES, 'UTF-8');
    echo "<script>alert('$msg'); window.location.href='tambah_meja.php';</script>";
    exit;
}

// Proses Edit Meja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_meja'])) {
    $id_meja = $_POST['id_meja'] ?? '';
    $status_meja = $_POST['status_meja'] ?? '';
    
    $res = editMeja($conn, $id_meja, $status_meja);
    
    $msg = htmlspecialchars($res['msg'], ENT_QUOTES, 'UTF-8');
    echo "<script>alert('$msg'); window.location.href='tambah_meja.php';</script>";
    exit;
}

// Proses Hapus Meja
if (isset($_GET['hapus'])) {
    $id_meja = $_GET['hapus'] ?? '';
    
    // Validasi format ID meja
    if (preg_match('/^MEJ[A-Z0-9]{8}$/', $id_meja)) {
        $res = hapusMeja($conn, $id_meja);
        $msg = htmlspecialchars($res['msg'], ENT_QUOTES, 'UTF-8');
    } else {
        $msg = 'ID meja tidak valid!';
    }
    
    echo "<script>alert('$msg'); window.location.href='tambah_meja.php';</script>";
    exit;
}

// Ambil semua data meja
$meja = getAllMeja($conn);

// Hitung statistik
$total = count($meja);
$kosong = count(array_filter($meja, fn($m) => $m['status_meja'] === 'kosong'));
$terisi = count(array_filter($meja, fn($m) => $m['status_meja'] === 'terisi'));
$menunggu = count(array_filter($meja, fn($m) => $m['status_meja'] === 'menunggu_pembayaran'));
$selesai = count(array_filter($meja, fn($m) => $m['status_meja'] === 'selesai'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Meja</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="../../css/owner/tambah_meja.css" rel="stylesheet">
</head>
<body>

<div class="main-content">
  <div class="header-page mb-4">
    <div>
      <h5 class="fw-semibold mb-1">Input & Kelola Meja</h5>
      <p class="text-muted small mb-0">Tambah dan atur meja restoran</p>
    </div>
    <button class="btn btn-tambah text-white" data-bs-toggle="modal" data-bs-target="#tambahMejaModal">
      <i class="bi bi-plus-lg me-1"></i> Tambah Meja
    </button>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card-info"><p class="text-muted mb-1">Total Meja</p><h4><?= $total ?></h4></div></div>
    <div class="col-md-3"><div class="card-info"><p class="text-muted mb-1">Kosong</p><h4 class="text-success"><?= $kosong ?></h4></div></div>
    <div class="col-md-3"><div class="card-info"><p class="text-muted mb-1">Terisi</p><h4 class="text-primary"><?= $terisi ?></h4></div></div>
    <div class="col-md-3"><div class="card-info"><p class="text-muted mb-1">Menunggu Bayar</p><h4 class="text-warning"><?= $menunggu ?></h4></div></div>
  </div>

  <div class="table-card">
    <h6 class="fw-semibold mb-3">Daftar Meja</h6>
    <div class="row g-3">
      <?php foreach ($meja as $m): ?>
      <div class="col-md-3">
        <div class="card shadow-sm border rounded p-3 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><i class="bi bi-grid-3x3-gap me-1"></i> <?= htmlspecialchars($m['nomor_meja'], ENT_QUOTES, 'UTF-8') ?></h6>
            <span class="status <?= htmlspecialchars($m['status_meja'], ENT_QUOTES, 'UTF-8') ?>">
              <?= ucfirst(str_replace('_',' ', htmlspecialchars($m['status_meja'], ENT_QUOTES, 'UTF-8'))) ?>
            </span>
          </div>
          <img src="<?= htmlspecialchars($m['qrcode_url'], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded mt-2" alt="QR Code">
          <div class="d-flex justify-content-between mt-3">
            <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#editMejaModal<?= htmlspecialchars($m['id_meja'], ENT_QUOTES, 'UTF-8') ?>">
              <i class="bi bi-pencil-square me-1"></i>Edit
            </button>
            <a href="tambah_meja.php?hapus=<?= urlencode($m['id_meja']) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus meja ini?')">
              <i class="bi bi-trash"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Modal Edit -->
      <div class="modal fade" id="editMejaModal<?= htmlspecialchars($m['id_meja'], ENT_QUOTES, 'UTF-8') ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="POST" action="">
              <div class="modal-header">
                <h5 class="modal-title">Edit Meja <?= htmlspecialchars($m['nomor_meja'], ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="id_meja" value="<?= htmlspecialchars($m['id_meja'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                  <label class="form-label">Nomor Meja</label>
                  <input type="text" class="form-control" value="<?= htmlspecialchars($m['nomor_meja'], ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
                </div>
                <div class="mb-3">
                  <label class="form-label">Status Meja</label>
                  <select name="status_meja" class="form-select" required>
                    <option value="kosong" <?= $m['status_meja']=='kosong'?'selected':'' ?>>Kosong</option>
                    <option value="terisi" <?= $m['status_meja']=='terisi'?'selected':'' ?>>Terisi</option>
                    <option value="menunggu_pembayaran" <?= $m['status_meja']=='menunggu_pembayaran'?'selected':'' ?>>Menunggu Pembayaran</option>
                    <option value="selesai" <?= $m['status_meja']=='selesai'?'selected':'' ?>>Selesai</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_meja" class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="tambahMejaModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Meja Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nomor Meja</label>
            <input type="number" name="nomor_meja" class="form-control" placeholder="Contoh: 5" required min="1">
            <small class="text-muted">Hanya angka yang diperbolehkan</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="tambah_meja" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>