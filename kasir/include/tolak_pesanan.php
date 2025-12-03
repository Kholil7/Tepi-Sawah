<?php
require '../../database/connect.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$json = file_get_contents('php://input');
$data = json_decode($json, true);

error_log("Tolak pesanan - Received data: " . print_r($data, true));

if (!isset($data['id_pesanan']) || empty($data['id_pesanan'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID Pesanan tidak valid'
    ]);
    exit;
}

if (!isset($data['id_meja']) || empty($data['id_meja'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID Meja tidak valid'
    ]);
    exit;
}

$id_pesanan = $data['id_pesanan'];
$id_meja = $data['id_meja'];

try {
    $conn->begin_transaction();
    
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
    
    $query_meja = "UPDATE meja SET status_meja = 'kosong' WHERE id_meja = ?";
    $stmt_meja = $conn->prepare($query_meja);
    
    if (!$stmt_meja) {
        throw new Exception('Prepare statement meja gagal: ' . $conn->error);
    }
    
    $stmt_meja->bind_param('s', $id_meja);
    
    if (!$stmt_meja->execute()) {
        throw new Exception('Update meja gagal: ' . $stmt_meja->error);
    }
    
    $stmt_meja->close();
    error_log("Meja dikosongkan: " . $id_meja);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pesanan ditolak dan data dihapus. Meja sudah dikosongkan.'
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    error_log("Tolak pesanan error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>