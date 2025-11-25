<?php
require_once '../../config/session.php';
require_once '../../database/connect.php';

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
                color: #27ae60;
                margin-bottom: 15px;
                font-size: 24px;
            }
            .popup p {
                color: #555;
                margin-bottom: 25px;
                font-size: 16px;
            }
            .popup button {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            <h2>✓ Berhasil!</h2>
            <p>{$message}</p>
            <button onclick='window.location.href=\"{$redirect}\"'>OK</button>
        </div>
    </body>
    </html>
    ";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = 'OWR' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $nama        = trim($_POST['nama']);
    $email       = trim($_POST['email']);
    $password    = trim($_POST['password']);

    if (empty($nama) || empty($email) || empty($password)) {
        showPopup('Semua field wajib diisi!', '../auth/register.php');
    }

    $check = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        showPopup('Email sudah terdaftar!', '../auth/register.php');
    }

    $check->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $role = 'owner';
    $sql = "INSERT INTO pengguna (id_pengguna, nama, email, password, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $id_pengguna, $nama, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        showPopup('Registrasi Owner berhasil! Silakan login.', '../auth/login.php');
    } else {
        showPopup('Terjadi kesalahan: ' . $conn->error, '../auth/register.php');
    }

    $stmt->close();
}
?>