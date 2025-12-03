<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();
require_once '../../database/connect.php';
include '../../sidebar/sidebar_kasir.php';

class Pesanan {
    private $conn;
    public function __construct($conn) { 
        $this->conn = $conn; 
    }

    // GET PESANAN AKTIF (BELUM SELESAI) - DIPERBAIKI
public function getAktif() {
    $sql = "
        SELECT p.id_pesanan, 
               p.id_meja, 
               p.waktu_pesan, 
               p.jenis_pesanan, 
               p.status_pesanan, 
               p.metode_bayar, 
               p.total_harga, 
               p.catatan,
               p.aktif,
               m.nomor_meja,
               pb.bukti_pembayaran,
               pb.status as status_pembayaran,
               pb.metode as metode_pembayaran_pb,
               pb.waktu_pembayaran,
               (SELECT COUNT(*) FROM detail_pesanan d WHERE d.id_pesanan = p.id_pesanan) AS jumlah_item
        FROM pesanan p
        LEFT JOIN meja m ON p.id_meja = m.id_meja
        LEFT JOIN pembayaran pb ON p.id_pesanan = pb.id_pesanan
        WHERE p.aktif = 1
        ORDER BY p.waktu_pesan DESC
    ";
    
    $result = $this->conn->query($sql);
    return $result;
}

    // GET PESANAN SELESAI HARI INI UNTUK DITAMPILKAN
    public function getSelesaiHariIni() {
        $sql = "
            SELECT p.*, 
                   m.nomor_meja,
                   pb.metode as metode_pembayaran,
                   pb.status as status_pembayaran,
                   (SELECT COUNT(*) FROM detail_pesanan d WHERE d.id_pesanan = p.id_pesanan) AS jumlah_item
            FROM pesanan p
            LEFT JOIN meja m ON p.id_meja = m.id_meja
            LEFT JOIN pembayaran pb ON p.id_pesanan = pb.id_pesanan
            WHERE p.status_pesanan = 'selesai' 
            AND DATE(p.waktu_pesan) = CURDATE()
            ORDER BY p.waktu_pesan DESC
            LIMIT 10
        ";
        
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // GET COUNT PESANAN SELESAI HARI INI
    public function getCountSelesaiHariIni() {
        $sql = "SELECT COUNT(*) as total FROM pesanan WHERE status_pesanan = 'selesai' AND DATE(waktu_pesan) = CURDATE()";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        return 0;
    }

    // GET STATISTIK YANG BENAR
    public function getStatistik() {
        $data = [
            'menunggu' => 0,
            'diterima' => 0, 
            'dimasak' => 0, 
            'disajikan' => 0, 
            'selesai' => 0, 
            'dibatalkan' => 0
        ];
        
        // Hitung pesanan aktif
        $result = $this->conn->query("
            SELECT status_pesanan, COUNT(*) AS total 
            FROM pesanan 
            WHERE status_pesanan IN ('menunggu', 'diterima', 'dimasak', 'disajikan', 'dibatalkan')
            AND DATE(waktu_pesan) = CURDATE()
            GROUP BY status_pesanan
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (isset($data[$row['status_pesanan']])) {
                    $data[$row['status_pesanan']] = $row['total'];
                }
            }
        }
        
        // Hitung pesanan selesai hari ini
        $data['selesai'] = $this->getCountSelesaiHariIni();
        
        return $data;
    }

