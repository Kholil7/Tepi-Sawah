<?php
require '../../database/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = $_POST['nama'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    if (empty($nama) || empty($email) || empty($password)) {
        echo "Semua field wajib diisi!";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $role = 'owner';

    $sql = "INSERT INTO menu (id_menu,nama_menu, kategori, harga, deskripsi, gambar, status, dibuat_oleh, diperbarui_oleh, waktu_dibuat, waktu_update) VALUES (?, ?, ?, ?)";
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
