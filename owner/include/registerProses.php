<?php
require '../../database/connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

  
    if (empty($nama) || empty($email) || empty($password)) {
        echo "<script>alert('Semua field wajib diisi!'); window.history.back();</script>";
        exit;
    }


    $check = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email sudah terdaftar!'); window.history.back();</script>";
        exit;
    }

    $check->close();


    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    
    $role = 'owner';

  
    $sql = "INSERT INTO pengguna (nama, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nama, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Registrasi Owner berhasil! Silakan login.'); window.location='../login.php';</script>";
    } else {
        echo "Terjadi kesalahan: " . $conn->error;
    }

    $stmt->close();
}
?>
