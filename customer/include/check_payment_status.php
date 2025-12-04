<?php
require '../../database/connect.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';

if (empty($order_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Order ID tidak valid'
    ]);
    exit;
}

try {
    $query = "SELECT p.status, p.metode, ps.status_pesanan 
              FROM pembayaran p
              LEFT JOIN pesanan ps ON p.id_pesanan = ps.id_pesanan
              WHERE p.id_pesanan = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) { throw new Exception('Kesalahan server'); }
    
    $stmt->bind_param('s', $order_id);
    if (!$stmt->execute()) { throw new Exception('Kesalahan server'); }
    
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    
    if (!$payment) {
        echo json_encode([
            'success' => false,
            'message' => 'Data pembayaran tidak ditemukan'
        ]);
        exit;
    }
    
    $status = 'pending';
    
    if ($payment['status'] === 'sudah_bayar') {
        $status = 'confirmed';
    } elseif ($payment['status_pesanan'] === 'dibatalkan') {
        $status = 'rejected';
    }
    
    echo json_encode([
        'success' => true,
        'status' => $status,
        'payment_status' => $payment['status'],
        'order_status' => $payment['status_pesanan']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Kesalahan server'
    ]);
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
