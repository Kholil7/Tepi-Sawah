<?php
require '../../database/connect.php';
header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
    exit;
}

try {
    $query = "SELECT p.status, p.metode, ps.status_pesanan 
              FROM pembayaran p
              LEFT JOIN pesanan ps ON p.id_pesanan = ps.id_pesanan
              WHERE p.id_pesanan = ? 
              ORDER BY p.id_pembayaran DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception('Prepare statement gagal: ' . $conn->error);
    $stmt->bind_param('i', $order_id);
    if (!$stmt->execute()) throw new Exception('Execute query gagal: ' . $stmt->error);
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Data pembayaran tidak ditemukan']);
        exit;
    }

    $status = 'pending';
    if ($payment['metode'] === 'qris') {
        if ($payment['status'] === 'sudah_bayar') {
            $status = 'confirmed';
        } elseif ($payment['status_pesanan'] === 'dibatalkan') {
            $status = 'rejected';
        } else {
            $status = 'waiting';
        }
    } else {
        if ($payment['status_pesanan'] === 'selesai') {
            $status = 'confirmed';
        } elseif ($payment['status_pesanan'] === 'dibatalkan') {
            $status = 'rejected';
        }
    }

    echo json_encode([
        'success' => true,
        'status' => $status,
        'payment_status' => $payment['status'],
        'order_status' => $payment['status_pesanan'],
        'metode' => $payment['metode']
    ]);
} catch (Exception $e) {
    error_log("Check payment status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
