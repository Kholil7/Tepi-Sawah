<?php
$koneksi = new mysqli("localhost", "root", "", "dbresto_app");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

if (isset($_GET['action']) && $_GET['action'] == 'detail' && isset($_GET['id_pesanan'])) {
    $id_pesanan = $_GET['id_pesanan'];
    $query = "
        SELECT d.*, m.nomor_meja, p.metode, p.jumlah_tagihan, p.waktu_pembayaran
        FROM detail_pesanan d
        JOIN pembayaran p ON p.id_pesanan = d.id_pesanan
        JOIN pesanan ps ON ps.id_pesanan = d.id_pesanan
        JOIN meja m ON m.id_meja = ps.id_meja
        WHERE d.id_pesanan = '$id_pesanan'
    ";
    $result = $koneksi->query($query);

    echo "<table class='table table-bordered'>
            <thead><tr><th>Menu</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr></thead><tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id_menu']}</td>
                <td>{$row['jumlah']}</td>
                <td>Rp " . number_format($row['harga_satuan'], 0, ',', '.') . "</td>
                <td>Rp " . number_format($row['subtotal'], 0, ',', '.') . "</td>
              </tr>";
    }
    echo "</tbody></table>";
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'print' && isset($_GET['id_pesanan'])) {
    $id_pesanan = $_GET['id_pesanan'];

    $transaksi = $koneksi->query("
        SELECT p.*, m.nomor_meja
        FROM pembayaran p
        JOIN pesanan ps ON p.id_pesanan = ps.id_pesanan
        JOIN meja m ON m.id_meja = ps.id_meja
        WHERE p.id_pesanan = '$id_pesanan'
    ")->fetch_assoc();

    $items = $koneksi->query("SELECT * FROM detail_pesanan WHERE id_pesanan='$id_pesanan'");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <title>Cetak Struk</title>
    <style>
    body { font-family: monospace; }
    .text-center { text-align: center; }
    hr { border: 1px dashed #000; }
    </style>
    </head>
    <body onload="window.print()">
    <div class="text-center">
      <h3>Resto App</h3>
      <small><?= date('d/m/Y H:i', strtotime($transaksi['waktu_pembayaran'])) ?></small>
      <hr>
    </div>
    <b>Meja:</b> <?= $transaksi['nomor_meja'] ?><br>
    <b>Metode:</b> <?= strtoupper($transaksi['metode']) ?><br><br>
    <table width="100%">
    <?php while($i = $items->fetch_assoc()): ?>
      <tr>
        <td><?= $i['id_menu'] ?> x<?= $i['jumlah'] ?></td>
        <td align="right">Rp <?= number_format($i['subtotal'],0,',','.') ?></td>
      </tr>
    <?php endwhile; ?>
    </table>
    <hr>
    <h4>Total: Rp <?= number_format($transaksi['jumlah_tagihan'], 0, ',', '.') ?></h4>
    <hr>
    <div class="text-center">Terima kasih telah berkunjung!</div>
    </body>
    </html>
    <?php
    exit;
}

$total_transaksi = $koneksi->query("SELECT COUNT(*) AS total FROM pembayaran WHERE status='sudah_bayar'")->fetch_assoc()['total'];

$total_pendapatan = $koneksi->query("SELECT SUM(jumlah_tagihan) AS total FROM pembayaran WHERE status='sudah_bayar'")->fetch_assoc()['total'];


$total_qris = $koneksi->query("SELECT COUNT(*) AS total FROM pembayaran WHERE metode='qris' AND status='sudah_bayar'")->fetch_assoc()['total'];
$total_cash = $koneksi->query("SELECT COUNT(*) AS total FROM pembayaran WHERE metode='cash' AND status='sudah_bayar'")->fetch_assoc()['total'];


$query = "
    SELECT p.id_pembayaran, p.id_pesanan, p.metode, p.jumlah_tagihan, p.waktu_pembayaran, m.nomor_meja
    FROM pembayaran p
    JOIN pesanan ps ON p.id_pesanan = ps.id_pesanan
    JOIN meja m ON m.id_meja = ps.id_meja
    WHERE p.status='sudah_bayar'
    ORDER BY p.waktu_pembayaran DESC
";
$result = $koneksi->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Riwayat Transaksi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; font-family: 'Poppins', sans-serif; }
.card-summary { border-radius: 15px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); }
.badge-qris { background-color: #ffcc00; color: #000; }
.badge-cash { background-color: #007bff; }
.table th, .table td { vertical-align: middle; }
</style>
</head>
<body class="p-4">
<div class="container">
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card card-summary p-3 text-center">
        <h5>Total Transaksi</h5>
        <h3><?= $total_transaksi ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-summary p-3 text-center">
        <h5>Total Pendapatan</h5>
        <h3>Rp <?= number_format($total_pendapatan ?? 0, 0, ',', '.') ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-summary p-3 text-center">
        <h5>Pembayaran QRIS</h5>
        <h3><?= $total_qris ?> transaksi</h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-summary p-3 text-center">
        <h5>Pembayaran Cash</h5>
        <h3><?= $total_cash ?> transaksi</h3>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="mb-3">Riwayat Transaksi</h5>
      
      <div class="row mb-3">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-white"><i>🔍</i></span>
            <input type="text" class="form-control" id="searchInput" placeholder="Cari menu atau nomor meja...">
          </div>
        </div>
        <div class="col-md-4">
          <select class="form-select" id="filterMeja">
            <option value="">Semua Meja</option>
            <?php
            $mejas = $koneksi->query("SELECT DISTINCT m.nomor_meja FROM meja m JOIN pesanan ps ON m.id_meja = ps.id_meja JOIN pembayaran p ON ps.id_pesanan = p.id_pesanan WHERE p.status='sudah_bayar' ORDER BY m.nomor_meja");
            while($meja = $mejas->fetch_assoc()):
            ?>
              <option value="<?= $meja['nomor_meja'] ?>"><?= $meja['nomor_meja'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-4">
          <select class="form-select" id="filterMetode">
            <option value="">Semua Metode</option>
            <option value="qris">QRIS</option>
            <option value="cash">Cash</option>
          </select>
        </div>
      </div>
      
      <table class="table table-hover align-middle">
        <thead>
          <tr class="table-secondary">
            <th>Waktu</th>
            <th>Meja</th>
            <th>Item Pesanan</th>
            <th>Metode</th>
            <th>Total</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $items = $koneksi->query("SELECT id_menu, jumlah FROM detail_pesanan WHERE id_pesanan='{$row['id_pesanan']}'");
            $first = $items->fetch_assoc();
            $jumlah_item = $koneksi->query("SELECT COUNT(*) as total FROM detail_pesanan WHERE id_pesanan='{$row['id_pesanan']}'")->fetch_assoc()['total'];
          ?>
          <tr>
            <td><?= date('H:i', strtotime($row['waktu_pembayaran'])) ?><br><small><?= date('d/m/Y', strtotime($row['waktu_pembayaran'])) ?></small></td>
            <td><span class="badge bg-warning text-dark"><?= $row['nomor_meja'] ?></span></td>
            <td>
              <?= $first['id_menu'] ?> x<?= $first['jumlah'] ?>
              <?php if ($jumlah_item > 1): ?>
                <div class="text-muted small">+<?= $jumlah_item - 1 ?> item lainnya</div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['metode'] == 'qris'): ?>
                <span class="badge badge-qris">QRIS</span>
              <?php else: ?>
                <span class="badge badge-cash">Cash</span>
              <?php endif; ?>
            </td>
            <td><strong>Rp <?= number_format($row['jumlah_tagihan'], 0, ',', '.') ?></strong></td>
            <td>
              <button class="btn btn-sm btn-primary" onclick="showDetail('<?= $row['id_pesanan'] ?>')">🔍</button>
              <button class="btn btn-sm btn-success" onclick="printStruk('<?= $row['id_pesanan'] ?>')">🖨</button>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Detail Transaksi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailContent">Memuat data...</div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showDetail(idPesanan) {
  fetch('?action=detail&id_pesanan=' + idPesanan)
    .then(res => res.text())
    .then(html => {
      document.getElementById('detailContent').innerHTML = html;
      new bootstrap.Modal(document.getElementById('detailModal')).show();
    });
}

function printStruk(idPesanan) {
  window.open('?action=print&id_pesanan=' + idPesanan, '_blank');
}

document.getElementById('searchInput').addEventListener('keyup', filterTable);
document.getElementById('filterMeja').addEventListener('change', filterTable);
document.getElementById('filterMetode').addEventListener('change', filterTable);

function filterTable() {
  const searchValue = document.getElementById('searchInput').value.toLowerCase();
  const mejaFilter = document.getElementById('filterMeja').value.toLowerCase();
  const metodeFilter = document.getElementById('filterMetode').value.toLowerCase();
  
  const rows = document.querySelectorAll('tbody tr');
  
  rows.forEach(row => {
    const menu = row.cells[2].textContent.toLowerCase();
    const meja = row.cells[1].textContent.toLowerCase();
    const metode = row.querySelector('td:nth-child(4) .badge').textContent.toLowerCase();
    
    const matchSearch = menu.includes(searchValue) || meja.includes(searchValue);
    const matchMeja = mejaFilter === '' || meja.includes(mejaFilter);
    const matchMetode = metodeFilter === '' || metode.includes(metodeFilter);
    
    if (matchSearch && matchMeja && matchMetode) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>
</body>
</html>