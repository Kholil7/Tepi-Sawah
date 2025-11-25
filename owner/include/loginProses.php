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
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($nama) || empty($email) || empty($password)) {
        showPopup('Semua field wajib diisi!', '../auth/login.php');
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
            showPopup('Login berhasil! Selamat datang.', '../inside/dashboard.php');
        } else {
            showPopup('Password salah!', '../auth/login.php');
        }
    } else {
        showPopup('Data pengguna tidak ditemukan!', '../auth/login.php');
    }

    $stmt->close();
}
?>