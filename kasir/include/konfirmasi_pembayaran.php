<?php
require '../../database/connect.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$json = file_get_contents('php://input');
$data = json_decode($json, true);

error_log("Konfirmasi pembayaran - Received data: " . print_r($data, true));

if (!isset($data['id_pesanan']) || empty($data['id_pesanan'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID Pesanan tidak valid'
    ]);
    exit;
}

$id_pesanan = $data['id_pesanan'];

// Ubah status dari frontend ke 'sudah_bayar'
$status = 'sudah_bayar';

try {
    $query = "UPDATE pembayaran SET status = ? WHERE id_pesanan = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare statement gagal: ' . $conn->error);
    }
    
    $stmt->bind_param('ss', $status, $id_pesanan);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Status pembayaran berhasil diupdate'
        ]);
    } else {
        throw new Exception($stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Konfirmasi pembayaran error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>