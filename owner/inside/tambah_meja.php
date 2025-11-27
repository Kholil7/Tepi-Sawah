<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();
include '../../sidebar/sidebar.php';
require_once '../../database/connect.php';
require_once '../../assets/phpqrcode/qrlib.php';

$popup_status = '';
$popup_message = '';

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . "://" . $host;
}

function tambahMeja($conn, $nomor_meja) {
    $id_meja = 'MEJ' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
    $kode_unik = uniqid('MEJA_');
    $qrcode_dir = '../../assets/qrcode/';
    $qrcode_path = $qrcode_dir . $kode_unik . '.png';
    $status = 'kosong';

    $nomor_meja = trim($nomor_meja);
    if (empty($nomor_meja)) {
        return ['success' => false, 'msg' => 'Nomor meja tidak boleh kosong!'];
    }

    $stmt = $conn->prepare("SELECT nomor_meja FROM meja WHERE nomor_meja = ?");
    $stmt->bind_param("s", $nomor_meja);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'msg' => 'Nomor meja sudah ada!'];
    }
    $stmt->close();

    if (!file_exists($qrcode_dir)) {
        mkdir($qrcode_dir, 0755, true);
    }

    $base_url = getBaseUrl();
    $qr_data = $base_url . "/customer/inside/home.php?kode=" . urlencode($kode_unik);
    
    try {
        QRcode::png($qr_data, $qrcode_path, QR_ECLEVEL_L, 6, 2);
    } catch (Exception $e) {
        return ['success' => false, 'msg' => 'Gagal membuat QR Code: ' . $e->getMessage()];
    }

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

