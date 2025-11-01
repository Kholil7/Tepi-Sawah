<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../database/connect.php'; // koneksi ke database

function clean($v){ return trim(htmlspecialchars($v ?? '')); }
function rupiah($v){ return 'Rp '.number_format($v,0,',','.'); }

// --- Range filter (hari | bulan | tahun) ---
$range = $_GET['range'] ?? 'hari';
$today = new DateTime('now');
$start = (clone $today)->setTime(0,0,0);
$end   = (clone $today)->setTime(23,59,59);

if($range === 'bulan'){
    $start = (clone $today)->modify('first day of this month')->setTime(0,0,0);
    $end   = (clone $today)->modify('last day of this month')->setTime(23,59,59);
} elseif($range === 'tahun'){
    $start = (clone $today)->setDate((int)$today->format('Y'),1,1)->setTime(0,0,0);
    $end   = (clone $today)->setDate((int)$today->format('Y'),12,31)->setTime(23,59,59);
}

$start_sql = $start->format('Y-m-d H:i:s');
$end_sql   = $end->format('Y-m-d H:i:s');

// === QUERY PENJUALAN ===
// Mengubah alias untuk Total agar konsisten dengan ringkasan
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

// === QUERY PENGELUARAN ===
$sql2 = "SELECT id_beli, tanggal_beli, nama_bahan, harga, keterangan 
          FROM pembelian_bahan 
          WHERE tanggal_beli BETWEEN ? AND ? 
          ORDER BY tanggal_beli ASC";
$stmt = $conn->prepare($sql2);
$stmt->bind_param("ss",$start_sql,$end_sql);
$stmt->execute();
$expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === PERHITUNGAN REKAP ===
$total_sales = array_sum(array_column($sales,'total'));
$total_expenses = array_sum(array_column($expenses,'harga'));
$profit = $total_sales - $total_expenses;
$is_profit = $profit >= 0;

// Menentukan warna dan teks untuk status
$status_text = $is_profit ? 'Untung' : 'Rugi';
$status_color_class = $is_profit ? 'status-untung' : 'status-rugi';