    public function getDetailPesanan($id) {
        $id = intval($id);
        $sql = "
            SELECT d.*, mn.nama_menu
            FROM detail_pesanan d
            LEFT JOIN menu mn ON d.id_menu = mn.id_menu
            WHERE d.id_pesanan = $id
        ";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // FUNGSI UNTUK MENGECEK STATUS PEMBAYARAN - DIPERBAIKI
    public function isPembayaranLunas($pesanan) {
        // Prioritas 1: Cek status_pembayaran dari tabel pembayaran
        if (isset($pesanan['status_pembayaran'])) {
            if ($pesanan['status_pembayaran'] === 'sudah_bayar') {
                return true;
            } elseif ($pesanan['status_pembayaran'] === 'gagal') {
                return false;
            }
        }
        
        // Prioritas 2: Cek jika ada bukti pembayaran (untuk QRIS)
        if (!empty($pesanan['bukti_pembayaran'])) {
            return true;
        }
        
        // Prioritas 3: Cek waktu pembayaran
        if (!empty($pesanan['waktu_pembayaran']) && $pesanan['waktu_pembayaran'] != '0000-00-00 00:00:00') {
            return true;
        }
        
        return false;
    }

    // FUNGSI BARU: Untuk mendapatkan status pembayaran yang detail
    public function getDetailStatusPembayaran($pesanan) {
        $status = $pesanan['status_pembayaran'] ?? 'belum_bayar';
        $hasBukti = !empty($pesanan['bukti_pembayaran']);
        
        if ($status === 'sudah_bayar') {
            return [
                'status' => 'lunas',
                'class' => 'lunas',
                'text' => 'LUNAS',
                'icon' => 'fa-check-circle',
                'description' => 'Pembayaran sudah dikonfirmasi'
            ];
        } elseif ($status === 'gagal') {
            return [
                'status' => 'gagal',
                'class' => 'gagal',
                'text' => 'GAGAL BAYAR',
                'icon' => 'fa-times-circle',
                'description' => 'Pembayaran gagal atau ditolak'
            ];
        } elseif ($hasBukti) {
            return [
                'status' => 'menunggu_konfirmasi',
                'class' => 'menunggu-konfirmasi',
                'text' => 'MENUNGGU KONFIRMASI',
                'icon' => 'fa-clock',
                'description' => 'Menunggu konfirmasi bukti pembayaran'
            ];
        } else {
            return [
                'status' => 'belum_bayar',
                'class' => 'belum-bayar',
                'text' => 'BELUM BAYAR',
                'icon' => 'fa-exclamation-circle',
                'description' => 'Belum melakukan pembayaran'
            ];
        }
    }
}

$pesananModel = new Pesanan($conn);
$pesananAktif = $pesananModel->getAktif();
$pesananSelesai = $pesananModel->getSelesaiHariIni();
$statistik = $pesananModel->getStatistik();

function rupiah($angka) { 
    return 'Rp ' . number_format($angka, 0, ',', '.'); 
}

// Fungsi untuk validasi path gambar
function validateImagePath($filename) {
    if (empty($filename)) return null;
    
    $basePaths = [
        '../../assets/uploads/',
        '../assets/uploads/', 
        './assets/uploads/',
        'assets/uploads/'
    ];
    
    foreach ($basePaths as $basePath) {
        $fullPath = $basePath . $filename;
        if (file_exists($fullPath) && is_file($fullPath)) {
            return $fullPath;
        }
    }
    return null;
}

// FUNGSI UNTUK MENENTUKAN STATUS PEMBAYARAN - DIPERBAIKI
function getStatusPembayaran($pesanan) {
    global $pesananModel;
    return $pesananModel->getDetailStatusPembayaran($pesanan);
}

// Fungsi untuk mendapatkan metode pembayaran yang benar
function getMetodePembayaran($pesanan) {
    if (!empty($pesanan['metode_pembayaran_pb'])) {
        return $pesanan['metode_pembayaran_pb'];
    } elseif (!empty($pesanan['metode_bayar'])) {
        return $pesanan['metode_bayar'];
    } elseif (!empty($pesanan['bukti_pembayaran'])) {
        return 'QRIS';
    }
    return '-';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Aktif - Kasir | Tepi Sawah</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
body {
  font-family: 'Poppins', sans-serif;
  background: #f8fafc;
  margin: 0; padding: 0;
  color: #1e293b;
    margin-left: 270px;
}
/* .container { margin-left: 260px; padding: 25px; } */
.page-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 25px;
}
.page-header h1 {
  font-size: 22px; font-weight: 600; color: #0f172a;
  display: flex; align-items: center; gap: 10px;
}
.refresh-btn {
  background: #38bdf8; color: #fff; border: none;
  padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 14px;
  transition: 0.3s;
}
.refresh-btn:hover { background: #0284c7; }

.stats-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 15px; margin-bottom: 25px;
}
.stat-card {
  background: white; border-radius: 14px; padding: 18px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  text-align: center; transition: 0.3s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-card h4 { margin:0; font-size:15px; color:#475569; }
.stat-card .value { font-size:28px; font-weight:600; color:#0f172a; margin-top:5px; }

.stat-card.menunggu { border-top: 4px solid #fbbf24; }
.stat-card.diterima { border-top: 4px solid #8b5cf6; }
.stat-card.dimasak { border-top: 4px solid #3b82f6; }
.stat-card.siap { border-top: 4px solid #22c55e; }
.stat-card.selesai { border-top: 4px solid #94a3b8; }
.stat-card.dibatalkan { border-top: 4px solid #ef4444; }

.tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.tab {
  background: #e2e8f0; border: none; padding: 8px 16px;
  border-radius: 8px; cursor: pointer; font-size: 14px; font-weight:500; color:#475569;
  transition:0.3s;
}
.tab.active, .tab:hover { background: #0ea5e9; color: #fff; }

.pesanan-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(320px,1fr));
  gap: 18px;
}
.pesanan-card {
  background: white; border-radius:16px; box-shadow:0 3px 10px rgba(0,0,0,0.05);
  padding:20px; transition:0.3s; position: relative;
}
.pesanan-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }

.pesanan-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.pesanan-id { font-weight:600; font-size:15px; color:#0f172a; }

.status-badge {
  padding:5px 10px; border-radius:10px; font-size:12px; font-weight:600; text-transform:uppercase;
}
.status-badge.menunggu { background:#fef3c7;color:#b45309; }
.status-badge.diterima { background:#e0e7ff;color:#3730a3; }
.status-badge.dimasak { background:#dbeafe;color:#1d4ed8; }
.status-badge.disajikan { background:#dcfce7;color:#166534; }
.status-badge.dibatalkan { background:#fee2e2;color:#991b1b; }

/* STATUS PEMBAYARAN - DIPERBAIKI */
.status-pembayaran {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  margin-left: 8px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.status-pembayaran.lunas {
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.status-pembayaran.belum-bayar {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fcd34d;
}

.status-pembayaran.menunggu-konfirmasi {
  background: #dbeafe;
  color: #1e40af;
  border: 1px solid #93c5fd;
}

.status-pembayaran.gagal {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
}

.pesanan-info { display:flex; gap:12px; flex-wrap:wrap; font-size:13px; color:#64748b; margin-bottom:10px; }
.info-item i { margin-right:5px; color:#0ea5e9; }

.items-list { border-top:1px solid #e2e8f0; padding-top:8px; margin-bottom:10px; }
.item-row { display:flex; justify-content:space-between; align-items:center; font-size:13px; margin:5px 0; }
.item-name { font-weight:500; color:#1e293b; }
.item-status { padding:2px 8px; border-radius:8px; font-size:11px; text-transform:capitalize; }
.item-status.menunggu { background:#fef3c7; color:#92400e; }
.item-status.dimasak { background:#dbeafe; color:#1e40af; }
.item-status.siap { background:#dcfce7; color:#166534; }
.item-status.dibatalkan { background:#fee2e2; color:#991b1b; }

.total-section { display:flex; justify-content:space-between; align-items:center; font-weight:600; padding-top:8px; border-top:1px solid #e2e8f0; margin-bottom:10px; }
.total-label { color:#64748b; }
.total-value { color:#0f172a; }

.bukti-pembayaran-section {
    margin: 12px 0;
    padding: 12px;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 10px;
    border: 1px solid #bbf7d0;
}

.bukti-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #166534;
}

.bukti-image-container {
    position: relative;
    display: inline-block;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.bukti-image-container:hover {
    transform: translateY(-2px);
}

.bukti-image {
    width: 100%;
    max-width: 200px;
    height: auto;
    display: block;
    cursor: pointer;
}

.bukti-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
}

.bukti-image-container:hover .bukti-overlay {
    opacity: 1;
}

.bukti-overlay i {
    color: white;
    font-size: 24px;
}

.bukti-info {
    margin-top: 8px;
    text-align: center;
}

.bukti-info small {
    color: #64748b;
    font-size: 11px;
}

.menunggu-bukti-section {
    margin: 12px 0;
    padding: 12px;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-radius: 10px;
    border: 1px solid #fcd34d;
}

.menunggu-bukti {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #92400e;
}

.error-bukti-section {
    margin: 12px 0;
    padding: 12px;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-radius: 10px;
    border: 1px solid #fca5a5;
}

.error-bukti {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #991b1b;
}

/* SECTION UNTUK STATUS PEMBAYARAN GAGAL */
.pembayaran-gagal-section {
  margin: 12px 0;
  padding: 12px;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-radius: 10px;
  border: 1px solid #fca5a5;
}

.pembayaran-gagal {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #991b1b;
}

.pembayaran-gagal i {
  color: #ef4444;
}

/* SECTION UNTUK MENUNGGU KONFIRMASI */
.pembayaran-menunggu-section {
  margin: 12px 0;
  padding: 12px;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border-radius: 10px;
  border: 1px solid #93c5fd;
}

.pembayaran-menunggu {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #1e40af;
}

.pembayaran-menunggu i {
  color: #3b82f6;
}

.pembayaran-warning {
    margin: 10px 0;
    padding: 10px;
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    font-size: 12px;
    color: #92400e;
    text-align: center;
}

.action-buttons { display:flex; gap:6px; flex-wrap:wrap; }
.btn { border:none; border-radius:8px; padding:8px 12px; cursor:pointer; font-size:13px; font-weight:500; display:flex; align-items:center; gap:5px; transition:0.3s; }
.btn i { font-size:13px; }
.btn-success { background:#22c55e; color:white; }
.btn-success:hover { background:#16a34a; }
.btn-danger { background:#ef4444; color:white; }
.btn-danger:hover { background:#dc2626; }
.btn-secondary { background:#e2e8f0;color:#475569; }
.btn-secondary:hover { background:#cbd5e1; }
.btn-warning { background:#f59e0b; color:white; }
.btn-warning:hover { background:#d97706; }
.btn-info { background:#3b82f6; color:white; }
.btn-info:hover { background:#2563eb; }
.btn-purple { background:#8b5cf6; color:white; }
.btn-purple:hover { background:#7c3aed; }

/* TOMBOL AKSI PEMBAYARAN BARU */
.btn-gagal {
  background: #ef4444;
  color: white;
}

.btn-gagal:hover {
  background: #dc2626;
}

.btn-reset {
  background: #6b7280;
  color: white;
}

.btn-reset:hover {
  background: #4b5563;
}

.empty-state { text-align:center; padding:60px 20px; color:#64748b; }
.empty-state i { font-size:48px; color:#94a3b8; margin-bottom:10px; }

.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index: 9999; }
.modal.active { display:flex; }
.modal-content { background:white; padding:20px; border-radius:12px; width:100%; max-width:400px; }
.modal-header h3 { margin:0 0 10px 0; }
.modal-footer { display:flex; justify-content:flex-end; gap:10px; margin-top:10px; }

.modal-preview {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.95);
    justify-content: center;
    align-items: center;
    z-index: 10000;
    backdrop-filter: blur(5px);
}

.modal-preview.active {
    display: flex;
    animation: fadeIn 0.3s ease;
}

.modal-preview-content {
    max-width: 90%;
    max-height: 90%;
    position: relative;
    animation: zoomIn 0.3s ease;
}

.modal-preview-content img {
    width: 100%;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.modal-preview-close {
    position: absolute;
    top: -50px;
    right: 0;
    background: none;
    border: none;
    color: white;
    font-size: 36px;
    cursor: pointer;
    padding: 5px;
    transition: color 0.3s ease;
}

.modal-preview-close:hover {
    color: #fbbf24;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes zoomIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* Tambahan style untuk section pesanan selesai */
.selesai-section {
    margin-top: 30px;
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.selesai-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e2e8f0;
}

.selesai-header h2 {
    margin: 0;
    font-size: 20px;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}

.selesai-header h2 i {
    color: #10b981;
}

.toggle-selesai {
    background: #f1f5f9;
    border: none;
    padding: 8px 15px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #475569;
    transition: 0.3s;
}

.toggle-selesai:hover {
    background: #e2e8f0;
}

.selesai-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.5s ease, opacity 0.3s ease;
    opacity: 0;
}

.selesai-grid.expanded {
    max-height: 1000px;
    opacity: 1;
}

.pesanan-card-selesai {
    background: #f8fafc;
    border-radius: 12px;
    padding: 15px;
    border-left: 4px solid #10b981;
    transition: 0.3s;
}

.pesanan-card-selesai:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.pesanan-header-selesai {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.pesanan-id-selesai {
    font-weight: 600;
    color: #0f172a;
    font-size: 14px;
}

.pesanan-time-selesai {
    font-size: 12px;
    color: #64748b;
}

.pesanan-info-selesai {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 12px;
    color: #64748b;
    margin-bottom: 8px;
}

.info-item-selesai {
    display: flex;
    align-items: center;
    gap: 4px;
}

.total-section-selesai {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
    margin-top: 8px;
}

.total-label-selesai {
    color: #64748b;
    font-size: 13px;
}

.total-value-selesai {
    color: #0f172a;
    font-size: 14px;
}

.badge-selesai {
    background: #dcfce7;
    color: #166534;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
}

.empty-selesai {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
    grid-column: 1 / -1;
}

.empty-selesai i {
    font-size: 48px;
    color: #cbd5e1;
    margin-bottom: 10px;
}

/* Notifikasi */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10001;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease;
}

.notification.success { background: #10b981; }
.notification.error { background: #ef4444; }
.notification.warning { background: #f59e0b; }
.notification.info { background: #3b82f6; }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Loading */
.loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Counter animation */
.counter-update {
    animation: pulse 0.5s ease;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* detail */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-detail {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    max-height: 85vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-detail-header {
    padding: 20px 25px;
    background: linear-gradient(135deg, #FF6B00 0%, #ff8533 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-detail-header h3 {
    margin: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.close-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.close-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.modal-detail-body {
    padding: 25px;
    max-height: calc(85vh - 80px);
    overflow-y: auto;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 16px;
}

.detail-section {
    margin-bottom: 25px;
}

.detail-section h4 {
    font-size: 16px;
    color: #333;
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 10px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 15px;
    color: #333;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-menunggu {
    background: #fff3cd;
    color: #856404;
}

.status-diterima {
    background: #d1ecf1;
    color: #0c5460;
}

.status-dimasak {
    background: #f8d7da;
    color: #721c24;
}

.status-disajikan {
    background: #d4edda;
    color: #155724;
}

.status-selesai {
    background: #28a745;
    color: white;
}

.status-ditolak {
    background: #dc3545;
    color: white;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.items-table thead {
    background: #f8f9fa;
}

.items-table th {
    padding: 12px;
    text-align: left;
    font-size: 13px;
    color: #666;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.items-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    color: #333;
}

.items-table tbody tr:hover {
    background: #f8f9fa;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 15px 0;
    border-top: 2px solid #dee2e6;
    margin-top: 10px;
}

.total-row .label {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.total-row .value {
    font-size: 18px;
    font-weight: 700;
    color: #FF6B00;
}

.payment-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 10px;
}

.payment-info .info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.payment-info .info-row:last-child {
    margin-bottom: 0;
}

.bukti-bayar {
    margin-top: 15px;
    text-align: center;
}

.bukti-bayar img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    border: 2px solid #dee2e6;
    cursor: pointer;
    transition: transform 0.3s;
}

.bukti-bayar img:hover {
    transform: scale(1.05);
}

/* alert */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Modal Confirm */
.modal-confirm {
    background: white;
    border-radius: 16px;
    padding: 35px;
    width: 90%;
    max-width: 420px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-confirm-icon {
    font-size: 64px;
    color: #FF6B00;
    margin-bottom: 20px;
    animation: bounce 0.6s ease;
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.modal-confirm h3 {
    font-size: 24px;
    color: #333;
    margin: 0 0 15px 0;
    font-weight: 600;
}

.modal-confirm p {
    font-size: 16px;
    color: #666;
    margin: 0 0 30px 0;
    line-height: 1.5;
}

.modal-confirm-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

/* Modal Success */
.modal-success {
    background: white;
    border-radius: 16px;
    padding: 40px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
    from {
        transform: scale(0.8);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.modal-success-icon {
    font-size: 72px;
    color: #28a745;
    margin-bottom: 20px;
    animation: scaleInBounce 0.5s ease;
}

@keyframes scaleInBounce {
    0% { transform: scale(0); }
    60% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.modal-success h3 {
    font-size: 24px;
    color: #333;
    margin: 0 0 15px 0;
    font-weight: 600;
}

.modal-success p {
    font-size: 16px;
    color: #666;
    margin: 0 0 30px 0;
}

/* Modal Error */
.modal-error {
    background: white;
    border-radius: 16px;
    padding: 40px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-15px); }
    50% { transform: translateX(15px); }
    75% { transform: translateX(-10px); }
}

.modal-error-icon {
    font-size: 72px;
    color: #dc3545;
    margin-bottom: 20px;
}

.modal-error h3 {
    font-size: 24px;
    color: #333;
    margin: 0 0 15px 0;
    font-weight: 600;
}

.modal-error p {
    font-size: 16px;
    color: #666;
    margin: 0 0 30px 0;
}

/* Button Styles */
.btn-modal {
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 120px;
    justify-content: center;
}

.btn-modal:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.btn-modal:active {
    transform: translateY(0);
}

.btn-cancel {
    background: #6c757d;
    color: white;
}

.btn-cancel:hover {
    background: #5a6268;
}

.btn-confirm {
    background: #FF6B00;
    color: white;
}

.btn-confirm:hover {
    background: #e55f00;
}

.btn-ok {
    background: #28a745;
    color: white;
}

.btn-ok:hover {
    background: #218838;
}

.btn-close {
    background: #dc3545;
    color: white;
}

.btn-close:hover {
    background: #c82333;
}

</style>
</head>
<body>

<div class="container">
  <div class="page-header">
    <h1><i class="fas fa-list-check"></i> Pesanan Aktif</h1>
    <button class="refresh-btn" onclick="location.reload()"><i class="fas fa-rotate"></i> Refresh</button>
  </div>

  <div class="stats-grid">
    <div class="stat-card menunggu">
        <h4>Menunggu</h4>
        <div class="value"><?= $statistik['menunggu'] ?? 0 ?></div>
        <small>Pesanan baru</small>
    </div>
    <div class="stat-card diterima">
        <h4>Diterima</h4>
        <div class="value"><?= $statistik['diterima'] ?? 0 ?></div>
        <small>Pesanan diterima</small>
    </div>
    <div class="stat-card dimasak">
        <h4>Dimasak</h4>
        <div class="value"><?= $statistik['dimasak'] ?? 0 ?></div>
        <small>Sedang dimasak</small>
    </div>
    <div class="stat-card siap">
        <h4>Siap Disajikan</h4>
        <div class="value"><?= $statistik['disajikan'] ?? 0 ?></div>
        <small>Siap disajikan</small>
    </div>
    <div class="stat-card selesai">
        <h4>Selesai</h4>
        <div class="value counter-update" id="countSelesai"><?= $statistik['selesai'] ?? 0 ?></div>
        <small>Pesanan selesai hari ini</small>
    </div>
    <!-- <div class="stat-card dibatalkan">
        <h4>Dibatalkan</h4>
        <div class="value"><?= $statistik['dibatalkan'] ?? 0 ?></div>
        <small>Pesanan dibatalkan</small>
    </div> -->
  </div>

  <div class="tabs">
    <button class="tab active" data-status="semua">Semua Pesanan</button>
    <button class="tab" data-status="menunggu">Menunggu</button>
    <button class="tab" data-status="diterima">Diterima</button>
    <button class="tab" data-status="dimasak">Dimasak</button>
    <button class="tab" data-status="disajikan">Siap Disajikan</button>
    <!-- <button class="tab" data-status="dibatalkan">Dibatalkan</button>
    <button class="tab" data-status="dibatalkan">Ditolak</button> -->
  </div>

  <div class="pesanan-grid" id="pesananGrid">
    <?php if ($pesananAktif && $pesananAktif->num_rows > 0): ?>
      <?php while ($pesanan = $pesananAktif->fetch_assoc()):
        $details = $pesananModel->getDetailPesanan($pesanan['id_pesanan']);
        $statusPembayaran = getStatusPembayaran($pesanan);
        $metodePembayaran = getMetodePembayaran($pesanan);
        
        $hasBuktiPembayaran = false;
        $buktiPath = "";
        
        if (!empty($pesanan['bukti_pembayaran'])) {
            $buktiPath = validateImagePath($pesanan['bukti_pembayaran']);
            if ($buktiPath) {
                $hasBuktiPembayaran = true;
            }
        }
      ?>
      <div class="pesanan-card <?= htmlspecialchars($pesanan['status_pesanan']) ?>" data-status="<?= htmlspecialchars($pesanan['status_pesanan']) ?>" id="pesanan-<?= $pesanan['id_pesanan'] ?>">
        <div class="pesanan-header">
          <div>
            <div class="pesanan-id">#<?= $pesanan['id_pesanan'] ?></div>
            <small style="color:#64748b;">Meja <?= $pesanan['nomor_meja'] ?? '-' ?></small>
          </div>
          <div>
            <span class="status-badge <?= htmlspecialchars($pesanan['status_pesanan']) ?>">
              <?= strtoupper(str_replace('_',' ',$pesanan['status_pesanan'])) ?>
            </span>
            <span class="status-pembayaran <?= $statusPembayaran['class'] ?>" title="<?= $statusPembayaran['description'] ?>">
              <i class="fas <?= $statusPembayaran['icon'] ?>"></i>
              <?= $statusPembayaran['text'] ?>
            </span>
          </div>
        </div>

        <div class="pesanan-info">
          <div class="info-item"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($pesanan['waktu_pesan'])) ?></div>
          <div class="info-item"><i class="fas fa-utensils"></i> <?= ucfirst($pesanan['jenis_pesanan'] ?? '-') ?></div>
          <div class="info-item">
            <i class="fas fa-wallet"></i> 
            <?= ucfirst($metodePembayaran) ?>
            <?php if ($hasBuktiPembayaran): ?>
              <span style="color:#10b981;"><i class="fas fa-check-circle"></i></span>
            <?php elseif (strtolower($metodePembayaran) === 'qris'): ?>
              <span style="color:#f59e0b;"><i class="fas fa-clock"></i></span>
            <?php endif; ?>
          </div>
          <div class="info-item"><i class="fas fa-shopping-bag"></i> <?= $pesanan['jumlah_item'] ?? 0 ?> Item</div>
        </div>

    <div class="items-list">
    <?php if (!empty($details)): ?>
        <?php foreach ($details as $item): ?>
        <div class="item-row">
            <div>
                <span class="item-name"><?= htmlspecialchars($item['nama_menu'] ?? '-') ?></span> × <?= $item['jumlah'] ?>
            </div>
            
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="item-row no-items">
            <span class="item-name">Tidak ada item pesanan.</span>
        </div>
    <?php endif; ?>
    </div>

        <div class="total-section">
          <span class="total-label">Total</span>
          <span class="total-value"><?= rupiah($pesanan['total_harga']) ?></span>
        </div>

        <!-- SECTION INFORMASI STATUS PEMBAYARAN -->
        <?php if ($statusPembayaran['status'] === 'menunggu_konfirmasi'): ?>
        <div class="pembayaran-menunggu-section">
          <div class="pembayaran-menunggu">
            <i class="fas fa-info-circle"></i>
            <span>Bukti pembayaran telah diupload. Silakan konfirmasi atau tolak pesanan.</span>
          </div>
        </div>
        <?php if ($statusPembayaran['status'] === 'gagal'): ?>
<div class="pembayaran-gagal-section">
  <div class="pembayaran-gagal">
    <i class="fas fa-exclamation-triangle"></i>
    <span>Pembayaran ditolak/gagal. 
    <?php if (!empty($pesanan['catatan'])): ?>
      Alasan: <?= htmlspecialchars($pesanan['catatan']) ?>
    <?php else: ?>
      Customer perlu mengupload bukti baru.
    <?php endif; ?>
    </span>
  </div>
</div>
<?php endif; ?>
            </span>
          </div>
        </div>
        <?php elseif ($statusPembayaran['status'] === 'belum_bayar'): ?>
        <div class="pembayaran-warning">
          <i class="fas fa-exclamation-triangle"></i>
          Pesanan ini belum dibayar - Menunggu pembayaran dari customer
        </div>
        <?php endif; ?>

        <!-- BUKTI PEMBAYARAN -->
        <?php if ($hasBuktiPembayaran): ?>
        <div class="bukti-pembayaran-section">
          <div class="bukti-header">
            <i class="fas fa-receipt"></i>
            <span>Bukti Pembayaran <?= strtoupper($metodePembayaran) ?></span>
          </div>
          <div class="bukti-image-container">
            <img src="<?= $buktiPath ?>" 
                 alt="Bukti Pembayaran" 
                 onclick="previewImage('<?= $buktiPath ?>')"
                 class="bukti-image">
            <div class="bukti-overlay" onclick="previewImage('<?= $buktiPath ?>')">
              <i class="fas fa-search-plus"></i>
            </div>
          </div>
          <div class="bukti-info">
            <small><i class="fas fa-info-circle"></i> Klik gambar untuk memperbesar</small>
          </div>
        </div>
        <?php elseif (strtolower($metodePembayaran) === 'qris' && $statusPembayaran['status'] !== 'gagal'): ?>
          <?php if (empty($pesanan['bukti_pembayaran'])): ?>
          <div class="menunggu-bukti-section">
            <div class="menunggu-bukti">
              <i class="fas fa-clock"></i>
              <span>Menunggu bukti pembayaran QRIS dari customer...</span>
            </div>
          </div>
          <?php else: ?>
          <div class="error-bukti-section">
            <div class="error-bukti">
              <i class="fas fa-exclamation-triangle"></i>
              <span>File bukti tidak ditemukan: <?= htmlspecialchars($pesanan['bukti_pembayaran']) ?></span>
            </div>
          </div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="action-buttons">
          <?php if ($pesanan['status_pesanan'] === 'menunggu'): ?>
            <!-- STATUS: MENUNGGU -->
            <button type="button" class="btn btn-success btn-terima" data-id="<?= $pesanan['id_pesanan'] ?>" data-status="diterima">
                <i class="fas fa-check-circle"></i> Terima Pesanan
            </button>
            <button type="button" class="btn btn-danger btn-tolak" 
    data-id="<?= $pesanan['id_pesanan'] ?>" 
    data-status="ditolak" 
    data-meja="<?= $pesanan['id_meja'] ?>">
                <i class="fas fa-times"></i> Tolak
            </button>
            
          <?php elseif ($pesanan['status_pesanan'] === 'diterima'): ?>
    <!-- STATUS: DITERIMA -->
    <?php 
    $statusBayarOK = in_array($statusPembayaran['status'], ['sudah_bayar', 'lunas']);
    $bisaProses = ($pesanan['metode_bayar'] === 'cash') || $statusBayarOK;
    ?>
    
    <?php if ($bisaProses): ?>
        <button type="button" class="btn btn-warning btn-proses-dapur" data-id="<?= $pesanan['id_pesanan'] ?>" data-status="dimasak">
            <i class="fas fa-utensils"></i> Proses ke Dapur
        </button>
    <?php else: ?>
        <button class="btn btn-secondary" disabled>
            <i class="fas fa-clock"></i> Menunggu Konfirmasi Pembayaran QRIS
        </button>
    <?php endif; ?>
    
    <!-- <button type="button" class="btn btn-danger btn-batalkan" data-id="<?= $pesanan['id_pesanan'] ?>">
        <i class="fas fa-times"></i> Batalkan
    </button> -->
            
          <?php elseif ($pesanan['status_pesanan'] === 'dimasak'): ?>
            <!-- STATUS: DIMASAK -->
            <button type="button" class="btn btn-info btn-disajikan" data-id="<?= $pesanan['id_pesanan'] ?>" data-status="disajikan">
                <i class="fas fa-check-double"></i> Disajikan
            </button>
            <!-- <button type="button" class="btn btn-danger btn-batalkan" data-id="<?= $pesanan['id_pesanan'] ?>">
                <i class="fas fa-times"></i> Batalkan
            </button> -->
            
<?php elseif ($pesanan['status_pesanan'] === 'disajikan'): ?>
    <?php 
    $sudahBayar = in_array($statusPembayaran['status'], ['sudah_bayar', 'lunas']);
    ?>
    
    <?php if ($sudahBayar): ?>
        <button type="button" class="btn btn-success btn-selesai" 
            data-id="<?= $pesanan['id_pesanan'] ?>" 
            data-meja="<?= $pesanan['id_meja'] ?>">
            <i class="fas fa-check-circle"></i> Selesai
        </button>
    <?php else: ?>
        <div class="alert alert-warning" style="padding: 10px; margin: 10px 0; border-radius: 6px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <i class="fas fa-clock"></i> Menunggu konfirmasi pembayaran
            <?php if ($pesanan['metode_bayar'] === 'cash'): ?>
                <br><small style="color: #856404;">Konfirmasi pembayaran cash di kasir untuk menyelesaikan pesanan</small>
            <?php elseif ($pesanan['metode_bayar'] === 'qris'): ?>
                <br><small style="color: #856404;">Konfirmasi pembayaran QRIS terlebih dahulu</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>
            <!-- <button type="button" class="btn btn-danger btn-batalkan" data-id="<?= $pesanan['id_pesanan'] ?>">
                <i class="fas fa-times"></i> Batalkan
            </button> -->
            
          <?php endif; ?>
          
          <!-- TOMBOL LIHAT DETAIL -->
          <button type="button" class="btn btn-secondary btn-detail" data-id="<?= $pesanan['id_pesanan'] ?>">
              <i class="fas fa-eye"></i> Detail
          </button>
          
          <!-- TOMBOL AKSI PEMBAYARAN -->
          <?php if ($statusPembayaran['status'] === 'menunggu_konfirmasi'): ?>
              <button type="button" class="btn btn-success btn-konfirmasi-bayar" data-id="<?= $pesanan['id_pesanan'] ?>">
                  <i class="fas fa-check-circle"></i> Konfirmasi Bayar
              </button>
              <!-- <button type="button" class="btn btn-gagal btn-tolak-bayar" data-id="<?= $pesanan['id_pesanan'] ?>">
                  <i class="fas fa-times-circle"></i> Tolak Bayar
              </button> -->
          <?php elseif ($statusPembayaran['status'] === 'gagal'): ?>
              <button type="button" class="btn btn-reset btn-reset-bayar" data-id="<?= $pesanan['id_pesanan'] ?>">
                  <i class="fas fa-undo"></i> Reset Status
              </button>
          <?php elseif ($statusPembayaran['status'] === 'belum_bayar' && $hasBuktiPembayaran): ?>
              <button type="button" class="btn btn-success btn-konfirmasi-bayar" data-id="<?= $pesanan['id_pesanan'] ?>">
                  <i class="fas fa-money-bill-wave"></i> Konfirmasi Bayar
              </button>
          <?php elseif ($statusPembayaran['status'] === 'belum_bayar' && $pesanan['metode_bayar'] === 'cash'): ?>
              <!-- <button type="button" class="btn btn-success btn-konfirmasi-bayar" data-id="<?= $pesanan['id_pesanan'] ?>">
                  <i class="fas fa-money-bill-wave"></i> Konfirmasi Pembayaran Cash
              </button> -->
          <?php endif; ?>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Tidak Ada Pesanan Aktif</h3>
        <p>Semua pesanan sudah selesai atau belum ada pesanan baru</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- SECTION PESANAN SELESAI HARI INI -->
  <div class="selesai-section">
    <div class="selesai-header">
      <h2><i class="fas fa-check-circle"></i> Pesanan Selesai Hari Ini</h2>
      <button class="toggle-selesai" onclick="toggleSelesai()">
        <i class="fas fa-chevron-down"></i> Tampilkan
      </button>
    </div>
    
    <div class="selesai-grid" id="selesaiGrid">
      <?php if (!empty($pesananSelesai)): ?>
        <?php foreach ($pesananSelesai as $pesanan): ?>
        <div class="pesanan-card-selesai" id="selesai-<?= $pesanan['id_pesanan'] ?>">
          <div class="pesanan-header-selesai">
            <div class="pesanan-id-selesai">#<?= $pesanan['id_pesanan'] ?></div>
            <div class="pesanan-time-selesai">
              <?= date('H:i', strtotime($pesanan['waktu_pesan'])) ?>
            </div>
          </div>
          
          <div class="pesanan-info-selesai">
            <div class="info-item-selesai">
              <i class="fas fa-table"></i> Meja <?= $pesanan['nomor_meja'] ?? '-' ?>
            </div>
            <div class="info-item-selesai">
              <i class="fas fa-utensils"></i> <?= ucfirst($pesanan['jenis_pesanan'] ?? '-') ?>
            </div>
            <div class="info-item-selesai">
              <i class="fas fa-shopping-bag"></i> <?= $pesanan['jumlah_item'] ?> Item
            </div>
          </div>
          
          <div class="total-section-selesai">
            <span class="total-label-selesai">Total</span>
            <span class="total-value-selesai"><?= rupiah($pesanan['total_harga']) ?></span>
          </div>
          
          <div style="margin-top: 8px; text-align: right;">
            <span class="badge-selesai">
              <i class="fas fa-check"></i> SELESAI
            </span>
            <?php if (!empty($pesanan['metode_pembayaran'])): ?>
            <span style="font-size: 11px; color: #64748b; margin-left: 8px;">
              <?= strtoupper($pesanan['metode_pembayaran']) ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-selesai">
          <i class="fas fa-check-circle"></i>
          <h3>Belum Ada Pesanan Selesai Hari Ini</h3>
          <p>Pesanan yang selesai akan muncul di sini</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal pembatalan
<div class="modal" id="batalkanModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Batalkan Pesanan</h3></div>
    <div class="modal-body">
      <label>Alasan Pembatalan</label>
      <textarea id="alasanBatal" rows="3" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;"></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
      <button class="btn btn-danger" onclick="confirmBatalkan()">Ya, Batalkan</button>
    </div>
  </div>
</div> -->

<!-- Modal tolak pembayaran -->
<div class="modal" id="tolakModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Tolak Pembayaran</h3></div>
    <div class="modal-body">
      <label>Alasan Penolakan</label>
      <textarea id="alasanTolak" rows="3" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;" placeholder="Contoh: Bukti pembayaran tidak jelas, nominal tidak sesuai, dll."></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeTolakModal()">Batal</button>
      <button class="btn btn-gagal" onclick="confirmTolakPembayaran()">Ya, Tolak Pembayaran</button>
    </div>
  </div>
</div>

<!-- Modal Preview Gambar -->
<div class="modal-preview" id="previewModal">
  <button class="modal-preview-close" onclick="closePreview()">&times;</button>
  <div class="modal-preview-content">
    <img id="previewImage" src="" alt="Preview Bukti Pembayaran">
  </div>
</div>

<!-- Modal Detail Pesanan -->
<div id="detailModal" class="modal-overlay" style="display: none;">
    <div class="modal-detail">
        <div class="modal-detail-header">
            <h3><i class="fas fa-receipt"></i> Detail Pesanan</h3>
            <button class="close-btn" onclick="closeDetailModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-detail-body" id="detailContent">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Memuat data...
            </div>
        </div>
    </div>
</div>


<!-- Modal Konfirmasi -->
<div id="confirmModal" class="modal-overlay" style="display: none;">
    <div class="modal-confirm">
        <div class="modal-confirm-icon">
            <i class="fas fa-question-circle"></i>
        </div>
        <h3 id="confirmTitle">Konfirmasi</h3>
        <p id="confirmMessage">Apakah Anda yakin?</p>
        <div class="modal-confirm-buttons">
            <button type="button" class="btn-modal btn-cancel" onclick="closeConfirmModal()">
                <i class="fas fa-times"></i> Batal
            </button>
            <button type="button" class="btn-modal btn-confirm" id="confirmButton">
                <i class="fas fa-check"></i> Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<!-- Modal Success -->
<div id="successModal" class="modal-overlay" style="display: none;">
    <div class="modal-success">
        <div class="modal-success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Berhasil!</h3>
        <p id="successMessage">Operasi berhasil dilakukan.</p>
        <button type="button" class="btn-modal btn-ok" onclick="closeSuccessModal()">
            <i class="fas fa-check"></i> OK
        </button>
    </div>
</div>

<!-- Modal Error -->
<div id="errorModal" class="modal-overlay" style="display: none;">
    <div class="modal-error">
        <div class="modal-error-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h3>Gagal!</h3>
        <p id="errorMessage">Terjadi kesalahan.</p>
        <button type="button" class="btn-modal btn-close" onclick="closeErrorModal()">
            <i class="fas fa-times"></i> Tutup
        </button>
    </div>
</div>

<script>
// Variabel global
let currentIdPesanan = null;
let isSelesaiExpanded = false;

// Fungsi untuk menampilkan notifikasi
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Toggle section pesanan selesai
function toggleSelesai() {
    const grid = document.getElementById('selesaiGrid');
    const button = document.querySelector('.toggle-selesai');
    
    if (isSelesaiExpanded) {
        grid.classList.remove('expanded');
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Tampilkan';
    } else {
        grid.classList.add('expanded');
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Sembunyikan';
    }
    
    isSelesaiExpanded = !isSelesaiExpanded;
}

// UPDATE STATUS PESANAN
function updateStatus(orderId, status) {
    console.log('=== UPDATE STATUS CALLED ===');
    console.log('Order ID:', orderId);
    console.log('Status:', status);
    
    if (!orderId || orderId === '' || orderId === 'undefined') {
        alert('Order ID tidak valid!');
        return;
    }
    
    if (!confirm(`Apakah Anda yakin ingin mengubah status pesanan menjadi "${status}"?`)) {
        return;
    }
    
    const requestData = {
        id_pesanan: orderId,
        status: status
    };
    
    console.log('Sending data:', requestData);
    
    fetch('../include/update_status_pesanan.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
            
            if (data.success) {
                alert('Status pesanan berhasil diupdate!');
                location.reload();
            } else {
                alert('Gagal mengupdate status: ' + (data.message || 'Terjadi kesalahan'));
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('Error: Response tidak valid');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Terjadi kesalahan saat mengupdate status');
    });
}

// TANDAI SELESAI
function tandaiSelesai(id) {
    console.log('🎯 TANDAI SELESAI:', id);
    
    if(!confirm('Tandai pesanan sebagai SELESAI? Pesanan akan dipindahkan ke daftar selesai.')) return;
    
    let button = event?.target?.closest?.('button');
    if (!button) {
        button = document.querySelector(`[onclick*="tandaiSelesai(${id})"]`);
    }
    
    const originalText = button?.innerHTML || '';
    
    if (button) {
        button.innerHTML = '<div class="loading"></div> Menyelesaikan...';
        button.disabled = true;
    }
    
    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append('aksi', 'selesai');
    
    fetch('aksi_pesanan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if(res.success) {
            showNotification('✅ ' + res.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('❌ ' + res.message, 'error');
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
    })
    .catch(e => {
        console.error('💥 Error:', e);
        showNotification('❌ Error: Gagal menyelesaikan pesanan', 'error');
        if (button) {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

// KONFIRMASI PEMBAYARAN
function konfirmasiPembayaran(id) {
    console.log('💰 KONFIRMASI PEMBAYARAN:', id);
    
    if(!confirm('Konfirmasi bahwa pembayaran sudah diterima? Status akan berubah menjadi LUNAS dan pesanan akan diterima.')) return;
    
    let button = event?.target?.closest?.('button');
    if (!button) {
        button = document.querySelector(`[onclick*="konfirmasiPembayaran(${id})"]`);
    }
    
    const originalText = button?.innerHTML || '';
    
    if (button) {
        button.innerHTML = '<div class="loading"></div> Mengkonfirmasi...';
        button.disabled = true;
    }
    
    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append('aksi', 'konfirmasi_bayar');
    
    fetch('aksi_pesanan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if(res.success) {
            showNotification('✅ ' + res.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('❌ ' + res.message, 'error');
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
    })
    .catch(e => {
        console.error('💥 Payment error:', e);
        showNotification('❌ Error: Gagal mengkonfirmasi pembayaran', 'error');
        if (button) {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

// TOLAK PEMBAYARAN
function tolakPembayaran(id) {
    currentIdPesanan = id;
    document.getElementById('tolakModal').classList.add('active');
    document.getElementById('alasanTolak').focus();
}

function closeTolakModal() {
    document.getElementById('tolakModal').classList.remove('active');
    document.getElementById('alasanTolak').value = '';
    currentIdPesanan = null;
}

function confirmTolakPembayaran() {
    const alasan = document.getElementById('alasanTolak').value.trim();
    
    if (!alasan) {
        alert('Harap masukkan alasan penolakan pembayaran.');
        return;
    }
    
    if(!confirm('Tolak pembayaran ini? Status akan berubah menjadi GAGAL.')) return;
    
    const button = document.querySelector('#tolakModal .btn-gagal');
    const originalText = button.innerHTML;
    button.innerHTML = '<div class="loading"></div> Memproses...';
    button.disabled = true;
    
    const formData = new URLSearchParams();
    formData.append('id', currentIdPesanan);
    formData.append('aksi', 'tolak_bayar');
    formData.append('alasan', alasan);
    
    fetch('aksi_pesanan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if(res.success) {
            showNotification('✅ ' + res.message, 'success');
            closeTolakModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('❌ ' + res.message, 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(e => {
        console.error('Error:', e);
        showNotification('❌ Error: Gagal menolak pembayaran', 'error');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// RESET PEMBAYARAN
function resetPembayaran(id) {
    if(!confirm('Reset status pembayaran? Status akan dikembalikan ke BELUM BAYAR.')) return;
    
    let button = event?.target?.closest?.('button');
    const originalText = button?.innerHTML || '';
    
    if (button) {
        button.innerHTML = '<div class="loading"></div> Memproses...';
        button.disabled = true;
    }
    
    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append('aksi', 'reset_bayar');
    
    fetch('aksi_pesanan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if(res.success) {
            showNotification('✅ ' + res.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('❌ ' + res.message, 'error');
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
    })
    .catch(e => {
        console.error('Error:', e);
        showNotification('❌ Error: Gagal reset pembayaran', 'error');
        if (button) {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

// BATALKAN PESANAN
// function openBatalkanModal(id) {
//     currentIdPesanan = id;
//     document.getElementById('batalkanModal').classList.add('active');
//     document.getElementById('alasanBatal').focus();
// }

// function closeModal() {
//     document.getElementById('batalkanModal').classList.remove('active');
//     document.getElementById('alasanBatal').value = '';
//     currentIdPesanan = null;
// }

// function confirmBatalkan() {
//     const alasan = document.getElementById('alasanBatal').value.trim();
    
//     if(!confirm('Batalkan pesanan ini? Tindakan ini tidak dapat dibatalkan.')) return;
    
//     const button = document.querySelector('.modal-footer .btn-danger');
//     const originalText = button.innerHTML;
//     button.innerHTML = '<div class="loading"></div> Membatalkan...';
//     button.disabled = true;
    
//     const formData = new URLSearchParams();
//     formData.append('id', currentIdPesanan);
//     formData.append('aksi', 'batal');
//     formData.append('alasan', alasan || 'Pesanan dibatalkan oleh kasir');
    
//     fetch('aksi_pesanan.php', {
//         method: 'POST',
//         headers: {'Content-Type': 'application/x-www-form-urlencoded'},
//         body: formData
//     })
//     .then(response => response.json())
//     .then(res => {
//         if(res.success) {
//             showNotification('✅ ' + res.message, 'success');
//             closeModal();
//             setTimeout(() => location.reload(), 1000);
//         } else {
//             showNotification('❌ ' + res.message, 'error');
//             button.innerHTML = originalText;
//             button.disabled = false;
//         }
//     })
//     .catch(e => {
//         console.error('💥 Cancel error:', e);
//         showNotification('❌ Error: Gagal membatalkan pesanan', 'error');
//         button.innerHTML = originalText;
//         button.disabled = false;
//     });
// }

// PREVIEW GAMBAR
function previewImage(src) {
    document.getElementById('previewImage').src = src;
    document.getElementById('previewModal').classList.add('active');
}

function closePreview() {
    document.getElementById('previewModal').classList.remove('active');
}

// LIHAT DETAIL (Placeholder)
function lihatDetail(orderId) {
    console.log('Lihat detail pesanan:', orderId);
    
    // Tampilkan modal dengan loading
    document.getElementById('detailModal').style.display = 'flex';
    document.getElementById('detailContent').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
    
    // Fetch detail pesanan
    fetch('../include/get_detail_pesanan.php?id=' + orderId)
        .then(response => response.text())
        .then(text => {
            console.log('Response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showDetailContent(data.pesanan);
                } else {
                    document.getElementById('detailContent').innerHTML = 
                        '<div class="loading"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                }
            } catch (e) {
                console.error('Parse error:', e);
                document.getElementById('detailContent').innerHTML = 
                    '<div class="loading"><i class="fas fa-exclamation-circle"></i> Error memuat data</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detailContent').innerHTML = 
                '<div class="loading"><i class="fas fa-exclamation-circle"></i> Terjadi kesalahan</div>';
        });
}

function showDetailContent(pesanan) {
    // Format status
    let statusClass = 'status-' + pesanan.status_pesanan.toLowerCase();
    let statusText = pesanan.status_pesanan.toUpperCase().replace('_', ' ');
    
    // Format status pembayaran
    let statusBayar = pesanan.status_pembayaran || 'belum_bayar';
    let statusBayarText = statusBayar.replace('_', ' ').toUpperCase();
    
    // Build items table
    let itemsHtml = '';
    if (pesanan.items && pesanan.items.length > 0) {
        itemsHtml = `
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th style="text-align: center;">Jumlah</th>
                        <th style="text-align: right;">Harga</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${pesanan.items.map(item => `
                        <tr>
                            <td>${item.nama_menu}</td>
                            <td style="text-align: center;">${item.jumlah}x</td>
                            <td style="text-align: right;">Rp ${parseInt(item.harga_satuan).toLocaleString('id-ID')}</td>
                            <td style="text-align: right;">Rp ${parseInt(item.subtotal).toLocaleString('id-ID')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            <div class="total-row">
                <span class="label">Total Pembayaran</span>
                <span class="value">Rp ${parseInt(pesanan.total_harga).toLocaleString('id-ID')}</span>
            </div>
        `;
    } else {
        itemsHtml = '<p style="text-align: center; color: #999;">Tidak ada item</p>';
    }
    
    // Bukti pembayaran
    let buktiHtml = '';
    if (pesanan.bukti_pembayaran) {
        buktiHtml = `
            <div class="bukti-bayar">
                <img src="../../assets/uploads/${pesanan.bukti_pembayaran}" 
                     alt="Bukti Pembayaran" 
                     onclick="window.open(this.src, '_blank')">
            </div>
        `;
    }
    
    // Build content
    let content = `
        <div class="detail-section">
            <h4><i class="fas fa-info-circle"></i> Informasi Pesanan</h4>
            <div class="detail-info">
                <div class="info-item">
                    <span class="info-label">ID Pesanan</span>
                    <span class="info-value">${pesanan.id_pesanan}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Nomor Meja</span>
                    <span class="info-value">${pesanan.nomor_meja}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Waktu Pesan</span>
                    <span class="info-value">${pesanan.waktu_pesan}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status Pesanan</span>
                    <span class="info-value">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </span>
                </div>
            </div>
            ${pesanan.catatan ? `
                <div style="margin-top: 15px;">
                    <span class="info-label">Catatan</span>
                    <p style="margin: 5px 0 0 0; color: #666;">${pesanan.catatan}</p>
                </div>
            ` : ''}
        </div>
        
        <div class="detail-section">
            <h4><i class="fas fa-utensils"></i> Daftar Menu</h4>
            ${itemsHtml}
        </div>
        
        <div class="detail-section">
            <h4><i class="fas fa-credit-card"></i> Informasi Pembayaran</h4>
            <div class="payment-info">
                <div class="info-row">
                    <span class="info-label">Metode Pembayaran</span>
                    <span class="info-value">${pesanan.metode_bayar.toUpperCase()}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status Pembayaran</span>
                    <span class="info-value">${statusBayarText}</span>
                </div>
            </div>
            ${buktiHtml}
        </div>
    `;
    
    document.getElementById('detailContent').innerHTML = content;
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'detailModal') {
        closeDetailModal();
    }
});

// Cek jika tidak ada pesanan aktif lagi
function checkEmptyState() {
    const pesananGrid = document.getElementById('pesananGrid');
    const visibleCards = pesananGrid.querySelectorAll('.pesanan-card:not([style*="display: none"])');
    
    if (visibleCards.length === 0) {
        const existingEmptyState = pesananGrid.querySelector('.empty-state');
        if (!existingEmptyState) {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
                <i class="fas fa-check-circle" style="color:#10b981;"></i>
                <h3>Semua Pesanan Selesai</h3>
                <p>Tidak ada pesanan aktif. Semua pesanan hari ini sudah selesai!</p>
            `;
            pesananGrid.appendChild(emptyState);
        }
    } else {
        const emptyState = pesananGrid.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }
    }
}

// Tab filtering
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function(){
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const status = this.dataset.status;
        
        document.querySelectorAll('.pesanan-card').forEach(card => {
            if (status === 'semua') {
                card.style.display = 'block';
            } else {
                card.style.display = card.dataset.status === status ? 'block' : 'none';
            }
        });
        
        checkEmptyState();
    });
});

// Event listener untuk modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeTolakModal();
        closePreview();
    }
});

// Close modal ketika klik di luar
// document.getElementById('batalkanModal').addEventListener('click', function(e) {
//     if (e.target === this) {
//         closeModal();
//     }
// });

document.getElementById('tolakModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTolakModal();
    }
});

document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePreview();
    }
});

// Fix event handlers
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM loaded, fixing event handlers...');
    
    setTimeout(() => {
        // Fix tombol updateStatus
        document.querySelectorAll('button[onclick*="updateStatus"]').forEach(button => {
            const originalOnclick = button.getAttribute('onclick');
            button.removeAttribute('onclick');
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const match = originalOnclick.match(/updateStatus\((\d+),\s*'([^']+)'\)/);
                if (match) {
                    const id = parseInt(match[1]);
                    const status = match[2];
                    updateStatus(id, status);
                }
            });
        });
        
        // Fix tombol lainnya
        ['tandaiSelesai', 'konfirmasiPembayaran', 'tolakPembayaran', 'resetPembayaran'].forEach(action => {
            document.querySelectorAll(`button[onclick*="${action}"]`).forEach(button => {
                const originalOnclick = button.getAttribute('onclick');
                button.removeAttribute('onclick');
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const match = originalOnclick.match(new RegExp(`${action}\\((\\d+)\\)`));
                    if (match) {
                        const id = parseInt(match[1]);
                        window[action](id);
                    }
                });
            });
        });
        
        console.log('✅ All button event handlers fixed');
    }, 500);
});

// Auto refresh data setiap 2 menit
// setInterval(() => {
//     fetch('get_counter_selesai.php')
//         .then(r => r.json())
//         .then(data => {
//             if (data.success) {
//                 const currentCount = parseInt(document.getElementById('countSelesai').textContent);
//                 if (currentCount !== data.count) {
//                     document.getElementById('countSelesai').textContent = data.count;
//                     document.getElementById('countSelesai').classList.add('counter-update');
//                     setTimeout(() => {
//                         document.getElementById('countSelesai').classList.remove('counter-update');
//                     }, 500);
//                 }
//             }
//         })
//         .catch(e => console.error('Error refreshing counter:', e));
// }, 120000);

// Attach event untuk semua button terima
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, attaching events...');
    
    const buttons = document.querySelectorAll('.btn-terima');
    console.log('Found buttons:', buttons.length);
    
    buttons.forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            console.log('Button clicked!');
            console.log('Order ID:', orderId);
            console.log('Status:', status);
            
            updateStatus(orderId, status);
        });
    });
});

// Fungsi updateStatus (pastikan ada)
function updateStatus(orderId, status) {
    console.log('=== UPDATE STATUS CALLED ===');
    console.log('Order ID:', orderId);
    console.log('Status:', status);
    
    if (!confirm(`Apakah Anda yakin ingin mengubah status pesanan menjadi "${status}"?`)) {
        return;
    }
    
    fetch('../include/update_status_pesanan.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id_pesanan: orderId,
            status: status
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Status pesanan berhasil diupdate!');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch (e) {
            console.error('Parse error:', e);
            alert('Error: Response tidak valid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

// Button tolak
document.querySelectorAll('.btn-tolak').forEach(function(button) {
    button.addEventListener('click', function() {
        updateStatus(this.getAttribute('data-id'), this.getAttribute('data-status'));
    });
});


document.addEventListener('DOMContentLoaded', function() {
    
    // Konfirmasi Pembayaran
    document.querySelectorAll('.btn-konfirmasi-bayar').forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            console.log('Konfirmasi pembayaran:', orderId);
            konfirmasiPembayaran(orderId);
        });
    });
    
    // Tolak Pembayaran
    document.querySelectorAll('.btn-tolak-bayar').forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            console.log('Tolak pembayaran:', orderId);
            tolakPembayaran(orderId);
        });
    });
    
    // Reset Pembayaran
    document.querySelectorAll('.btn-reset-bayar').forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            console.log('Reset pembayaran:', orderId);
            resetPembayaran(orderId);
        });
    });
});

// Fungsi konfirmasi pembayaran
function konfirmasiPembayaran(orderId) {
    console.log('=== KONFIRMASI PEMBAYARAN ===');
    console.log('Order ID:', orderId);
    
    if (!confirm('Apakah Anda yakin ingin mengkonfirmasi pembayaran ini?')) {
        return;
    }
    
    fetch('../include/konfirmasi_pembayaran.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id_pesanan: orderId
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Pembayaran berhasil dikonfirmasi!');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch (e) {
            console.error('Parse error:', e);
            alert('Error: Response tidak valid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

function tolakPembayaran(orderId) {
    console.log('=== TOLAK PEMBAYARAN ===');
    console.log('Order ID:', orderId);
    
    if (!confirm('Apakah Anda yakin ingin menolak pembayaran ini?')) {
        return;
    }
    
    fetch('../include/tolak_pembayaran.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id_pesanan: orderId
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Pembayaran ditolak!');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch (e) {
            console.error('Parse error:', e);
            alert('Error: Response tidak valid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

function resetPembayaran(orderId) {
    console.log('=== RESET PEMBAYARAN ===');
    console.log('Order ID:', orderId);
    
    if (!confirm('Apakah Anda yakin ingin mereset status pembayaran?')) {
        return;
    }
    
    fetch('../include/reset_pembayaran.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id_pesanan: orderId
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Status pembayaran berhasil direset!');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch (e) {
            console.error('Parse error:', e);
            alert('Error: Response tidak valid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    
    // ... event listener lain yang sudah ada ...
    
    // Proses ke Dapur
    document.querySelectorAll('.btn-proses-dapur').forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            console.log('Proses ke dapur:', orderId);
            updateStatus(orderId, status);
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    
    // ... event listener lain ...
    
    // Disajikan
    document.querySelectorAll('.btn-disajikan').forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            console.log('Disajikan:', orderId);
            updateStatus(orderId, status);
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    
    // ... event listener lain ...
    
    // Tandai Selesai
    document.querySelectorAll('.btn-selesai').forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const mejaId = this.getAttribute('data-meja');
            console.log('Tandai selesai:', orderId, 'Meja:', mejaId);
            tandaiSelesai(orderId, mejaId);
        });
    });
});

// Fungsi tandai selesai
function tandaiSelesai(orderId, mejaId) {
    console.log('=== TANDAI SELESAI ===');
    console.log('Order ID:', orderId);
    console.log('Meja ID:', mejaId);
    
    if (!confirm('Apakah Anda yakin ingin menandai pesanan ini selesai? Status meja akan berubah menjadi kosong.')) {
        return;
    }
    
    fetch('../include/tandai_selesai.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id_pesanan: orderId,
            id_meja: mejaId
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Pesanan selesai! Meja sudah dikosongkan.');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch (e) {
            console.error('Parse error:', e);
            alert('Error: Response tidak valid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Button Tolak
    document.querySelectorAll('.btn-tolak').forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const mejaId = this.getAttribute('data-meja');
            const status = this.getAttribute('data-status');
            
            console.log('Tolak pesanan - Order:', orderId, 'Meja:', mejaId);
            tolakPesanan(orderId, mejaId, status);
        });
    });
    
    // ... event listener lainnya ...
});

// Fungsi tolak pesanan
function tolakPesanan(orderId, mejaId, status) {
    console.log('=== TOLAK PESANAN ===');
    console.log('Order ID:', orderId);
    console.log('Meja ID:', mejaId);
    
    if (!confirm('Apakah Anda yakin ingin menolak pesanan ini? Meja akan dikosongkan.')) {
        return;
    }
    
    fetch('../include/tolak_pesanan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_pesanan: orderId,
            id_meja: mejaId,
            status: status
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Pesanan ditolak! Meja sudah dikosongkan.');
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch (e) {
            console.error('Parse error:', e);
            alert('Error: Response tidak valid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    
    console.log('DOM Loaded - Attaching events...');

    // Button Detail - TAMBAHKAN INI
    const detailButtons = document.querySelectorAll('.btn-detail');
    console.log('Found detail buttons:', detailButtons.length);
    
    detailButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            console.log('Detail button clicked!');
            const orderId = this.getAttribute('data-id');
            console.log('Order ID:', orderId);
            lihatDetail(orderId);
        });
    });
});


// alert
function showConfirm(message, onConfirm, title = 'Konfirmasi') {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmModal').style.display = 'flex';
    
    document.getElementById('confirmButton').onclick = function() {
        closeConfirmModal();
        onConfirm();
    };
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function showSuccess(message) {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModal').style.display = 'flex';
}

function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
    location.reload();
}

function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorModal').style.display = 'flex';
}

function closeErrorModal() {
    document.getElementById('errorModal').style.display = 'none';
}

// Close modal ketika klik di luar
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeConfirmModal();
        closeErrorModal();
    }
});

// ========== UPDATE SEMUA FUNGSI ==========

function updateStatus(orderId, status) {
    showConfirm(
        `Apakah Anda yakin ingin mengubah status pesanan menjadi "${status}"?`,
        function() {
            fetch('../include/update_status_pesanan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_pesanan: orderId,
                    status: status
                })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showSuccess('Status pesanan berhasil diupdate!');
                    } else {
                        showError('Gagal: ' + data.message);
                    }
                } catch (e) {
                    showError('Error: Response tidak valid');
                }
            })
            .catch(error => {
                showError('Terjadi kesalahan');
            });
        }
    );
}

function tolakPesanan(orderId, mejaId, status) {
    showConfirm(
        'Apakah Anda yakin ingin menolak pesanan ini? Data pesanan akan dihapus dan meja dikosongkan.',
        function() {
            fetch('../include/tolak_pesanan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_pesanan: orderId,
                    id_meja: mejaId,
                    status: status
                })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showSuccess('Pesanan ditolak dan data dihapus. Meja sudah dikosongkan.');
                    } else {
                        showError('Gagal: ' + data.message);
                    }
                } catch (e) {
                    showError('Error: Response tidak valid');
                }
            })
            .catch(error => {
                showError('Terjadi kesalahan');
            });
        },
        'Tolak Pesanan'
    );
}

function tandaiSelesai(orderId, mejaId) {
    showConfirm(
        'Apakah Anda yakin pesanan sudah selesai? Meja akan dikosongkan.',
        function() {
            fetch('../include/tandai_selesai.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_pesanan: orderId,
                    id_meja: mejaId
                })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showSuccess('Pesanan selesai! Meja sudah dikosongkan.');
                    } else {
                        showError('Gagal: ' + data.message);
                    }
                } catch (e) {
                    showError('Error: Response tidak valid');
                }
            })
            .catch(error => {
                showError('Terjadi kesalahan');
            });
        },
        'Tandai Selesai'
    );
}

function konfirmasiPembayaran(orderId) {
    showConfirm(
        'Apakah Anda yakin pembayaran sudah diterima?',
        function() {
            fetch('../include/konfirmasi_pembayaran.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_pesanan: orderId
                })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showSuccess('Pembayaran berhasil dikonfirmasi!');
                    } else {
                        showError('Gagal: ' + data.message);
                    }
                } catch (e) {
                    showError('Error: Response tidak valid');
                }
            })
            .catch(error => {
                showError('Terjadi kesalahan');
            });
        },
        'Konfirmasi Pembayaran'
    );
}

function tolakPembayaran(orderId) {
    showConfirm(
        'Apakah Anda yakin ingin menolak pembayaran ini?',
        function() {
            fetch('../include/tolak_pembayaran.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_pesanan: orderId
                })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showSuccess('Pembayaran ditolak!');
                    } else {
                        showError('Gagal: ' + data.message);
                    }
                } catch (e) {
                    showError('Error: Response tidak valid');
                }
            })
            .catch(error => {
                showError('Terjadi kesalahan');
            });
        },
        'Tolak Pembayaran'
    );
}

function resetPembayaran(orderId) {
    showConfirm(
        'Status pembayaran akan direset. Lanjutkan?',
        function() {
            fetch('../include/reset_pembayaran.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_pesanan: orderId
                })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showSuccess('Status pembayaran berhasil direset!');
                    } else {
                        showError('Gagal: ' + data.message);
                    }
                } catch (e) {
                    showError('Error: Response tidak valid');
                }
            })
            .catch(error => {
                showError('Terjadi kesalahan');
            });
        },
        'Reset Status Pembayaran'
    );
}
</script>
</body>
</html>