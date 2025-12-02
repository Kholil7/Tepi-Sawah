<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../database/connect.php';

function sendJsonResponse($success, $message) {
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method tidak valid');
}

$email = trim($_POST['reset_email'] ?? '');
$oldPassword = trim($_POST['old_password'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

if (empty($email) || empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
    sendJsonResponse(false, 'Semua field harus diisi!');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Format email tidak valid!');
}

if ($newPassword !== $confirmPassword) {
    sendJsonResponse(false, 'Password baru dan konfirmasi password tidak cocok!');
}

if (strlen($newPassword) < 6) {
    sendJsonResponse(false, 'Password baru minimal 6 karakter!');
}

if ($oldPassword === $newPassword) {
    sendJsonResponse(false, 'Password baru tidak boleh sama dengan password lama!');
}

try {
    $stmt = $conn->prepare("SELECT id_pengguna, nama, password, role FROM pengguna WHERE email = ? AND role = 'owner'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        sendJsonResponse(false, 'Email tidak terdaftar sebagai owner!');
    }
    
    if (!password_verify($oldPassword, $user['password'])) {
        sendJsonResponse(false, 'Password lama tidak sesuai!');
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $updateStmt = $conn->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ? AND role = 'owner'");
    $updateStmt->bind_param("ss", $hashedPassword, $user['id_pengguna']);
    
    if ($updateStmt->execute()) {
        sendJsonResponse(true, 'Password berhasil diubah! Silakan login dengan password baru.');
    } else {
        sendJsonResponse(false, 'Gagal mengubah password. Silakan coba lagi.');
    }
    
} catch (Exception $e) {
    error_log("Error reset password: " . $e->getMessage());
    sendJsonResponse(false, 'Terjadi kesalahan sistem. Silakan coba lagi nanti.');
}
?>