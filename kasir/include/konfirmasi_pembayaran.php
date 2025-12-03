<?php
require '../../database/connect.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['id_pesanan']) || empty($data['id_pesanan'])) {
    http_response_code(400); 
    echo json_encode([
        'success' => false,
        'message' => 'ID Pesanan tidak valid'
    ]);
    exit;
}

$id_pesanan = $data['id_pesanan'];
$status_pembayaran_lunas = 'sudah_bayar'; 
$status_pesanan_diterima = 'diterima';

$conn->begin_transaction();

try {
    $query_pembayaran = "UPDATE pembayaran SET status = ? WHERE id_pesanan = ?";
    $stmt_pembayaran = $conn->prepare($query_pembayaran);
    
    if (!$stmt_pembayaran) {
        throw new Exception('Prepare pembayaran gagal: ' . $conn->error);
    }
    
    $stmt_pembayaran->bind_param('ss', $status_pembayaran_lunas, $id_pesanan);
    
    if (!$stmt_pembayaran->execute()) {
        throw new Exception('Eksekusi update pembayaran gagal: ' . $stmt_pembayaran->error);
    }
    
    $stmt_pembayaran->close();

    $query_pesanan = "UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?";
    $stmt_pesanan = $conn->prepare($query_pesanan);
    
    if (!$stmt_pesanan) {
        throw new Exception('Prepare pesanan gagal: ' . $conn->error);
    }
    
    $stmt_pesanan->bind_param('ss', $status_pesanan_diterima, $id_pesanan);
    
    if (!$stmt_pesanan->execute()) {
        throw new Exception('Eksekusi update pesanan gagal: ' . $stmt_pesanan->error);
    }
    
    $stmt_pesanan->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pembayaran berhasil dikonfirmasi dan pesanan diterima.'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    
    http_response_code(500); 
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>