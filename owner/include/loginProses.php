<?php
require_once '../../config/session.php';
require_once '../../database/connect.php';

if (!function_exists('setFlashMessage')) {
    function setFlashMessage($message, $type = 'error') {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

function redirectWithError($message, $redirect) {
    setFlashMessage($message, 'error');
    header("Location: " . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($nama) || empty($email) || empty($password)) {
        redirectWithError('Semua field wajib diisi!', '../auth/login.php');
    }

    $sql = "SELECT * FROM pengguna WHERE email = ? AND role = 'owner'";
    $stmt = $conn->prepare($sql);
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
            
            header('Location: ../inside/dashboard.php');
            exit;
        } else {
            redirectWithError('Password yang Anda masukkan salah!', '../auth/login.php');
        }
    } else {
        redirectWithError('Email tidak terdaftar atau bukan akun owner!', '../auth/login.php');
    }

    $stmt->close();
}
?>