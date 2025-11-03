<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../database/connect.php';

function clean($v){ return trim(htmlspecialchars($v ?? '')); }
function rupiah($v){ return 'Rp '.number_format($v,0,',','.'); }

// === Filter waktu ===
$range = $_GET['range'] ?? 'hari';
$today = new DateTime('now');

switch ($range) {
    case 'bulan':
        $start = new DateTime($today->format('Y-m-01 00:00:00'));
        $end   = new DateTime($today->format('Y-m-t 23:59:59'));
        break;
    case 'tahun':
        $start = new DateTime($today->format('Y-01-01 00:00:00'));
        $end   = new DateTime($today->format('Y-12-31 23:59:59'));
        break;
    default: // hari
        $start = new DateTime($today->format('Y-m-d 00:00:00'));
        $end   = new DateTime($today->format('Y-m-d 23:59:59'));
        break;
}

$start_sql = $start->format('Y-m-d H:i:s');
$end_sql   = $end->format('Y-m-d H:i:s');

// === Penjualan ===
$sql = "SELECT d.id_pesanan, 
               GROUP_CONCAT(CONCAT(COALESCE(m.nama_menu,'Item'),' x', d.jumlah) SEPARATOR ', ') AS items,
               SUM(d.subtotal) AS total,
               p.waktu_pesan
        FROM detail_pesanan d
        JOIN pesanan p ON d.id_pesanan = p.id_pesanan
        LEFT JOIN menu m ON d.id_menu = m.id_menu
        WHERE p.waktu_pesan BETWEEN ? AND ?
        GROUP BY d.id_pesanan
        ORDER BY p.waktu_pesan ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss",$start_sql,$end_sql);
$stmt->execute();
$sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === Pengeluaran ===
$sql2 = "SELECT id_beli, tanggal_beli, nama_bahan, harga 
          FROM pembelian_bahan 
          WHERE tanggal_beli BETWEEN ? AND ? 
          ORDER BY tanggal_beli ASC";
$stmt = $conn->prepare($sql2);
$stmt->bind_param("ss",$start_sql,$end_sql);
$stmt->execute();
$expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_sales = array_sum(array_column($sales,'total'));
$total_expenses = array_sum(array_column($expenses,'harga'));
$profit = $total_sales - $total_expenses;
$is_profit = $profit >= 0;

$status_text = $is_profit ? 'Untung' : 'Rugi';
$status_color_class = $is_profit ? 'status-untung' : 'status-rugi';

// === EXPORT CSV ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_keuangan_'.$range.'_'.date('Ymd_His').'.csv');
    $output = fopen('php://output', 'w');

    // Judul
    fputcsv($output, ['Laporan Keuangan - '.ucfirst($range).' Ini']);
    fputcsv($output, []);

    // Penjualan
    fputcsv($output, ['Rekap Penjualan']);
    fputcsv($output, ['No Pesanan', 'Item', 'Waktu', 'Total']);
    if (!empty($sales)) {
        foreach ($sales as $s) {
            fputcsv($output, [
                $s['id_pesanan'],
                $s['items'],
                date('d-m-Y H:i', strtotime($s['waktu_pesan'])),
                $s['total']
            ]);
        }
    } else {
        fputcsv($output, ['-', 'Tidak ada penjualan', '-', '-']);
    }

    fputcsv($output, []);
    fputcsv($output, ['Laporan Pengeluaran']);
    fputcsv($output, ['ID Beli', 'Tanggal', 'Nama Bahan', 'Harga']);
    if (!empty($expenses)) {
        foreach ($expenses as $e) {
            fputcsv($output, [
                $e['id_beli'],
                $e['tanggal_beli'],
                $e['nama_bahan'],
                $e['harga']
            ]);
        }
    } else {
        fputcsv($output, ['-', 'Tidak ada pengeluaran', '-', '-']);
    }

    fputcsv($output, []);
    fputcsv($output, ['Total Pendapatan', $total_sales]);
    fputcsv($output, ['Total Pengeluaran', $total_expenses]);
    fputcsv($output, ['Keuntungan Bersih', $profit]);
    fputcsv($output, ['Status', $status_text]);

    fclose($output);
    exit;
}

