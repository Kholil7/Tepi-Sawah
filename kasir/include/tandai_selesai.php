<?php
require '../../database/connect.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$json = file_get_contents('php://input');
$data = json_decode($json, true);

error_log("Tandai selesai - Received data: " . print_r($data, true));

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
    
    $query_pesanan = "UPDATE pesanan SET status_pesanan = 'selesai', aktif = 0 WHERE id_pesanan = ?";
    $stmt_pesanan = $conn->prepare($query_pesanan);
    
    if (!$stmt_pesanan) {
        throw new Exception('Prepare statement pesanan gagal: ' . $conn->error);
    }
    
    $stmt_pesanan->bind_param('s', $id_pesanan);
    
    if (!$stmt_pesanan->execute()) {
        throw new Exception('Update pesanan gagal: ' . $stmt_pesanan->error);
    }
    
    $stmt_pesanan->close();
    
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
    
    $conn->commit();
    
    error_log("Pesanan selesai - Order: $id_pesanan, Meja: $id_meja dikosongkan, aktif = 0");
    
    echo json_encode([
        'success' => true,
        'message' => 'Pesanan selesai dan meja dikosongkan'
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    error_log("Tandai selesai error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>