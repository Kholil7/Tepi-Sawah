<?php
require_once '../../config/session.php';
require_once '../../database/connect.php';

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

function showPopup($message, $redirect) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .popup {
                background: white;
                padding: 30px 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                text-align: center;
                animation: slideIn 0.3s ease;
            }
            @keyframes slideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .popup h2 {
                color: " . (strpos($message, 'berhasil') !== false ? '#27ae60' : '#FF0000') . ";
                margin-bottom: 15px;
                font-size: 24px;
            }
            .popup p {
                color: #555;
                margin-bottom: 25px;
                font-size: 16px;
            }
            .popup button {
                background: #FF9500;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                font-weight: 600;
            }
            .popup button:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            }
        </style>
    </head>
    <body>
        <div class='popup'>
            <h2>" . (strpos($message, 'berhasil') !== false ? '✓ Berhasil!' : '✗ Gagal!') . "</h2>
            <p>{$message}</p>
            <button onclick='window.location.href=\"{$redirect}\"'>OK</button>
        </div>
    </body>
    </html>
    ";
    exit;
}

if ($action === 'register') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        showPopup('Kata sandi tidak sama!', '../auth/register.php');
    }

    $check = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        showPopup('Email sudah terdaftar!', '../auth/register.php');
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
        showPopup('Registrasi berhasil! Silakan login.', '../auth/register.php');
    } else {
        showPopup('Gagal menyimpan data!', '../auth/register.php');
    }

} elseif ($action === 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

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
            showPopup('Login berhasil! Selamat datang.', '../inside/dashboard_kasir.php');
        } else {
            showPopup('Kata sandi salah!', '../auth/register.php');
        }
    } else {
        showPopup('Email tidak ditemukan!', '../auth/register.php');
    }

} elseif ($action === 'change_password') {
    $email = trim($_POST['email'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if (empty($email) || empty($old_password) || empty($new_password) || empty($confirm_new_password)) {
        exit("Error: Semua kolom wajib diisi."); 
    }

    if ($new_password !== $confirm_new_password) {
        exit("Error: Konfirmasi kata sandi baru tidak cocok.");
    }
    
    if (strlen($new_password) < 8) {
        exit("Error: Kata sandi baru minimal 8 karakter.");
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
                    exit("Success: Kata sandi Anda berhasil diubah! Silakan login kembali."); 
                } else {
                    exit("Error: Gagal menyimpan perubahan ke database. Kode: " . $update_stmt->error);
                }
            } else {
                exit("Error: Kata sandi lama salah.");
            }
        } else {
            exit("Error: Email tidak ditemukan atau peran tidak valid.");
        }

    } catch (Exception $e) {
        exit("Error: Terjadi kesalahan server database. Detail: " . $e->getMessage());
    }

} else {
    showPopup('Aksi tidak dikenali.', '../auth/register.php');
}