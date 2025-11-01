<?php
session_start();

// Koneksi database (sesuaikan dengan konfigurasi Anda)
include '../../database/connect.php';

// Proses tambah pembelian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_pembelian'])) {
    $nama_bahan = $_POST['nama_bahan'];
    $harga = $_POST['harga'];
    $tanggal_beli = $_POST['tanggal_beli'];
    $keterangan = $_POST['keterangan'];
    $bukti_pembelian = $_POST['bukti_pembelian'];
    $dibuat_oleh = $_SESSION['user_id'] ?? 1; // Sesuaikan dengan session user
    
    $query = "INSERT INTO pembelian_bahan (nama_bahan, harga, tanggal_beli, keterangan, bukti_pembelian, dibuat_oleh) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdsssi", $nama_bahan, $harga, $tanggal_beli, $keterangan, $bukti_pembelian, $dibuat_oleh);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Pembelian berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambahkan pembelian!";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Proses hapus pembelian
if (isset($_GET['hapus'])) {
    $id_beli = $_GET['hapus'];
    
    $query = "DELETE FROM pembelian_bahan WHERE id_beli = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_beli);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Pembelian berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus pembelian!";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Ambil data pembelian hari ini
$today = date('Y-m-d');
$query = "SELECT * FROM pembelian_bahan WHERE DATE(tanggal_beli) = ? ORDER BY tanggal_beli DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$pembelian_list = $result->fetch_all(MYSQLI_ASSOC);

// Hitung summary
$total_pembelian = count($pembelian_list);
$total_pengeluaran = array_sum(array_column($pembelian_list, 'harga'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pembelian Bahan</title>
    <link rel="stylesheet" href="../../css/owner/pembelian_bahan.css">
</head>
<body>
    <?php include '../../sidebar/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1>Input Pembelian Bahan</h1>
                    <p>Catat pembelian bahan baku restoran</p>
                </div>
                <button class="btn-tambah" onclick="openModal()">
                    <span class="plus-icon">+</span> Tambah Pembelian
                </button>
            </div>

            <div class="summary-cards">
                <div class="card">
                    <p class="card-label">Total Pembelian Hari Ini</p>
                    <h2 class="card-value"><?php echo $total_pembelian; ?></h2>
                </div>
                <div class="card">
                    <p class="card-label">Total Pengeluaran</p>
                    <h2 class="card-value-amount">Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></h2>
                </div>
            </div>

            <div class="table-container">
                <h3>Daftar Pembelian</h3>
                <table class="purchase-table">
                    <thead>
                        <tr>
                            <th>Nama Bahan</th>
                            <th>Harga</th>
                            <th>Tanggal Beli</th>
                            <th>Keterangan</th>
                            <th>Bukti Pembelian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pembelian_list) > 0): ?>
                            <?php foreach ($pembelian_list as $pembelian): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pembelian['nama_bahan']); ?></td>
                                    <td>Rp <?php echo number_format($pembelian['harga'], 0, ',', '.'); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($pembelian['tanggal_beli'])); ?></td>
                                    <td><?php echo htmlspecialchars($pembelian['keterangan']) ?: '-'; ?></td>
                                    <td><?php echo htmlspecialchars($pembelian['bukti_pembelian']) ?: '-'; ?></td>
                                    <td>
                                        <a href="?hapus=<?php echo $pembelian['id_beli']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pembelian ini?')">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                                <path d="M2 4h12M5.333 4V2.667a1.333 1.333 0 0 1 1.334-1.334h2.666a1.333 1.333 0 0 1 1.334 1.334V4m2 0v9.333a1.333 1.333 0 0 1-1.334 1.334H4.667a1.333 1.333 0 0 1-1.334-1.334V4h9.334Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                    Belum ada data pembelian hari ini
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Tambah Pembelian -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Pembelian Bahan</h2>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nama_bahan">Nama Bahan <span class="required">*</span></label>
                    <input type="text" id="nama_bahan" name="nama_bahan" maxlength="150" required>
                </div>
                <div class="form-group">
                    <label for="harga">Harga <span class="required">*</span></label>
                    <input type="number" id="harga" name="harga" placeholder="285000" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="tanggal_beli">Tanggal Beli <span class="required">*</span></label>
                    <input type="date" id="tanggal_beli" name="tanggal_beli" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="3" placeholder="Masukkan keterangan pembelian..."></textarea>
                </div>
                <div class="form-group">
                    <label for="bukti_pembelian">Bukti Pembelian</label>
                    <input type="text" id="bukti_pembelian" name="bukti_pembelian" maxlength="255" placeholder="Nomor invoice atau kode bukti">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" name="tambah_pembelian" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalTambah').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('modalTambah').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('modalTambah');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Auto hide alerts after 3 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 3000);
    </script>
</body>
</html>