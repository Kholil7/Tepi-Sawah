<?php
session_start();
require '../../database/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($nama) || empty($email) || empty($password)) {
        echo "<script>alert('Semua field wajib diisi!'); window.history.back();</script>";
        exit;
    }

    $sql = "SELECT * FROM pengguna WHERE email = ? AND nama = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $nama);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();


        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'owner') {
                header("Location: ../inside/dashboard.php");
                exit;
            } else {
                echo "<script>alert('Anda tidak memiliki akses sebagai owner.'); window.location='../../index.php';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Password salah!'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Data pengguna tidak ditemukan!'); window.history.back();</script>";
        exit;
    }

    $stmt->close();
}
?>
