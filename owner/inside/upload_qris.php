<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();

include '../../database/connect.php'; 

$nama_file = 'upload_qris.php';

$sql_select = "SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = 'qris_image_path'";
$result_select = mysqli_query($conn, $sql_select);
$data = mysqli_fetch_assoc($result_select);

$qris_path = $data['nilai_pengaturan'] ?? null; 

if (isset($_POST['upload_qris'])) {
    
    $upload_dir = '../../assets/uploads/payment_qris/'; 

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['qris_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_extension, $allowed_ext)) {
            
            $new_file_name = 'qris_static_' . time() . '.' . $file_extension;
            $destination_file_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file['tmp_name'], $destination_file_path)) {
                
                $sql_update = "
                    INSERT INTO pengaturan (nama_pengaturan, nilai_pengaturan) 
                    VALUES ('qris_image_path', '$destination_file_path')
                    ON DUPLICATE KEY UPDATE nilai_pengaturan = '$destination_file_path'
                ";
                
                if (mysqli_query($conn, $sql_update)) {
                    
                    if ($qris_path && file_exists($qris_path)) {
                        unlink($qris_path);
                    }

                    header("Location: {$nama_file}?status=success");
                    exit();
                    
                } else {
                    header("Location: {$nama_file}?status=error&msg=db_fail");
                    exit();
                }
                
            } else {
                header("Location: {$nama_file}?status=error&msg=move_fail");
                exit();
            }
        } else {
            header("Location: {$nama_file}?status=error&msg=ext_fail");
            exit();
        }
    } else {
        header("Location: {$nama_file}?status=error&msg=upload_fail");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola QRIS Pembayaran</title>
    <?php $version = filemtime('../../css/owner/upload_qris.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/owner/upload_qris.css?v=<?php echo $version; ?>">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>

    <?php include '../../sidebar/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="container">
            <div class="content">
                <div class="page-header">
                    <h1>Kelolah Barcode Qris</h1>
                </div>

                <h2 class="section-title">QRIS Aktif Saat Ini</h2>
                
                <?php $display_path = $qris_path ?? 'placeholder.png'; ?>

                <div class="qris-container">
                    <?php if ($display_path != 'placeholder.png'): ?>
                        <p>Pastikan kode ini jelas dan benar.</p>
                        <img src="<?php echo $display_path; ?>" alt="QRIS Aktif">
                    <?php else: ?>
                        <p class="warning-text">⚠️ Belum ada QRIS yang terpasang.</p>
                    <?php endif; ?>
                </div>

                <div class="divider"></div>

                <h2 class="section-title">Unggah QRIS Baru</h2>
                
                <div class="upload-section">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="qris_file">Pilih Gambar QRIS (.jpg, .png):</label>
                            <input type="file" name="qris_file" id="qris_file" accept=".jpg,.jpeg,.png" required>
                        </div>
                        <button type="submit" name="upload_qris" class="btn-submit">Unggah & Ganti QRIS</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="popup-overlay" id="popupOverlay">
        <div class="popup-container">
            <div class="popup-icon" id="popupIcon"></div>
            <h3 class="popup-title" id="popupTitle"></h3>
            <p class="popup-message" id="popupMessage"></p>
            <button class="popup-btn" onclick="closePopup()">OK</button>
        </div>
    </div>

    <script>
    function checkSidebarState() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.getElementById('mainContent');
        
        if (sidebar) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        if (sidebar.classList.contains('closed')) {
                            mainContent.classList.add('sidebar-closed');
                        } else {
                            mainContent.classList.remove('sidebar-closed');
                        }
                    }
                });
            });
            
            observer.observe(sidebar, { attributes: true });
            
            if (sidebar.classList.contains('closed')) {
                mainContent.classList.add('sidebar-closed');
            }
        }
    }

    window.addEventListener('load', checkSidebarState);

    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const msg = urlParams.get('msg');
    
    function showPopup(type, title, message) {
        const overlay = document.getElementById('popupOverlay');
        const icon = document.getElementById('popupIcon');
        const titleEl = document.getElementById('popupTitle');
        const messageEl = document.getElementById('popupMessage');
        
        icon.className = 'popup-icon ' + type;
        icon.innerHTML = type === 'success' ? '✓' : '✕';
        titleEl.textContent = title;
        messageEl.textContent = message;
        
        overlay.style.display = 'block';
    }
    
    function closePopup() {
        document.getElementById('popupOverlay').style.display = 'none';
        history.replaceState(null, '', window.location.pathname);
    }
    
    if (status === 'success') {
        showPopup('success', 'Berhasil!', 'Data QRIS berhasil diperbarui!');
    } else if (status === 'error') {
        let errorMessage = 'Gagal mengunggah QRIS. ';
        if (msg === 'db_fail') {
            errorMessage += 'Terjadi error pada database.';
        } else if (msg === 'move_fail') {
            errorMessage += 'Gagal memindahkan file.';
        } else if (msg === 'ext_fail') {
            errorMessage += 'Ekstensi file tidak valid (Hanya JPG, JPEG, PNG).';
        } else if (msg === 'upload_fail') {
            errorMessage += 'Upload file gagal.';
        }
        showPopup('error', 'Gagal!', errorMessage);
    }

    document.getElementById('popupOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            closePopup();
        }
    });
    </script>

</body>
</html>