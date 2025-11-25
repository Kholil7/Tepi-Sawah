<?php
require_once '../../config/session.php';
redirectIfLoggedIn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesehan Tepi Sawah - Admin</title>
    <?php $version = filemtime('../../css/kasir/register.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/kasir/register.css?v=<?php echo $version; ?>">
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m16 2-2.3 2.3a3 3 0 0 0 0 4.2l1.8 1.8a3 3 0 0 0 4.2 0L22 8"/>
                    <path d="M15 15 3.3 3.3a4.2 4.2 0 0 0 0 6l7.3 7.3c.7.7 2 .7 2.8 0L15 15Zm0 0 7 7"/>
                    <path d="m2.1 21.8 6.4-6.3"/>
                    <path d="m19 5-7 7"/>
                </svg>
            </div>
            <h1>Lesehan Tepi Sawah</h1>
            <p class="subtitle">Panel Admin</p>
        </div>

        <div class="form-container">
            <!-- Form Login -->
            <div class="form-wrapper active" id="loginForm">
                <h2>Masuk</h2>
                <p class="form-description">Lengkapi Data Di Bawah</p>
                
                <form method="POST" action="../include/auth.php">
                    <!-- Aksi login -->
                    <input type="hidden" name="action" value="login">

                    <div class="form-group">
                        <label for="loginEmail">Alamat Email</label>
                        <input type="email" id="loginEmail" name="email" placeholder="admin@foodiehub.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="loginPassword">Kata Sandi</label>
                        <input type="password" id="loginPassword" name="password" placeholder="Masukkan kata sandi Anda" required>
                    </div>
                    
                    <div class="form-options">
                        <a href="#" class="forgot-link">Lupa kata sandi?</a>
                    </div>
                    
                    <button type="submit" class="btn-primary">Masuk</button>
                </form>
                
                <div class="form-switch">
                    <span>Belum punya akun?</span>
                    <button type="button" class="link-button" onclick="switchForm('register')">Daftar</button>
                </div>
            </div>

            <!-- Form Register -->
            <div class="form-wrapper" id="registerForm">
                <h2>Daftar</h2>
                <p class="form-description">Buat akun admin untuk mengakses panel</p>
                
                <form method="POST" action="../include/auth.php">
                    <!-- Aksi register -->
                    <input type="hidden" name="action" value="register">

                    <div class="form-group">
                        <label for="regFullName">Nama Lengkap</label>
                        <input type="text" id="regFullName" name="fullname" placeholder="Contoh: John Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="regEmail">Alamat Email</label>
                        <input type="email" id="regEmail" name="email" placeholder="admin@foodiehub.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="regPassword">Kata Sandi</label>
                        <input type="password" id="regPassword" name="password" placeholder="Minimal 8 karakter" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="regConfirmPassword">Konfirmasi Kata Sandi</label>
                        <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="Masukkan ulang kata sandi" required>
                    </div>

                    <button type="submit" class="btn-primary">Buat Akun</button>
                </form>
                
                <div class="form-switch">
                    <span>Sudah punya akun?</span>
                    <button type="button" class="link-button" onclick="switchForm('login')">Masuk</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchForm(formType) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            if (formType === 'register') {
                loginForm.classList.remove('active');
                registerForm.classList.add('active');
            } else {
                registerForm.classList.remove('active');
                loginForm.classList.add('active');
            }
        }
    </script>
</body>
</html>
