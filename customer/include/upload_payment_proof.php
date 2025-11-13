<?php
require '../../database/connect.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Order ID tidak valid'
    ]);
    exit;
}

$order_id = $_POST['order_id'];
error_log("Upload payment proof - Order ID: " . $order_id);

if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'File bukti pembayaran tidak valid atau gagal diupload'
    ]);
    exit;
}

$file = $_FILES['payment_proof'];

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = mime_content_type($file['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipe file tidak diperbolehkan. Hanya JPG, PNG, dan GIF yang diizinkan.'
    ]);
    exit;
}

$max_size = 5 * 1024 * 1024;
if ($file['size'] > $max_size) {
    echo json_encode([
        'success' => false,
        'message' => 'Ukuran file terlalu besar. Maksimal 5MB.'
    ]);
    exit;
}

try {
    // Cek apakah order_id ada di database
    $check_query = "SELECT id_pesanan, status FROM pembayaran WHERE id_pesanan = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('s', $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Order ID tidak ditemukan di database: ' . $order_id);
    }
    
    $payment_data = $check_result->fetch_assoc();
    error_log("Current payment status: " . $payment_data['status']);
    $check_stmt->close();

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'bukti_' . $order_id . '_' . time() . '.' . $file_extension;
    $upload_path = '../../assets/uploads/' . $new_filename;

    if (!file_exists('../../assets/uploads/')) {
        mkdir('../../assets/uploads/', 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Gagal memindahkan file ke folder upload');
    }

    $query = "UPDATE pembayaran SET bukti_pembayaran = ? WHERE id_pesanan = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        unlink($upload_path);
        throw new Exception('Prepare statement gagal: ' . $conn->error);
    }
    
    $stmt->bind_param('ss', $new_filename, $order_id);
    
    if (!$stmt->execute()) {
        unlink($upload_path);
        throw new Exception('Gagal menyimpan data ke database: ' . $stmt->error);
    }
    
    $affected_rows = $stmt->affected_rows;
    error_log("Rows affected by update: " . $affected_rows);
    
    if ($affected_rows === 0) {
        error_log("WARNING: Update executed but no rows affected for order: " . $order_id);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Bukti pembayaran berhasil diupload',
        'filename' => $new_filename,
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    error_log("Upload payment proof error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>