// === DATA UNTUK GRAFIK (Pendapatan vs Pengeluaran) ===
$chart_label = ($range === 'hari' ? date('j M') : ucfirst($range) . " Ini");
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
<title>Laporan Rekap Penjualan & Pengeluaran</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<style>
:root{
  --accent:#2563eb;
  --muted:#f3f4f6;
  --card:#fff;
  --text:#111827;
  --green:#10b981; 
  --red:#ef4444; 
  --orange:#f97316; 
  --blue-dark:#1e40af; 
}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,'Segoe UI',sans-serif;background:#f6f8fb;color:var(--text);padding:24px;}
.container{max-width:1150px;margin:0 auto;}
.header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:18px;}
.title h1{margin:0;font-size:24px;color:#0f172a}
.small{font-size:13px;color:#6b7280}
.controls{display:flex;gap:12px;align-items:center}
.range-btns{display:flex;gap:8px;background:#fff;padding:8px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.range-btns a{padding:8px 16px;border-radius:8px;text-decoration:none;color:#374151;font-weight:600;transition:background 0.2s, color 0.2s;}
.range-btns a.active{background:var(--blue-dark);color:#fff;} 

.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;border:none;cursor:pointer;font-weight:600}
.btn.primary{background:var(--accent);color:#fff;box-shadow:0 6px 18px rgba(37,99,235,0.12)}
.btn.ghost{background:#fff;color:var(--accent);border:1px solid #e6eefc}

/* === Summary Cards Styles === */
.summary-cards{display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;margin-bottom:20px;}
.summary-card{background:var(--card);border-radius:12px;padding:18px;box-shadow:0 4px 12px rgba(0,0,0,0.05);display:flex;flex-direction:column;}
.card-label{font-size:14px;color:#6b7280;margin-bottom:12px;}
.card-value{font-size:24px;font-weight:700;}
.card-icon-wrapper{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:8px;}
.card-icon{font-size:20px;}
.card-status-text{font-size:16px;font-weight:600;text-align:center;}
.card-status-label{font-size:14px;color:#6b7280;text-align:center;margin-top:4px;}
.card-sales .card-icon-wrapper{background:#ecfdf5;} 
.card-sales .card-icon{color:var(--green);}
.card-sales .card-value{color:var(--green);}
.card-expenses .card-icon-wrapper{background:#fef2f2;} 
.card-expenses .card-icon{color:var(--red);}
.card-expenses .card-value{color:var(--red);}
.card-profit .card-icon-wrapper{background:#fff7ed;} 
.card-profit .card-icon{color:var(--orange);}
.card-profit .card-value{color:var(--orange);}
.card-status{background:#fff;display:flex;flex-direction:column;justify-content:center;align-items:center;border:1px solid #eef2f8;}
.card-status-box{padding:10px 20px;border-radius:8px;font-weight:700;margin-bottom:8px;}
.status-untung .card-status-box{background:#d1fae5;color:var(--green);}
.status-rugi .card-status-box{background:#fee2e2;color:var(--red);}

/* Tabs Styling */
.tabs{display:flex;gap:8px;margin-bottom:16px}
.tab{background:#fff;padding:10px 18px;border-radius:999px;border:1px solid #eef2f8;cursor:pointer;color:#374151;transition:.2s;font-weight:600;}
.tab.active{background:var(--muted);box-shadow:inset 0 0 0 6px #f3f4f6;color:#111827}

/* Table Styles */
.card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 20px rgba(2,6,23,0.04);margin-bottom:16px;}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:14px 12px;}
.table th{background:#fff;text-align:left;color:#374151;font-weight:700;border-bottom:none;} /* Hapus border bawah di thead */
.table td{border-bottom:1px solid #f1f5f9;color:#374151;} /* Tambahkan border bawah di body */
.table tbody tr:last-child td{border-bottom:none;} /* Hapus border bawah di baris terakhir */
.table tfoot th{border-top:2px solid #eef2f8;}

.actions{display:flex;gap:8px}
.print-hide{display:inline-block}
@media print{.print-hide{display:none}}
.hidden{display:none;}

/* Style untuk Grafik */
.chart-container {
    width: 100%;
    height: 400px;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="title">
      <h1>Laporan</h1>
      <div class="small">Rekap penjualan dan pengeluaran restoran</div>
    </div>

    <div class="controls">
      <div class="actions print-hide">
        <a class="btn ghost" href="?range=<?= $range ?>&export=csv"><i class="fa fa-download"></i> CSV</a>
        <a class="btn primary" href="javascript:window.print()"><i class="fa fa-file-pdf"></i> PDF</a>
      </div>
    </div>
  </div>

  <div class="range-btns print-hide" style="margin-bottom: 20px;">
    <a class="btn <?= $range==='hari' ? 'active':'' ?>" href="?range=hari">Hari Ini</a>
    <a class="btn <?= $range==='bulan' ? 'active':'' ?>" href="?range=bulan">Bulan Ini</a>
    <a class="btn <?= $range==='tahun' ? 'active':'' ?>" href="?range=tahun">Tahun Ini</a>
  </div>

  <div class="summary-cards">
    <div class="summary-card card-sales">
      <div class="card-label">Total Pendapatan</div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <div class="card-icon-wrapper"><i class="fa fa-dollar-sign card-icon"></i></div>
        <div class="card-value"><?= rupiah($total_sales) ?></div>
      </div>
    </div>
    
    <div class="summary-card card-expenses">
      <div class="card-label">Total Pengeluaran</div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <div class="card-icon-wrapper"><i class="fa fa-shopping-cart card-icon"></i></div>
        <div class="card-value"><?= rupiah($total_expenses) ?></div>
      </div>
    </div>
    
    <div class="summary-card card-profit">
      <div class="card-label">Keuntungan Bersih</div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <div class="card-icon-wrapper"><i class="fa fa-chart-line card-icon"></i></div>
        <div class="card-value"><?= rupiah($profit) ?></div>
      </div>
    </div>
    
    <div class="summary-card card-status <?= $status_color_class ?>">
      <div class="card-status-box"><?= $status_text ?></div>
      <div class="card-status-label">Status <?= ucfirst($range) ?> Ini</div>
    </div>
  </div>
  <div class="card">
      <h3 style="margin-top:0">Grafik Perbandingan <?= ucfirst($range) ?> Ini</h3>
      <div id="chart_div" class="chart-container"></div>
  </div>
  <div class="tabs">
    <div class="tab active" id="tab-penjualan">Rekap Penjualan</div>
    <div class="tab" id="tab-pengeluaran">Laporan Pengeluaran</div>
  </div>

  <div class="card tab-content" id="content-penjualan">
    <h3 style="margin-top:0">Detail Penjualan <?= ucfirst($range) ?> Ini</h3>
    <table class="table" style="width: 100%;">
      <thead>
        <tr>
          <th style="width: 15%;">No. Pesanan</th>
          <th style="width: 50%;">Item</th>
          <th style="width: 15%; text-align: right;">Waktu</th>
          <th style="width: 20%; text-align: right;">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($sales)): ?>
          <tr><td colspan="4" style="padding:20px;text-align:center;color:#6b7280;border-bottom: none;">Tidak ada penjualan</td></tr>
        <?php else: foreach($sales as $s): ?>
          <tr>
            <td><?= $s['id_pesanan'] ?></td>
            <td><?= htmlspecialchars($s['items']) ?></td>
            <td style="text-align:right;"><?= date('H:i', strtotime($s['waktu_pesan'])) ?></td>
            <td style="text-align:right; font-weight: 600;"><?= rupiah($s['total']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
      <tfoot>
        </tfoot>
    </table>
  </div>

  <div class="card tab-content hidden" id="content-pengeluaran">
    <h3 style="margin-top:0">Laporan Pengeluaran</h3>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Tanggal</th>
          <th>Nama Bahan</th>
          <th style="text-align:right">Harga</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($expenses)): ?>
          <tr><td colspan="4" style="padding:20px;text-align:center;color:#6b7280;border-bottom: none;">Tidak ada pengeluaran</td></tr>
        <?php else: $i=1; foreach($expenses as $e): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= $e['tanggal_beli'] ?></td>
            <td><?= htmlspecialchars($e['nama_bahan']) ?></td>
            <td style="text-align:right"><?= rupiah($e['harga']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3" style="text-align:right">Total Pengeluaran</th>
          <th style="text-align:right"><?= rupiah($total_expenses) ?></th>
        </tr>
        <tr>
          <th colspan="3" style="text-align:right">Keuntungan Bersih</th>
          <th style="text-align:right;color:<?= $profit>=0?'var(--green)':'var(--red)' ?>"><?= rupiah($profit) ?></th>
        </tr>
      </tfoot>
    </table>
  </div>

</div>

<script>
// === LOGIKA GRAFIK GOOGLE CHARTS ===
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
    const chartData = <?= $chart_data_json ?>;
    const data = google.visualization.arrayToDataTable(chartData);

    const options = {
      title: 'Perbandingan Pendapatan dan Pengeluaran',
      titleTextStyle: { fontSize: 16, bold: true, color: '#111827' },
      legend: { position: 'bottom' },
      vAxis: { 
          title: 'Jumlah (Rp)',
          format: 'decimal',
          minValue: 0
      },
      // Mengubah warna menjadi biru dan oranye (sesuai gambar)
      colors: ['#2563eb', '#f97316'], 
      chartArea: { width: '80%', height: '70%' },
      bar: { groupWidth: '50%' }
    };

    const chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
    chart.draw(data, options);
}
window.addEventListener('resize', drawChart);


// === SWITCH TAB ===
const tabs = document.querySelectorAll('.tab');
const contents = document.querySelectorAll('.tab-content');
tabs.forEach(tab=>{
  tab.addEventListener('click',()=>{
    tabs.forEach(t=>t.classList.remove('active'));
    tab.classList.add('active');
    contents.forEach(c=>c.classList.add('hidden'));
    const id = tab.id.replace('tab-','content-');
    document.getElementById(id).classList.remove('hidden');
    
    // Redraw chart saat tab diaktifkan (memastikan grafik tampil dengan benar)
    if (id === 'content-pe njualan' || id === 'content-pengeluaran') { 
         drawChart(); 
    }
  });
});
</script>
</body>
</html>