function getAllMeja($conn) {
    // Pengurutan numerik (tetap dipertahankan)
    $stmt = $conn->prepare("SELECT * FROM meja ORDER BY nomor_meja + 0 ASC"); 
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function getMejaById($conn, $id_meja) {
    $stmt = $conn->prepare("SELECT * FROM meja WHERE id_meja = ?");
    $stmt->bind_param("s", $id_meja);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_meja'])) {
    $nomor_meja = $_POST['nomor_meja'] ?? '';
    $res = tambahMeja($conn, $nomor_meja);
    
    $popup_status = $res['success'] ? 'success' : 'error';
    $popup_message = $res['msg'];
}

$meja = getAllMeja($conn);

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
<title>Kelola Meja Restoran Minimalis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="../../css/owner/tambah_meja.css" rel="stylesheet">
<style>
body {
    background-color: #ffffff;
}

.card-info {
    padding: 15px;
    border-radius: 4px;
    background-color: #ffffffff;
    border: 1px solid #e9ecef;
}
.card-info h4 {
    font-weight: 600;
}

.header-page {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 15px;
    margin-bottom: 20px;
}
.btn-tambah {
    background-color: #007bff;
    border-color: #007bff;
}

.table-card {
    border-radius: 4px;
    border: 1px solid #e9ecef;
}
.card-meja {
    border-radius: 4px;
    border: 1px solid #dee2e6;
    background-color: #ffffff;
}

.status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.8rem;
    font-weight: 600;
}
.status.kosong { background-color: #d1e7dd; color: #0f5132; }
.status.terisi { background-color: #cfe2ff; color: #084298; }
.status.menunggu_pembayaran { background-color: #fff3cd; color: #664d03; }
.status.selesai { background-color: #e2e6ea; color: #495057; }

.qr-container {
    max-width: 100px; 
    margin: 10px auto; 
    padding: 2px;
    border: 1px dashed #ced4da;
}
</style>
</head>
<body>

<div class="main-content">
  <div class="header-page">
    <div>
      <h5 class="fw-bold mb-1">Tambah Meja Baru</h5>
      <p class="text-muted small mb-0">Lihat Keseluruhan Meja.</p>
    </div>
    <button class="btn btn-primary text-white btn-sm" data-bs-toggle="modal" data-bs-target="#tambahMejaModal">
      <i class="bi bi-plus-lg me-1"></i> Tambah Meja
    </button>
  </div>

  <div class="row g-2 mb-4">
    <div class="col-md-3">
        <div class="card-info">
            <p class="text-muted small mb-1">Total Meja</p>
            <h4 class="mb-0"><?= $total ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-info">
            <p class="text-muted small mb-1">Kosong</p>
            <h4 class="text-success mb-0"><?= $kosong ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-info">
            <p class="text-muted small mb-1">Terisi</p>
            <h4 class="text-primary mb-0"><?= $terisi ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-info">
            <p class="text-muted small mb-1">Menunggu Bayar</p>
            <h4 class="text-warning mb-0"><?= $menunggu ?></h4>
        </div>
    </div>
  </div>

  <div class="table-card p-3">
    <h6 class="fw-semibold mb-3">Daftar Meja</h6>
    <div class="row g-3">
      <?php foreach ($meja as $m): ?>
      <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card card-meja p-3 h-100 text-center">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0 fw-bold">#<?= htmlspecialchars($m['nomor_meja'], ENT_QUOTES, 'UTF-8') ?></h6>
            <span class="status <?= htmlspecialchars($m['status_meja'], ENT_QUOTES, 'UTF-8') ?>">
              <?= ucfirst(str_replace('_',' ', htmlspecialchars($m['status_meja'], ENT_QUOTES, 'UTF-8'))) ?>
            </span>
          </div>
          
          <div class="qr-container">
              <img src="<?= htmlspecialchars($m['qrcode_url'], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid" alt="QR Code">
          </div>
          
          <div class="d-flex justify-content-center gap-2 mt-3">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#lihatMejaModal<?= htmlspecialchars($m['id_meja'], ENT_QUOTES, 'UTF-8') ?>">
              <i class="bi bi-eye"></i> Detail
            </button>
            <a href="<?= htmlspecialchars($m['qrcode_url'], ENT_QUOTES, 'UTF-8') ?>" download="qrcode_meja_<?= htmlspecialchars($m['nomor_meja'], ENT_QUOTES, 'UTF-8') ?>.png" class="btn btn-success btn-sm">
              <i class="bi bi-download"></i>
            </a>
          </div>
        </div>
      </div>

      <div class="modal fade" id="lihatMejaModal<?= htmlspecialchars($m['id_meja'], ENT_QUOTES, 'UTF-8') ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">Detail Meja #<?= htmlspecialchars($m['nomor_meja'], ENT_QUOTES, 'UTF-8') ?></h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              
              <h6 class="fw-bold text-secondary mb-3">Informasi Meja</h6>
              <div class="row mb-2">
                <div class="col-5 fw-semibold">Nomor Meja:</div>
                <div class="col-7">**<?= htmlspecialchars($m['nomor_meja'], ENT_QUOTES, 'UTF-8') ?>**</div>
              </div>
              <div class="row mb-2">
                <div class="col-5 fw-semibold">Status:</div>
                <div class="col-7">
                  <span class="badge bg-<?= $m['status_meja']=='kosong'?'success':($m['status_meja']=='terisi'?'primary':($m['status_meja']=='menunggu_pembayaran'?'warning':'secondary')) ?>">
                    <?= ucfirst(str_replace('_',' ', htmlspecialchars($m['status_meja'], ENT_QUOTES, 'UTF-8'))) ?>
                  </span>
                </div>
              </div>
              <div class="row mb-2">
                <div class="col-5 fw-semibold">ID Meja (Internal):</div>
                <div class="col-7 small text-truncate"><?= htmlspecialchars($m['id_meja'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="row mb-3">
                <div class="col-5 fw-semibold">Waktu Update:</div>
                <div class="col-7 small"><?= date('d-m-Y H:i', strtotime($m['last_update'])) ?></div>
              </div>
              
              <hr>

              <h6 class="fw-bold text-secondary mb-3">Akses Pelanggan</h6>
              <div class="row mb-3">
                <div class="col-12 text-center mb-3">
                  <h6 class="fw-semibold mb-2 text-primary">Kode Unik Meja (QR Data)</h6>
                  <p class="mb-1"><code><?= htmlspecialchars($m['kode_unik'], ENT_QUOTES, 'UTF-8') ?></code></p> 
                  <img src="<?= htmlspecialchars($m['qrcode_url'], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid border p-2" style="max-width: 150px;" alt="QR Code">
                </div>
                <div class="col-12">
                  <h6 class="fw-semibold mb-2">URL Akses Langsung:</h6>
                  <div class="alert alert-light border py-2 mb-0 small text-break">
                    <?php 
                    $base_url = getBaseUrl();
                    $url_akses = $base_url . "/customer/inside/home.php?kode=" . urlencode($m['kode_unik']);
                    ?>
                    <a href="<?= htmlspecialchars($url_akses, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-decoration-none text-primary">
                      <?= htmlspecialchars($url_akses, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="modal fade" id="tambahMejaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Tambah Meja Baru</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="nomor_meja" class="form-label fw-semibold">Nomor Meja</label>
            <input type="number" id="nomor_meja" name="nomor_meja" class="form-control" placeholder="Contoh: 5" required min="1" onkeydown="return event.key !== 'e' && event.key !== 'E' && event.key !== '+' && event.key !== '-'">
            <small class="text-muted">Masukkan nomor unik meja (Hanya angka).</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="tambah_meja" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="resultModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div id="resultModalHeader" class="modal-header text-white">
        <h5 id="resultModalTitle" class="modal-title">Status Operasi</h5>
      </div>
      <div class="modal-body text-center">
        <div class="d-flex align-items-center justify-content-center mb-3">
            <i id="resultModalIcon" class="me-3" style="font-size: 2.5rem;"></i>
            <h5 id="resultModalMessage" class="mb-0 fw-semibold"></h5>
        </div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-primary" onclick="window.location.href='tambah_meja.php'">
            <i class="bi bi-arrow-clockwise me-1"></i> OK & Muat Ulang
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const resultModalElement = document.getElementById('resultModal');
  const resultModal = new bootstrap.Modal(resultModalElement);
  const modalHeader = document.getElementById('resultModalHeader');
  const modalTitle = document.getElementById('resultModalTitle');
  const modalMessage = document.getElementById('resultModalMessage');
  const modalIcon = document.getElementById('resultModalIcon');

  <?php if (!empty($popup_status) && !empty($popup_message)): ?>
    const status = '<?= $popup_status ?>';
    const message = '<?= htmlspecialchars($popup_message, ENT_QUOTES, 'UTF-8') ?>';
    
    if (status === 'success') {
      modalHeader.className = 'modal-header bg-primary text-white';
      modalTitle.textContent = 'Sukses!';
      modalIcon.className = 'bi bi-check-circle-fill text-primary';
      modalIcon.style.color = '#007bff'; 
    } else {
      modalHeader.className = 'modal-header bg-danger text-white';
      modalTitle.textContent = 'Gagal!';
      modalIcon.className = 'bi bi-x-circle-fill text-danger';
      modalIcon.style.color = '#dc3545';
    }

    modalMessage.textContent = message;
    
    resultModal.show();
  <?php endif; ?>
});
</script>
</body>
</html>