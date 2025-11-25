<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();
if (isset($_GET['action']) && $_GET['action'] == 'detail' && isset($_GET['id_pesanan'])) {
    $koneksi = new mysqli("localhost", "root", "", "dbresto_app");
    if ($koneksi->connect_error) { die("Koneksi gagal: " . $koneksi->connect_error); }
    
    $id_pesanan = $_GET['id_pesanan'];
    $query = "
        SELECT d.*, mn.nama_menu, m.nomor_meja, p.metode, p.waktu_pembayaran
        FROM detail_pesanan d
        JOIN menu mn ON mn.id_menu = d.id_menu
        JOIN pembayaran p ON p.id_pesanan = d.id_pesanan
        JOIN pesanan ps ON ps.id_pesanan = d.id_pesanan
        JOIN meja m ON m.id_meja = ps.id_meja
        WHERE d.id_pesanan = '$id_pesanan'
    ";
    $result = $koneksi->query($query);

    $first_row = $result->fetch_assoc();
    $result->data_seek(0);

    echo "<div class='mb-3'>
            <p><strong>Meja:</strong> {$first_row['nomor_meja']}</p>
            <p><strong>Metode Pembayaran:</strong> " . strtoupper($first_row['metode']) . "</p>
            <p><strong>Waktu:</strong> " . date('d/m/Y H:i', strtotime($first_row['waktu_pembayaran'])) . "</p>
          </div>";

    echo "<table class='table table-bordered'>
            <thead>
                <tr><th>Menu</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr>
            </thead>
            <tbody>";
    
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $total += $row['subtotal'];
        echo "<tr>
                <td>{$row['nama_menu']}</td>
                <td>{$row['jumlah']}</td>
                <td>Rp " . number_format($row['harga_satuan'], 0, ',', '.') . "</td>
                <td>Rp " . number_format($row['subtotal'], 0, ',', '.') . "</td>
              </tr>";
    }
    echo "</tbody>
          <tfoot>
            <tr class='table-secondary'>
                <td colspan='3' class='text-end'><strong>Total:</strong></td>
                <td><strong>Rp " . number_format($total, 0, ',', '.') . "</strong></td>
            </tr>
          </tfoot>
          </table>";
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'print' && isset($_GET['id_pesanan'])) {
    $koneksi = new mysqli("localhost", "root", "", "dbresto_app");
    if ($koneksi->connect_error) { die("Koneksi gagal: " . $koneksi->connect_error); }
    
    $id_pesanan = $_GET['id_pesanan'];

    $transaksi = $koneksi->query("
        SELECT p.*, m.nomor_meja
        FROM pembayaran p
        JOIN pesanan ps ON p.id_pesanan = ps.id_pesanan
        JOIN meja m ON m.id_meja = ps.id_meja
        WHERE p.id_pesanan = '$id_pesanan'
    ")->fetch_assoc();

    $items = $koneksi->query("
        SELECT d.*, mn.nama_menu 
        FROM detail_pesanan d
        JOIN menu mn ON mn.id_menu = d.id_menu
        WHERE d.id_pesanan='$id_pesanan'
    ");
    
    $total_print = $koneksi->query("
        SELECT SUM(subtotal) AS total FROM detail_pesanan WHERE id_pesanan='$id_pesanan'
    ")->fetch_assoc()['total'];
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body { 
        font-family: 'Courier New', monospace;
        padding: 20px;
        max-width: 400px;
        margin: 0 auto;
    }
    .text-center { 
        text-align: center; 
    }
    hr { 
        border: none;
        border-top: 1px dashed #000;
        margin: 10px 0;
    }
    h3 {
        margin: 10px 0;
        font-size: 20px;
    }
    h4 {
        margin: 10px 0;
        font-size: 18px;
    }
    small {
        font-size: 12px;
    }
    table {
        width: 100%;
        margin: 10px 0;
    }
    td {
        padding: 5px 0;
        font-size: 14px;
    }
    .print-button {
        text-align: center;
        margin: 20px 0;
    }
    .btn-print {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }
    .btn-print:hover {
        background-color: #0056b3;
    }
    @media print {
        body {
            padding: 0;
        }
        .print-button {
            display: none;
        }
    }
    </style>
    </head>
    <body>
    <div class="text-center">
      <h3>Resto App</h3>
      <small><?= date('d/m/Y H:i', strtotime($transaksi['waktu_pembayaran'])) ?></small>
      <hr>
    </div>

    <b>Meja:</b> <?= $transaksi['nomor_meja'] ?><br>
    <b>Metode:</b> <?= strtoupper($transaksi['metode']) ?><br><br>

    <table>
    <?php while($i = $items->fetch_assoc()): ?>
      <tr>
        <td><?= $i['nama_menu'] ?> x<?= $i['jumlah'] ?></td>
        <td align="right">Rp <?= number_format($i['subtotal'],0,',','.') ?></td>
      </tr>
    <?php endwhile; ?>
    </table>

    <hr>
    <h4>Total: Rp <?= number_format($total_print, 0, ',', '.') ?></h4>
    <hr>
    <div class="text-center">Terima kasih telah berkunjung!</div>

    <div class="print-button">
        <button class="btn-print" onclick="window.print()">Cetak Struk</button>
    </div>
    </body>
    </html>
    <?php
    exit;
}

include '../../sidebar/sidebar_kasir.php';

$koneksi = new mysqli("localhost", "root", "", "dbresto_app");
if ($koneksi->connect_error) { die("Koneksi gagal: " . $koneksi->connect_error); }

$total_transaksi = $koneksi->query("
    SELECT COUNT(*) AS total 
    FROM pembayaran 
    WHERE status='sudah_bayar'
")->fetch_assoc()['total'];

$total_pendapatan = $koneksi->query("
    SELECT SUM(subtotal) AS total
    FROM detail_pesanan d
    JOIN pembayaran p ON p.id_pesanan = d.id_pesanan
    WHERE p.status='sudah_bayar'
")->fetch_assoc()['total'];

$total_qris = $koneksi->query("
    SELECT COUNT(*) AS total 
    FROM pembayaran 
    WHERE metode='qris' AND status='sudah_bayar'
")->fetch_assoc()['total'];

$total_cash = $koneksi->query("
    SELECT COUNT(*) AS total 
    FROM pembayaran 
    WHERE metode='cash' AND status='sudah_bayar'
")->fetch_assoc()['total'];

$query = "
    SELECT 
        p.id_pembayaran,
        p.id_pesanan,
        p.metode,
        p.waktu_pembayaran,
        m.nomor_meja,
        (SELECT SUM(subtotal) FROM detail_pesanan WHERE id_pesanan = p.id_pesanan) AS total_tagihan
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Transaksi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
body { 
    background: #f8f9fa; 
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
}
.content { 
    margin-left: 260px;
    padding: 25px;
    min-height: 100vh;
}
.card-summary { 
    border-radius: 15px; 
    box-shadow: 0 3px 8px rgba(0,0,0,0.05); 
}
.badge-qris { 
    background-color: #ffcc00; 
    color: #000; 
}
.badge-cash { 
    background-color: #007bff; 
}
</style>
</head>

<body>

<div class="content">

    <div class="container-fluid">
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
                            $first = $koneksi->query("
                                SELECT mn.nama_menu, d.jumlah 
                                FROM detail_pesanan d
                                JOIN menu mn ON mn.id_menu = d.id_menu
                                WHERE d.id_pesanan='{$row['id_pesanan']}' LIMIT 1
                            ")->fetch_assoc();

                            $jumlah_item = $koneksi->query("
                                SELECT COUNT(*) AS total 
                                FROM detail_pesanan 
                                WHERE id_pesanan='{$row['id_pesanan']}'
                            ")->fetch_assoc()['total'];
                        ?>

                        <tr>
                            <td><?= date('H:i d/m', strtotime($row['waktu_pembayaran'])) ?></td>
                            <td><span class="badge bg-warning text-dark"><?= $row['nomor_meja'] ?></span></td>
                            <td>
                                <?= $first['nama_menu'] ?> x<?= $first['jumlah'] ?>
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

                            <td><strong>Rp <?= number_format($row['total_tagihan'], 0, ',', '.') ?></strong></td>

                            <td>
                                <button class="btn btn-sm btn-primary" onclick="showDetail('<?= $row['id_pesanan'] ?>')">
                                    <i class="bi bi-search"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="printStruk('<?= $row['id_pesanan'] ?>')">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                    </tbody>
                </table>

            </div>
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

<script>

function showDetail(id) {
  const currentUrl = window.location.href.split('?')[0];
  fetch(currentUrl + '?action=detail&id_pesanan=' + id)
    .then(res => res.text())
    .then(html => {
      document.getElementById('detailContent').innerHTML = html;
      new bootstrap.Modal(document.getElementById('detailModal')).show();
    })
    .catch(error => {
      console.error('Error:', error);
      document.getElementById('detailContent').innerHTML = '<div class="alert alert-danger">Gagal memuat data</div>';
    });
}

function printStruk(id) {
  const currentUrl = window.location.href.split('?')[0];
  window.open(currentUrl + '?action=print&id_pesanan=' + id, '_blank', 'width=500,height=700');
}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>