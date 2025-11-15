<?php
require '../../database/connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID Pesanan tidak valid'
    ]);
    exit;
}

$id_pesanan = $_GET['id'];

try {
    // Get pesanan info
    $query = "SELECT p.*, m.nomor_meja, pb.status as status_pembayaran, pb.bukti_pembayaran
              FROM pesanan p
              LEFT JOIN meja m ON p.id_meja = m.id_meja
              LEFT JOIN pembayaran pb ON p.id_pesanan = pb.id_pesanan
              WHERE p.id_pesanan = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $id_pesanan);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Pesanan tidak ditemukan');
    }
    
    $pesanan = $result->fetch_assoc();
    $stmt->close();
    
    // Get items - PERBAIKAN DI SINI
    $query_items = "SELECT dp.*, m.nama_menu
                    FROM detail_pesanan dp
                    LEFT JOIN menu m ON dp.id_menu = m.id_menu
                    WHERE dp.id_pesanan = ?";
    
    $stmt_items = $conn->prepare($query_items);
    $stmt_items->bind_param('s', $id_pesanan);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    
    $items = [];
    while ($row = $result_items->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt_items->close();
    
    $pesanan['items'] = $items;
    
    echo json_encode([
        'success' => true,
        'pesanan' => $pesanan
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>