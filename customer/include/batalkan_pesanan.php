<?php
require '../../database/connect.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan = isset($_POST['id_pesanan']) ? $_POST['id_pesanan'] : '';
    $kode = isset($_POST['kode']) ? $_POST['kode'] : '';
    
    error_log("Batalkan pesanan - ID: $id_pesanan, Kode: $kode");
    
    if(empty($id_pesanan) || empty($kode)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        $query_meja = "SELECT id_meja FROM meja WHERE kode_unik = ?";
        $stmt_meja = $conn->prepare($query_meja);
        $stmt_meja->bind_param('s', $kode);
        $stmt_meja->execute();
        $result_meja = $stmt_meja->get_result();
        
        if($result_meja->num_rows === 0) {
            throw new Exception('Meja tidak ditemukan');
        }
        
        $meja = $result_meja->fetch_assoc();
        $id_meja = $meja['id_meja'];
        $stmt_meja->close();
        
        $query_detail = "DELETE FROM detail_pesanan WHERE id_pesanan = ?";
        $stmt_detail = $conn->prepare($query_detail);
        
        if (!$stmt_detail) {
            throw new Exception('Prepare statement detail pesanan gagal: ' . $conn->error);
        }
        
        $stmt_detail->bind_param('s', $id_pesanan);
        
        if (!$stmt_detail->execute()) {
            throw new Exception('Hapus detail pesanan gagal: ' . $stmt_detail->error);
        }
        
        $stmt_detail->close();
        error_log("Detail pesanan dihapus untuk: " . $id_pesanan);
        
        $query_pembayaran = "DELETE FROM pembayaran WHERE id_pesanan = ?";
        $stmt_pembayaran = $conn->prepare($query_pembayaran);
        
        if (!$stmt_pembayaran) {
            throw new Exception('Prepare statement pembayaran gagal: ' . $conn->error);
        }
        
        $stmt_pembayaran->bind_param('s', $id_pesanan);
        
        if (!$stmt_pembayaran->execute()) {
            throw new Exception('Hapus pembayaran gagal: ' . $stmt_pembayaran->error);
        }
        
        $stmt_pembayaran->close();
        error_log("Pembayaran dihapus untuk: " . $id_pesanan);
        
        $query_pesanan = "DELETE FROM pesanan WHERE id_pesanan = ?";
        $stmt_pesanan = $conn->prepare($query_pesanan);
        
        if (!$stmt_pesanan) {
            throw new Exception('Prepare statement pesanan gagal: ' . $conn->error);
        }
        
        $stmt_pesanan->bind_param('s', $id_pesanan);
        
        if (!$stmt_pesanan->execute()) {
            throw new Exception('Hapus pesanan gagal: ' . $stmt_pesanan->error);
        }
        
        $stmt_pesanan->close();
        error_log("Pesanan dihapus: " . $id_pesanan);
        
        $query_update_meja = "UPDATE meja SET status_meja = 'kosong' WHERE id_meja = ?";
        $stmt_update_meja = $conn->prepare($query_update_meja);
        
        if (!$stmt_update_meja) {
            throw new Exception('Prepare statement meja gagal: ' . $conn->error);
        }
        
        $stmt_update_meja->bind_param('s', $id_meja);
        
        if (!$stmt_update_meja->execute()) {
            throw new Exception('Update meja gagal: ' . $stmt_update_meja->error);
        }
        
        $stmt_update_meja->close();
        error_log("Meja dikosongkan: " . $id_meja);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pesanan berhasil dibatalkan dan meja dikosongkan'
        ]);
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
        }
        
        error_log("Batalkan pesanan error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid']);
}
?>