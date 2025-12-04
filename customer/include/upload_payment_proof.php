<?php
require '../../database/connect.php';

header('Content-Type: application/json');

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
        'message' => 'Tipe file tidak diperbolehkan'
    ]);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode([
        'success' => false,
        'message' => 'Ukuran file terlalu besar'
    ]);
    exit;
}

try {
    $check = $conn->prepare("SELECT id_pesanan FROM pembayaran WHERE id_pesanan = ?");
    $check->bind_param('s', $order_id);
    $check->execute();
    $has = $check->get_result();
    if ($has->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Order ID tidak ditemukan'
        ]);
        exit;
    }
    $check->close();

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = 'bukti_' . $order_id . '_' . time() . '.' . $ext;
    $path = '../../assets/uploads/' . $new_name;

    if (!file_exists('../../assets/uploads/')) {
        mkdir('../../assets/uploads/', 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal upload file'
        ]);
        exit;
    }

    $upd = $conn->prepare("UPDATE pembayaran SET bukti_pembayaran = ? WHERE id_pesanan = ?");
    $upd->bind_param('ss', $new_name, $order_id);
    $upd->execute();

    if ($upd->affected_rows === 0) {
        unlink($path);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan data'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Bukti pembayaran berhasil diupload',
        'filename' => $new_name,
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server'
    ]);
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
