<?php
require_once '../../config/session.php';
require_once '../../database/connect.php';

$action = $_POST['action'] ?? '';

if (!function_exists('setFlashMessage')) {
    function setFlashMessage($message, $type = 'error') {
        $color = $type === 'success' ? '#FF9500' : '#FF9500'; 
        $title = $type === 'success' ? 'Berhasil!' : 'Perhatian!';
        
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type,
            'color' => $color,
            'title' => $title
        ];
    }
}

function redirectWithFlash($message, $redirect, $type = 'error') {
    setFlashMessage($message, $type);
    header("Location: " . $redirect);
    exit;
}

function generateKasirId() {
    $prefix = 'KSR';
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < 8; $i++) {
        $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
    }
    return $prefix . $randomString;
}

if ($action === 'register') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($fullname) || empty($email) || empty($password) || empty($confirm)) {
        redirectWithFlash('Semua kolom wajib diisi!', '../auth/register.php', 'error');
    }

    if ($password !== $confirm) {
        redirectWithFlash('Kata sandi tidak sama!', '../auth/register.php', 'error');
    }

    $check = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        redirectWithFlash('Email sudah terdaftar!', '../auth/register.php', 'error');
    }

    do {
        $id_pengguna = generateKasirId();
        $idCheck = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE id_pengguna = ?");
        $idCheck->bind_param("s", $id_pengguna);
        $idCheck->execute();
        $idResult = $idCheck->get_result();
    } while($idResult->num_rows > 0);

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $role = 'kasir';
    $stmt = $conn->prepare("INSERT INTO pengguna (id_pengguna, nama, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $id_pengguna, $fullname, $email, $hashed, $role);

    if ($stmt->execute()) {
        redirectWithFlash('Registrasi berhasil! Silakan login.', '../auth/register.php', 'success');
    } else {
        redirectWithFlash('Gagal menyimpan data!', '../auth/register.php', 'error');
    }

} elseif ($action === 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        redirectWithFlash('Email dan kata sandi wajib diisi!', '../auth/register.php', 'error');
    }

    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE email = ? AND role = 'kasir'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            setUserSession([
                'id_pengguna' => $user['id_pengguna'],
                'nama' => $user['nama'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);
            
            header('Location: ../inside/dashboard_kasir.php');
            exit;
        } else {
            redirectWithFlash('Kata sandi salah!', '../auth/register.php', 'error');
        }
    } else {
        redirectWithFlash('Email tidak ditemukan atau bukan akun kasir!', '../auth/register.php', 'error');
    }

} elseif ($action === 'change_password') {
    header('Content-Type: application/json'); 
    
    $email = trim($_POST['email'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if (empty($email) || empty($old_password) || empty($new_password) || empty($confirm_new_password)) {
        echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
        exit;
    }

    if ($new_password !== $confirm_new_password) {
        echo json_encode(['success' => false, 'message' => 'Konfirmasi kata sandi baru tidak cocok.']);
        exit;
    }
    
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Kata sandi baru minimal 8 karakter.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT id_pengguna, password FROM pengguna WHERE email = ? AND role = 'kasir'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if (password_verify($old_password, $user['password'])) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ?");
                $update_stmt->bind_param("ss", $hashed_new_password, $user['id_pengguna']);
                
                if ($update_stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Kata sandi Anda berhasil diubah! Silakan login kembali.']); 
                } else {
                    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perubahan ke database.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Kata sandi lama salah.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Email tidak ditemukan atau peran tidak valid.']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server database.']);
    }

} else {
    redirectWithFlash('Aksi tidak dikenali.', '../auth/register.php', 'error');
}
?>