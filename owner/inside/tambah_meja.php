<?php
include '../../sidebar/sidebar.php';
require_once '../../database/connect.php';
require_once '../../assets/phpqrcode/qrlib.php'; // QRCode tanpa Composer

// Fungsi tambah meja dengan QR
function tambahMeja($conn, $nomor_meja) {
    $kode_unik = uniqid('MEJA_');
    $qrcode_dir = '../../assets/qrcode/';
    $qrcode_path = $qrcode_dir . $kode_unik . '.png';
    $status = 'kosong';

    // Cek duplikat nomor meja
    $cek = mysqli_query($conn, "SELECT * FROM meja WHERE nomor_meja = '$nomor_meja'");
    if (mysqli_num_rows($cek) > 0) {
        return ['msg' => 'Nomor meja sudah ada!'];
    }

    // Generate QR Code
    if (!file_exists($qrcode_dir)) {
        mkdir($qrcode_dir, 0777, true);
    }

    $qr_data = "https://example.com/restoran/order.php?meja=" . urlencode($kode_unik);
    QRcode::png($qr_data, $qrcode_path, QR_ECLEVEL_L, 6, 2);

    // Simpan ke database
    $insert = mysqli_query($conn, "INSERT INTO meja (nomor_meja, kode_unik, status_meja, qrcode_url, last_update)
                                   VALUES ('$nomor_meja', '$kode_unik', '$status', '$qrcode_path', NOW())");

    if ($insert) {
        return ['msg' => 'Meja berhasil ditambahkan beserta QR Code-nya!'];
    } else {
        return ['msg' => 'Gagal menambahkan meja: ' . mysqli_error($conn)];
    }
}

// Fungsi edit meja
function editMeja($conn, $id_meja, $nomor_meja, $status_meja) {
    $update = mysqli_query($conn, "UPDATE meja 
                                   SET nomor_meja='$nomor_meja', status_meja='$status_meja', last_update=NOW() 
                                   WHERE id_meja='$id_meja'");
    if ($update) return ['msg' => 'Data meja berhasil diperbarui!'];
    else return ['msg' => 'Gagal memperbarui data: ' . mysqli_error($conn)];
}

// Fungsi hapus meja
function hapusMeja($conn, $id_meja) {
    $hapus = mysqli_query($conn, "DELETE FROM meja WHERE id_meja='$id_meja'");
    if ($hapus) return ['msg' => 'Meja berhasil dihapus!'];
    else return ['msg' => 'Gagal menghapus meja: ' . mysqli_error($conn)];
}

// Ambil semua data meja
function getAllMeja($conn) {
    $result = mysqli_query($conn, "SELECT * FROM meja ORDER BY id_meja ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    return $data;
}

// Aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_meja'])) {
    $nomor_meja = trim($_POST['nomor_meja']);
    $res = tambahMeja($conn, $nomor_meja);
    echo "<script>alert('{$res['msg']}'); window.location.href='tambah_meja.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_meja'])) {
    $id_meja = $_POST['id_meja'];
    $nomor_meja = trim($_POST['nomor_meja']);
    $status_meja = $_POST['status_meja'];
    $res = editMeja($conn, $id_meja, $nomor_meja, $status_meja);
    echo "<script>alert('{$res['msg']}'); window.location.href='tambah_meja.php';</script>";
    exit;
}

if (isset($_GET['hapus'])) {
    $id_meja = $_GET['hapus'];
    $res = hapusMeja($conn, $id_meja);
    echo "<script>alert('{$res['msg']}'); window.location.href='tambah_meja.php';</script>";
    exit;
}

$meja = getAllMeja($conn);

// Statistik
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
            <h6 class="mb-0"><i class="bi bi-grid-3x3-gap me-1"></i> <?= htmlspecialchars($m['nomor_meja']) ?></h6>
            <span class="status <?= htmlspecialchars($m['status_meja']) ?>">
              <?= ucfirst(str_replace('_',' ', $m['status_meja'])) ?>
            </span>
          </div>
          <img src="<?= htmlspecialchars($m['qrcode_url']) ?>" class="img-fluid rounded mt-2" alt="QR Code">
          <div class="d-flex justify-content-between mt-3">
            <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#editMejaModal<?= $m['id_meja'] ?>">
              <i class="bi bi-pencil-square me-1"></i>Edit
            </button>
            <a href="tambah_meja.php?hapus=<?= $m['id_meja'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus meja ini?')">
              <i class="bi bi-trash"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Modal Edit -->
      <div class="modal fade" id="editMejaModal<?= $m['id_meja'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="POST" action="">
              <div class="modal-header">
                <h5 class="modal-title">Edit Meja <?= htmlspecialchars($m['nomor_meja']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="id_meja" value="<?= $m['id_meja'] ?>">
                <div class="mb-3">
                  <label class="form-label">Nomor Meja</label>
                  <input type="text" name="nomor_meja" class="form-control" value="<?= htmlspecialchars($m['nomor_meja']) ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Status Meja</label>
                  <select name="status_meja" class="form-select">
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
            <input type="text" name="nomor_meja" class="form-control" placeholder="Contoh: Meja 5" required>
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
