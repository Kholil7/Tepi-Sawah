<?php
require_once '../../config/session.php';
redirectIfLoggedIn();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mengambil seluruh array flash message
$flash_data = null; 

if (isset($_SESSION['flash_message'])) {
    $flash_data = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Owner - Shopping Cart</title>
    <?php $version = filemtime('../../css/styleOwner.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/styleOwner.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Kustom untuk Pop-up (Meniru style sebelumnya dengan nuansa biru) */
        .custom-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            max-width: 350px;
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        .custom-popup.show {
            opacity: 1;
            transform: translateX(0);
        }
        @keyframes slideInRight {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="frame">
            <div class="logo-container">
                <div class="cart-icon">
                    <i class="fas fa-utensils"></i>
                </div>
            </div>

            <h1>Login Owner</h1>
            
            <form action="../include/loginProses.php" method="POST">
                <div class="input-group">
                    <label for="nama">Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="nama" id="nama" placeholder="Masukkan nama lengkap" required>
                    </div>
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" placeholder="Masukkan email" required>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" placeholder="Masukkan password" required>
                            <i id="togglePassword" class="fas fa-eye"></i>
                        </div>
                    </div>
                    <div class="forgot-link">
                        <a href="#" id="forgotPasswordLink">Lupa Password?</a>
                    </div>
                </div>

                <button type="submit">Masuk</button>
                
                <p>Belum memiliki akun? <a href="register.php">Daftar sekarang</a></p>
            </form>
        </div>
    </div>

    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reset Password</h2>
                <span class="close">&times;</span>
            </div>
            <form id="forgotPasswordForm">
                <div class="input-group">
                    <label for="reset_email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="reset_email" id="reset_email" placeholder="Masukkan email Anda" required>
                    </div>
                </div>

                <div class="input-group">
                    <label for="old_password">Password Lama</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="old_password" id="old_password" placeholder="Masukkan password lama" required>
                        <i class="fas fa-eye toggle-password" data-target="old_password"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label for="new_password">Password Baru</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="new_password" id="new_password" placeholder="Masukkan password baru" required>
                        <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Konfirmasi password baru" required>
                        <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                    </div>
                </div>

                <button type="submit">Reset Password</button>
            </form>
        </div>
    </div>

    <div id="popupAlert"></div>

    <script>
        const togglePassword = document.querySelector("#togglePassword");
        const passwordInput = document.querySelector("#password");

        togglePassword.addEventListener("click", function () {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });

        const modal = document.getElementById("forgotPasswordModal");
        const forgotLink = document.getElementById("forgotPasswordLink");
        const closeBtn = document.querySelector(".close");

        forgotLink.addEventListener("click", function(e) {
            e.preventDefault();
            modal.style.display = "block";
        });

        closeBtn.addEventListener("click", function() {
            modal.style.display = "none";
        });

        window.addEventListener("click", function(e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });

        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        function showCustomPopup(data) {
            const popup = document.getElementById('popupAlert');
            
            // Atur class untuk styling kustom
            popup.className = 'custom-popup'; 
            
            const title = data.title || 'Pesan';
            const message = data.message || 'Pesan notifikasi.';
            const color = data.color || '#3498db'; // Default warna biru
            
            // Membangun konten HTML pop-up
            popup.innerHTML = `
                <h3 style="color: ${color}; margin: 0 0 10px 0; font-size: 18px; font-weight: 600;">${title}</h3>
                <p style="color: #7f8c8d; margin: 0; font-size: 14px; line-height: 1.5;">${message}</p>
            `;

            popup.classList.add('show');
            
            setTimeout(() => {
                popup.classList.remove('show');
            }, 5000); 
        }

        // --- Logika Lupa Password (Menggunakan showCustomPopup) ---
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                showCustomPopup({
                    message: 'Password baru dan konfirmasi password tidak cocok!', 
                    type: 'error', 
                    title: 'Perhatian!', 
                    color: '#3498db' 
                });
                return;
            }

            fetch('../include/resetPassword.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const popupData = {
                    message: data.message,
                    type: data.success ? 'success' : 'error',
                    title: data.success ? 'Berhasil!' : 'Perhatian!',
                    color: data.success ? '#2980b9' : '#3498db'
                };

                if (data.success) {
                    showCustomPopup(popupData);
                    modal.style.display = 'none';
                    document.getElementById('forgotPasswordForm').reset();
                } else {
                    showCustomPopup(popupData);
                }
            })
            .catch(error => {
                showCustomPopup({
                    message: 'Terjadi kesalahan. Silakan coba lagi.', 
                    type: 'error', 
                    title: 'Perhatian!', 
                    color: '#3498db'
                });
                console.error('Error:', error);
            });
        });
        
        // --- Logika Pengecekan Flash Message dari PHP Session ---
        const flashData = <?php echo json_encode($flash_data); ?>;

        if (flashData) {
            document.addEventListener('DOMContentLoaded', function() {
                showCustomPopup(flashData);
            });
        }
    </script>
</body>
</html>