$chart_label = ($range === 'hari' ? date('j M') : ucfirst($range)." Ini");
$chart_data_array = [
    ['Tipe', 'Pendapatan', 'Pengeluaran'],
    [$chart_label, $total_sales, $total_expenses]
];
$chart_data_json = json_encode($chart_data_array);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan Keuangan</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://www.gstatic.com/charts/loader.js"></script>
<style>
:root{
  --blue:#2563eb;
  --red:#ef4444;
  --green:#10b981;
  --orange:#f97316;
  --gray:#6b7280;
  --bg:#f3f4f6;
}

/* ======== Layout Utama ======== */
body{
  margin:0;
  font-family:Inter,'Segoe UI',sans-serif;
  background:var(--bg);
  color:#111827;
}

.layout {display:flex;min-height:100vh;width:100%;}
.sidebar {width:250px;background:#1f2937;flex-shrink:0;transition:width 0.3s ease;}
.sidebar.closed {width:70px;}
.main-content {flex:1;transition:margin-left 0.3s ease,width 0.3s ease;margin-left:0;background:var(--bg);padding:24px;}
.sidebar.open ~ .main-content {margin-left:250px;}
.sidebar.closed ~ .main-content {margin-left:70px;}
@media (max-width:768px){.sidebar{position:fixed;height:100vh;z-index:100}.main-content{margin-left:0!important;width:100%}}

.container{max-width:1150px;margin:auto;}
h1{margin:0;font-size:24px;color:#111827;}
.small{font-size:13px;color:var(--gray);}
.range-btns{display:flex;gap:8px;margin:20px 0;}
.range-btns a{padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600;color:#374151;background:#fff;border:1px solid #e5e7eb;}
.range-btns a.active{background:var(--blue);color:#fff;border:none;}
.summary-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;}
.summary-card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.card-label{font-size:14px;color:var(--gray);margin-bottom:8px;}
.card-value{font-size:22px;font-weight:700;}
.card-status-box{text-align:center;font-weight:700;padding:10px;border-radius:8px;}
.status-untung .card-status-box{background:#d1fae5;color:var(--green);}
.status-rugi .card-status-box{background:#fee2e2;color:var(--red);}
.table{width:100%;border-collapse:collapse;}
.table th,.table td{padding:12px;border-bottom:1px solid #e5e7eb;}
.table th{text-align:left;background:#fff;}
.tabs{display:flex;gap:8px;margin:16px 0;}
.tab{background:#fff;padding:10px 16px;border-radius:999px;cursor:pointer;border:1px solid #e5e7eb;font-weight:600;color:#374151;}
.tab.active{background:var(--blue);color:#fff;}
.hidden{display:none;}
.card{background:#fff;padding:18px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-bottom:20px;}
.chart-container{width:100%;height:380px;}
.btn{padding:10px 14px;border-radius:8px;font-weight:600;text-decoration:none;}
.btn.primary{background:var(--blue);color:#fff;}
.btn.ghost{background:#fff;color:var(--blue);border:1px solid #cbd5e1;}
.actions{display:flex;gap:8px;}
@media print{.print-hide{display:none}}
</style>
</head>
<body>

<div class="layout">
  <?php include '../../sidebar/sidebar.php'; ?>
  <main class="main-content">
    <div class="container">

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
          <h1>Laporan Keuangan</h1>
          <div class="small">Rekap Penjualan & Pengeluaran Restoran</div>
        </div>
        <div class="actions print-hide">
          <a class="btn ghost" href="?range=<?= $range ?>&export=csv"><i class="fa fa-download"></i> CSV</a>
          <a class="btn primary" href="javascript:window.print()"><i class="fa fa-file-pdf"></i> PDF</a>
        </div>
      </div>

      <div class="range-btns print-hide">
        <a class="<?= $range==='hari'?'active':'' ?>" href="?range=hari">Hari Ini</a>
        <a class="<?= $range==='bulan'?'active':'' ?>" href="?range=bulan">Bulan Ini</a>
        <a class="<?= $range==='tahun'?'active':'' ?>" href="?range=tahun">Tahun Ini</a>
      </div>

      <div class="summary-cards">
        <div class="summary-card">
          <div class="card-label">Total Pendapatan</div>
          <div class="card-value" style="color:var(--green)"><?= rupiah($total_sales) ?></div>
        </div>
        <div class="summary-card">
          <div class="card-label">Total Pengeluaran</div>
          <div class="card-value" style="color:var(--red)"><?= rupiah($total_expenses) ?></div>
        </div>
        <div class="summary-card">
          <div class="card-label">Keuntungan Bersih</div>
          <div class="card-value" style="color:var(--orange)"><?= rupiah($profit) ?></div>
        </div>
        <div class="summary-card <?= $status_color_class ?>">
          <div class="card-status-box"><?= $status_text ?></div>
          <div style="text-align:center;color:var(--gray);font-size:14px;">Status <?= ucfirst($range) ?> Ini</div>
        </div>
      </div>

      <div class="card">
        <h3>Grafik Perbandingan <?= ucfirst($range) ?> Ini</h3>
        <div id="chart_div" class="chart-container"></div>
      </div>

      <div class="tabs">
        <div class="tab active" id="tab-penjualan">Rekap Penjualan</div>
        <div class="tab" id="tab-pengeluaran">Laporan Pengeluaran</div>
      </div>

      <div class="card tab-content" id="content-penjualan">
        <h3>Detail Penjualan <?= ucfirst($range) ?> Ini</h3>
        <table class="table">
          <thead>
            <tr>
              <th>No. Pesanan</th>
              <th>Item</th>
              <th style="text-align:right;">Waktu</th>
              <th style="text-align:right;">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($sales)): ?>
              <tr><td colspan="4" style="text-align:center;color:var(--gray);padding:18px;">Tidak ada penjualan</td></tr>
            <?php else: foreach($sales as $s): ?>
              <tr>
                <td><?= $s['id_pesanan'] ?></td>
                <td><?= htmlspecialchars($s['items']) ?></td>
                <td style="text-align:right;"><?= date('d-m-Y H:i', strtotime($s['waktu_pesan'])) ?></td>
                <td style="text-align:right;font-weight:600;"><?= rupiah($s['total']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <div class="card tab-content hidden" id="content-pengeluaran">
        <h3>Laporan Pengeluaran</h3>
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Tanggal</th>
              <th>Nama Bahan</th>
              <th style="text-align:right;">Harga</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($expenses)): ?>
              <tr><td colspan="4" style="text-align:center;color:var(--gray);padding:18px;">Tidak ada pengeluaran</td></tr>
            <?php else: $i=1; foreach($expenses as $e): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= $e['tanggal_beli'] ?></td>
                <td><?= htmlspecialchars($e['nama_bahan']) ?></td>
                <td style="text-align:right;"><?= rupiah($e['harga']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="3" style="text-align:right;">Total Pengeluaran</th>
              <th style="text-align:right;"><?= rupiah($total_expenses) ?></th>
            </tr>
            <tr>
              <th colspan="3" style="text-align:right;">Keuntungan Bersih</th>
              <th style="text-align:right;color:<?= $is_profit?'var(--green)':'var(--red)' ?>"><?= rupiah($profit) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>

    </div>
  </main>
</div>

<script>
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);
function drawChart(){
  const chartData = <?= $chart_data_json ?>;
  const data = google.visualization.arrayToDataTable(chartData);
  const options = {
    title:'Perbandingan Pendapatan dan Pengeluaran',
    titleTextStyle:{fontSize:16,bold:true},
    legend:{position:'bottom'},
    vAxis:{title:'Jumlah (Rp)',minValue:0},
    colors:['#2563eb','#f97316'],
    chartArea:{width:'80%',height:'70%'}
  };
  const chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
  chart.draw(data,options);
}
window.addEventListener('resize',drawChart);

// Tabs
const tabs=document.querySelectorAll('.tab');
const contents=document.querySelectorAll('.tab-content');
tabs.forEach(tab=>{
  tab.addEventListener('click',()=>{
    tabs.forEach(t=>t.classList.remove('active'));
    tab.classList.add('active');
    contents.forEach(c=>c.classList.add('hidden'));
    const id=tab.id.replace('tab-','content-');
    document.getElementById(id).classList.remove('hidden');
  });
});
</script>
</body>
</html>
