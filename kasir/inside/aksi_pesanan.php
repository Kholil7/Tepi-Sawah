<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();

require_once '../../database/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

class PesananAksi {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // FUNGSI KONFIRMASI PEMBAYARAN - DIPERBAIKI
    public function konfirmasiPembayaran($id) {
        $id = intval($id);
        
        // 1. Cek apakah sudah ada record pembayaran
        $check_sql = "SELECT id_pembayaran, bukti_pembayaran, metode FROM pembayaran WHERE id_pesanan = $id";
        $check_result = $this->conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            $metode = !empty($row['metode']) ? $row['metode'] : 'qris';
            
            // Update existing record - set status menjadi 'sudah_bayar'
            $sql_pembayaran = "UPDATE pembayaran SET status = 'sudah_bayar', waktu_pembayaran = NOW() WHERE id_pesanan = $id";
        } else {
            // Insert new record dengan status 'sudah_bayar'
            $metode = 'qris';
            $sql_pembayaran = "INSERT INTO pembayaran (id_pesanan, status, waktu_pembayaran, metode) 
                              VALUES ($id, 'sudah_bayar', NOW(), '$metode')";
        }
        
        // 2. Eksekusi update/insert pembayaran
        if ($this->conn->query($sql_pembayaran)) {
            // 3. Update status pesanan menjadi 'diterima' jika masih 'menunggu'
            $sql_pesanan = "UPDATE pesanan SET status_pesanan = 'diterima' WHERE id_pesanan = $id AND status_pesanan = 'menunggu'";
            $this->conn->query($sql_pesanan);
            
            return ['success' => true, 'message' => 'Pembayaran berhasil dikonfirmasi dan status diupdate'];
        } else {
            error_log("Error konfirmasi pembayaran: " . $this->conn->error);
            return ['success' => false, 'message' => 'Gagal konfirmasi pembayaran: ' . $this->conn->error];
        }
    }
    
    // FUNGSI BARU: Tolak pembayaran (status gagal) - DIPERBAIKI
    public function tolakPembayaran($id, $alasan) {
        $id = intval($id);
        $alasan = $this->conn->real_escape_string($alasan);
        
        // 1. Update catatan di tabel pesanan
        $sql_pesanan = "UPDATE pesanan SET catatan = '$alasan' WHERE id_pesanan = $id";
        $this->conn->query($sql_pesanan);
        
        // 2. Cek apakah sudah ada record pembayaran
        $check_sql = "SELECT id_pembayaran FROM pembayaran WHERE id_pesanan = $id";
        $check_result = $this->conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing record - set status menjadi 'gagal'
            $sql_pembayaran = "UPDATE pembayaran SET status = 'gagal' WHERE id_pesanan = $id";
        } else {
            // Insert new record dengan status 'gagal'
            $sql_pembayaran = "INSERT INTO pembayaran (id_pesanan, status, metode) 
                              VALUES ($id, 'gagal', 'qris')";
        }
        
        if ($this->conn->query($sql_pembayaran)) {
            return ['success' => true, 'message' => 'Pembayaran ditolak dan status diupdate menjadi gagal'];
        } else {
            error_log("Error tolak pembayaran: " . $this->conn->error);
            return ['success' => false, 'message' => 'Gagal menolak pembayaran: ' . $this->conn->error];
        }
    }
    
    // FUNGSI BARU: Reset status pembayaran (jika ada kesalahan) - DIPERBAIKI
    public function resetPembayaran($id) {
        $id = intval($id);
        
        // Hapus catatan di tabel pesanan
        $sql_pesanan = "UPDATE pesanan SET catatan = NULL WHERE id_pesanan = $id";
        $this->conn->query($sql_pesanan);
        
        $sql = "UPDATE pembayaran SET status = 'belum_bayar' WHERE id_pesanan = $id";
        
        if ($this->conn->query($sql)) {
            return ['success' => true, 'message' => 'Status pembayaran berhasil direset'];
        } else {
            error_log("Error reset pembayaran: " . $this->conn->error);
            return ['success' => false, 'message' => 'Gagal reset pembayaran: ' . $this->conn->error];
        }
    }
    
    public function updateStatus($id, $status) {
        $id = intval($id);
        $status = $this->conn->real_escape_string($status);
        
        $sql = "UPDATE pesanan SET status_pesanan = '$status' WHERE id_pesanan = $id";
        
        if ($this->conn->query($sql)) {
            return ['success' => true, 'message' => 'Status pesanan berhasil diupdate'];
        } else {
            error_log("Error update status: " . $this->conn->error);
            return ['success' => false, 'message' => 'Gagal update status: ' . $this->conn->error];
        }
    }
    
    public function batalkanPesanan($id, $alasan) {
        $id = intval($id);
        $alasan = $this->conn->real_escape_string($alasan);
        
        // Update status pesanan dan catatan
        $sql_pesanan = "UPDATE pesanan SET status_pesanan = 'dibatalkan', catatan = '$alasan' WHERE id_pesanan = $id";
        
        // Update status pembayaran jika ada
        $sql_pembayaran = "UPDATE pembayaran SET status = 'gagal' WHERE id_pesanan = $id";
        $this->conn->query($sql_pembayaran);
        
        if ($this->conn->query($sql_pesanan)) {
            return ['success' => true, 'message' => 'Pesanan berhasil dibatalkan'];
        } else {
            error_log("Error batalkan pesanan: " . $this->conn->error);
            return ['success' => false, 'message' => 'Gagal membatalkan pesanan: ' . $this->conn->error];
        }
    }
    
    public function selesaikanPesanan($id) {
        $id = intval($id);
        
        $sql = "UPDATE pesanan SET status_pesanan = 'selesai' WHERE id_pesanan = $id";
        
        if ($this->conn->query($sql)) {
            return ['success' => true, 'message' => 'Pesanan berhasil diselesaikan'];
        } else {
            error_log("Error selesaikan pesanan: " . $this->conn->error);
            return ['success' => false, 'message' => 'Gagal menyelesaikan pesanan: ' . $this->conn->error];
        }
    }
}

$aksi = new PesananAksi($conn);
$response = ['success' => false, 'message' => 'Aksi tidak dikenali'];

try {
    if (!isset($_POST['aksi'])) {
        throw new Exception('Aksi tidak ditentukan');
    }
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id === 0) {
        throw new Exception('ID pesanan tidak valid');
    }
    
    switch ($_POST['aksi']) {
        case 'update':
            if (!isset($_POST['status'])) {
                throw new Exception('Status tidak ditentukan');
            }
            $response = $aksi->updateStatus($id, $_POST['status']);
            break;
            
        case 'konfirmasi_bayar':
            $response = $aksi->konfirmasiPembayaran($id);
            break;
            
        case 'tolak_bayar':
            $alasan = isset($_POST['alasan']) ? $_POST['alasan'] : 'Bukti pembayaran tidak valid';
            $response = $aksi->tolakPembayaran($id, $alasan);
            break;
            
        case 'reset_bayar':
            $response = $aksi->resetPembayaran($id);
            break;
            
        case 'batal':
            $alasan = isset($_POST['alasan']) ? $_POST['alasan'] : 'Pesanan dibatalkan oleh kasir';
            $response = $aksi->batalkanPesanan($id, $alasan);
            break;
            
        case 'selesai':
            $response = $aksi->selesaikanPesanan($id);
            break;
            
        default:
            throw new Exception('Aksi tidak dikenali: ' . $_POST['aksi']);
    }
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>