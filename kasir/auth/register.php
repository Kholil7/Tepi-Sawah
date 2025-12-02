<?php
require_once '../../config/session.php';
redirectIfLoggedIn();

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
    <title>Lesehan Tepi Sawah - Admin</title>
    <?php $version = filemtime('../../css/kasir/register.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/kasir/register.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
.modal {
    display: none; 
    position: fixed; 
    z-index: 1000; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.5); 
    justify-content: center; 
    align-items: center;
}
.modal-content {
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    position: relative;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    animation: slideDown 0.3s ease-out;
}
@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
.close-button {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 20px;
    cursor: pointer;
}
.close-button:hover,
.close-button:focus {
    color: #333;
    text-decoration: none;
    cursor: pointer;
}

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
.custom-popup h3 {
    margin: 0 0 10px 0; 
    font-size: 18px; 
    font-weight: 600;
}
.custom-popup p {
    color: #7f8c8d; 
    margin: 0; 
    font-size: 14px; 
    line-height: 1.5;
}
.custom-popup .btn-primary {
    display: none; 
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.password-wrapper input {
    padding-right: 40px; 
    width: 100%;
}
.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #aaa;
    z-index: 10;
}
.toggle-password:hover {
    color: #333;
}

.form-container .btn-primary,
.modal-content .btn-primary {
    background-color: #FF9500;
    transition: background-color 0.2s ease;
}

.form-container .btn-primary:hover,
.modal-content .btn-primary:hover {
    background-color: #e68600; 
}
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-utensils"></i>
            </div>
            <h1>Lesehan Tepi Sawah</h1>
            <p class="subtitle">Kasir</p>
        </div>

        <div class="form-container">
            <div class="form-wrapper active" id="loginForm">
                <h2>Masuk</h2>
                <p class="form-description">Lengkapi Data Di Bawah</p>
                
                <form method="POST" action="../include/auth.php">
                    <input type="hidden" name="action" value="login">

                    <div class="form-group">
                        <label for="loginEmail">Alamat Email</label>
                        <input type="email" id="loginEmail" name="email" placeholder="Rina@gmail.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="loginPassword">Kata Sandi</label>
                        <div class="password-wrapper">
                            <input type="password" id="loginPassword" name="password" placeholder="Masukkan kata sandi Anda" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility('loginPassword', this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.91 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-3.32a3 3 0 1 1-4.24-4.24"/></svg>
                            </span>
                        </div>
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

            <div class="form-wrapper" id="registerForm">
                <h2>Daftar</h2>
                <p class="form-description">Buat akun admin untuk mengakses panel</p>
                
                <form method="POST" action="../include/auth.php">
                    <input type="hidden" name="action" value="register">

                    <div class="form-group">
                        <label for="regFullName">Nama Lengkap</label>
                        <input type="text" id="regFullName" name="fullname" placeholder="Contoh: John Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="regEmail">Alamat Email</label>
                        <input type="email" id="regEmail" name="email" placeholder="Rina@gmail.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="regPassword">Kata Sandi</label>
                        <div class="password-wrapper">
                            <input type="password" id="regPassword" name="password" placeholder="Minimal 8 karakter" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility('regPassword', this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.91 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-3.32a3 3 0 1 1-4.24-4.24"/></svg>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="regConfirmPassword">Konfirmasi Kata Sandi</label>
                        <div class="password-wrapper">
                            <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="Masukkan ulang kata sandi" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility('regConfirmPassword', this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.91 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-3.32a3 3 0 1 1-4.24-4.24"/></svg>
                            </span>
                        </div>
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

    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('forgotPasswordModal')">&times;</span>
            <h2>Ubah Kata Sandi</h2>
            <p class="form-description">Masukkan data Anda untuk mengubah kata sandi.</p>
            
            <form id="forgotPasswordForm" method="POST" action="../include/auth.php">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label for="fpEmail">Alamat Email</label>
                    <input type="email" id="fpEmail" name="email" placeholder="Rina@gmail.com" required>
                </div>
                
                <div class="form-group">
                    <label for="fpOldPassword">Kata Sandi Lama</label>
                    <div class="password-wrapper">
                        <input type="password" id="fpOldPassword" name="old_password" placeholder="Masukkan kata sandi lama Anda" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('fpOldPassword', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.91 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-3.32a3 3 0 1 1-4.24-4.24"/></svg>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fpNewPassword">Kata Sandi Baru</label>
                    <div class="password-wrapper">
                        <input type="password" id="fpNewPassword" name="new_password" placeholder="Minimal 8 karakter" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('fpNewPassword', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.91 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-3.32a3 3 0 1 1-4.24-4.24"/></svg>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fpConfirmNewPassword">Konfirmasi Kata Sandi Baru</label>
                    <div class="password-wrapper">
                        <input type="password" id="fpConfirmNewPassword" name="confirm_new_password" placeholder="Masukkan ulang kata sandi baru" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('fpConfirmNewPassword', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.91 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-3.32a3 3 0 1 1-4.24-4.24"/></svg>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Ubah Kata Sandi</button>
            </form>
        </div>
    </div>

    <div id="statusPopup" class="custom-popup"></div>

    <script>
        function showCustomPopup(data) {
            const popup = document.getElementById('statusPopup');
            
            popup.className = 'custom-popup'; 
            
            const title = data.title || 'Pesan';
            const message = data.message || 'Pesan notifikasi.';
            const color = data.color || '#FF9500'; 
            
            popup.innerHTML = `
                <h3 style="color: ${color};">${title}</h3>
                <p>${message}</p>
            `;

            popup.classList.add('show');
            
            setTimeout(() => {
                popup.classList.remove('show');
            }, 5000); 
        }

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

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        document.querySelector('.forgot-link').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('forgotPasswordModal');
        });

        function togglePasswordVisibility(inputId, iconElement) {
            const input = document.getElementById(inputId);
            const isPassword = input.type === 'password';
            
            const eyeOpen = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            const eyeClosed = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.91 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-3.32a3 3 0 1 1-4.24-4.24"/></svg>';

            if (isPassword) {
                input.type = 'text';
                iconElement.innerHTML = eyeOpen;
            } else {
                input.type = 'password';
                iconElement.innerHTML = eyeClosed;
            }
        }
        
        document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const url = form.getAttribute('action'); 
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json(); 
                
                closeModal('forgotPasswordModal');
                
                const popupData = {
                    message: result.message,
                    type: result.success ? 'success' : 'error',
                    title: result.success ? 'Berhasil!' : 'Perhatian!',
                    color: result.success ? '#FF9500' : '#FF9500'
                };
                
                showCustomPopup(popupData); 
                
                if (result.success) {
                    form.reset();
                }

            } catch (error) {
                closeModal('forgotPasswordModal');
                showCustomPopup({
                    message: "Terjadi kesalahan jaringan atau respons server tidak valid.",
                    type: 'error',
                    title: 'Kesalahan!',
                    color: '#FF9500' 
                });
            }
        });
        
        window.onclick = function(event) {
            const modal = document.getElementById('forgotPasswordModal');
            if (event.target == modal) {
                closeModal('forgotPasswordModal');
            }
        }

        const flashData = <?php echo json_encode($flash_data); ?>;

        if (flashData) {
            document.addEventListener('DOMContentLoaded', function() {
                showCustomPopup(flashData); 
                
                if (flashData.type === 'success' && flashData.message.includes('Registrasi berhasil')) {
                    switchForm('login');
                }
            });
        }
    </script>
</body>
</html>