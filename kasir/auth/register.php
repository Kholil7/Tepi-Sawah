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

.popup {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 2000;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.popup.show {
    display: flex;
    opacity: 1;
}
.popup-content {
    background-color: #4CAF50;
    color: white;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
    max-width: 300px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transform: scale(0.9);
    transition: transform 0.3s ease;
}
.popup.show .popup-content {
    transform: scale(1);
}
.popup-content.error {
    background-color: #F44336;
}
.popup-content p {
    margin-bottom: 20px;
    font-size: 1.1em;
}
.popup-content .btn-primary {
    background-color: white;
    color: #4CAF50;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 5px;
    font-weight: bold;
}
.popup-content.error .btn-primary {
    color: #F44336;
}
.popup-content .btn-primary:hover {
    background-color: #f0f0f0;
}

/* Gaya untuk Toggle Password */
.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.password-wrapper input {
    padding-right: 40px; /* Ruang untuk ikon */
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
    </style>
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

    <div id="statusPopup" class="popup">
        <div id="popupContent" class="popup-content">
            <p id="popupMessage"></p>
            <button type="button" class="btn-primary" onclick="closePopup()">Tutup</button>
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

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openPopup(message, isSuccess) {
            const popupContent = document.getElementById('popupContent');
            document.getElementById('popupMessage').innerText = message;
            
            if (isSuccess) {
                popupContent.classList.remove('error');
            } else {
                popupContent.classList.add('error');
            }
            
            document.getElementById('statusPopup').classList.add('show');
        }

        function closePopup() {
            document.getElementById('statusPopup').classList.remove('show');
        }

        document.querySelector('.forgot-link').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('forgotPasswordModal');
        });

        // Fungsi Baru: Toggle Password Visibility
        function togglePasswordVisibility(inputId, iconElement) {
            const input = document.getElementById(inputId);
            const isPassword = input.type === 'password';
            
            if (isPassword) {
                input.type = 'text';
                // Ganti icon ke mata terbuka
                iconElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            } else {
                input.type = 'password';
                // Ganti icon ke mata tertutup
                iconElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.91 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-3.32a3 3 0 1 1-4.24-4.24"/></svg>';
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
                
                const result = await response.text();
                
                if (result.startsWith("Success:")) {
                    const message = result.substring(9).trim();
                    closeModal('forgotPasswordModal');
                    openPopup(message, true); 
                    form.reset();
                } else if (result.startsWith("Error:")) {
                    const message = result.substring(6).trim();
                    closeModal('forgotPasswordModal');
                    openPopup(message, false); 
                } else {
                    closeModal('forgotPasswordModal');
                    openPopup("Terjadi kesalahan tak terduga. Respons server tidak valid. Cek log PHP Anda.", false);
                }
            } catch (error) {
                closeModal('forgotPasswordModal');
                openPopup("Terjadi kesalahan jaringan. Periksa koneksi atau URL tujuan.", false);
            }
        });
        
        window.onclick = function(event) {
            const modal = document.getElementById('forgotPasswordModal');
            if (event.target == modal) {
                closeModal('forgotPasswordModal');
            }
        }
    </script>
</body>
</html>