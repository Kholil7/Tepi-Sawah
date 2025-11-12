<?php
require_once '../../database/connect.php';
session_start();

$action = $_POST['action'] ?? '';

function generateKasirId() {
    $prefix = 'KSR';
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < 8; $i++) {
        $randomString .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $prefix . $randomString;
}

if ($action === 'register') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        echo "<script>alert('Kata sandi tidak sama!'); history.back();</script>";
        exit;
    }

    $check = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email sudah terdaftar!'); history.back();</script>";
        exit;
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
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location.href='../register.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data!'); history.back();</script>";
    }

} elseif ($action === 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            echo "<script>alert('Login berhasil!'); window.location.href='../inside/dashboard_kasir.php';</script>";
        } else {
            echo "<script>alert('Kata sandi salah!'); history.back();</script>";
        }
    } else {
        echo "<script>alert('Email tidak ditemukan!'); history.back();</script>";
    }

} else {
    echo "<script>alert('Aksi tidak dikenali.'); history.back();</script>";
}
?>
