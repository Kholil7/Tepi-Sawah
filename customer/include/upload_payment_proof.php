<?php
require '../../database/connect.php';

header('Content-Type: application/json');

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Validasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Validasi order_id
if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Order ID tidak valid'
    ]);
    exit;
}

$order_id = intval($_POST['order_id']);

// Validasi file upload
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'File bukti pembayaran tidak valid atau gagal diupload'
    ]);
    exit;
}

$file = $_FILES['payment_proof'];

// Validasi tipe file
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = mime_content_type($file['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipe file tidak diperbolehkan. Hanya JPG, PNG, dan GIF yang diizinkan.'
    ]);
    exit;
}

// Validasi ukuran file (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    echo json_encode([
        'success' => false,
        'message' => 'Ukuran file terlalu besar. Maksimal 5MB.'
    ]);
    exit;
}

try {
    // Buat nama file unik
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'bukti_' . $order_id . '_' . time() . '.' . $file_extension;
    $upload_path = '../../assets/uploads/' . $new_filename;

    // Pastikan folder upload ada
    if (!file_exists('../../assets/uploads/')) {
        mkdir('../../assets/uploads/', 0755, true);
    }

    // Upload file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Gagal memindahkan file ke folder upload');
    }

    // Update database - simpan nama file di tabel pembayaran
    $query = "UPDATE pembayaran SET bukti_pembayaran = ? WHERE id_pesanan = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        // Hapus file yang sudah diupload jika query gagal
        unlink($upload_path);
        throw new Exception('Prepare statement gagal: ' . $conn->error);
    }
    
    $stmt->bind_param('si', $new_filename, $order_id);
    
    if (!$stmt->execute()) {
        // Hapus file yang sudah diupload jika execute gagal
        unlink($upload_path);
        throw new Exception('Gagal menyimpan data ke database: ' . $stmt->error);
    }

    // Response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Bukti pembayaran berhasil diupload',
        'filename' => $new_filename
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