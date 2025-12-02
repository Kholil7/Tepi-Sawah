<?php
require_once '../../config/session.php';
require_once '../../database/connect.php';

if (!function_exists('setFlashMessage')) {
    function setFlashMessage($message, $type = 'error') {
        // Nuansa Biru Baru
        $color = $type === 'success' ? '#2980b9' : '#3498db'; // Biru sukses: Biru Tua; Biru error: Biru Sedang
        $title = $type === 'success' ? 'Berhasil!' : 'Perhatian!';
        
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type,
            'color' => $color,
            'title' => $title
        ];
    }
}

function redirectWithFlash($message, $redirect, $type = 'success') {
    setFlashMessage($message, $type);
    header("Location: " . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = 'OWR' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $nama        = trim($_POST['nama']);
    $email       = trim($_POST['email']);
    $password    = trim($_POST['password']);

    if (empty($nama) || empty($email) || empty($password)) {
        redirectWithFlash('Semua field wajib diisi!', '../auth/register.php', 'error');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithFlash('Format email tidak valid!', '../auth/register.php', 'error');
    }

    $check = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        redirectWithFlash('Email sudah terdaftar!', '../auth/register.php', 'error');
    }

    $check->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $role = 'owner';
    $sql = "INSERT INTO pengguna (id_pengguna, nama, email, password, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $id_pengguna, $nama, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        redirectWithFlash('Registrasi Owner berhasil! Silakan login.', '../auth/login.php', 'success');
    } else {
        redirectWithFlash('Registrasi gagal. Silakan coba lagi.', '../auth/register.php', 'error');
    }

    $stmt->close();
}
?>