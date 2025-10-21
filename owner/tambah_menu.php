<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../database/connect.php'; // koneksi database pakai $conn

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_menu   = trim($_POST['nama_menu']);
    $kategori    = $_POST['kategori'];
    $harga       = $_POST['harga'];
    $deskripsi   = $_POST['deskripsi'];
    $status      = $_POST['status'];
    $dibuat_oleh = 1; // sementara ID user manual

    // Simpan menu utama ke tabel menu
    $sql = "INSERT INTO menu (nama_menu, kategori, harga, deskripsi, status, dibuat_oleh)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdssi", $nama_menu, $kategori, $harga, $deskripsi, $status, $dibuat_oleh);

    if ($stmt->execute()) {
        $id_menu = $stmt->insert_id; // ambil ID menu yang baru dimasukkan

        // === Simpan semua gambar ke tabel menu_gambar ===
        if (!empty($_FILES['gambar']['tmp_name'][0])) {
            foreach ($_FILES['gambar']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    $gambar = file_get_contents($tmp_name);

                    // simpan gambar ke database
                    $stmt_gbr = $conn->prepare("INSERT INTO menu_gambar (id_menu, gambar) VALUES (?, ?)");
                    $stmt_gbr->bind_param("ib", $id_menu, $gambar);
                    $stmt_gbr->send_long_data(1, $gambar);
                    $stmt_gbr->execute();
                    $stmt_gbr->close();
                }
            }
        }

        echo "<script>alert('✅ Menu baru berhasil ditambahkan beserta gambarnya!'); window.location='tambah_menu.php';</script>";
    } else {
        echo "❌ Gagal menambah menu: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sistem Manajemen - Tambah Menu</title>
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f8f9fc;
        color: #333;
        display: flex;
    }

    /* Sidebar */
    .sidebar {
        width: 230px;
        background-color: #111827;
        color: #fff;
        height: 100vh;
        padding: 20px 0;
        display: flex;
        flex-direction: column;
    }
    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
        font-size: 18px;
        color: #facc15;
    }
    .sidebar a {
        text-decoration: none;
        color: #d1d5db;
        padding: 12px 20px;
        display: block;
        border-radius: 6px;
        margin: 5px 10px;
    }
    .sidebar a:hover, .sidebar a.active {
        background-color: #2563eb;
        color: #fff;
    }

    /* Main content */
    .main {
        flex-grow: 1;
        padding: 30px 50px;
    }
    .main h1 {
        font-size: 24px;
        margin-bottom: 5px;
    }
    .main p {
        color: #6b7280;
        margin-bottom: 30px;
    }

    /* Form */
    .form-container {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        max-width: 700px;
        margin: auto;
    }
    .form-container h3 {
        margin-bottom: 20px;
    }
    label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
    }
    input[type="text"],
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 15px;
        outline: none;
    }
    textarea {
        resize: none;
        height: 80px;
    }
    .upload-box {
        border: 2px dashed #9ca3af;
        border-radius: 10px;
        text-align: center;
        padding: 40px;
        color: #6b7280;
        margin-bottom: 20px;
    }
    .upload-box input {
        display: none;
    }
    .upload-box:hover {
        border-color: #2563eb;
        color: #2563eb;
    }
    .buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .buttons button {
        padding: 10px 15px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }
    .buttons .save {
        background-color: #2563eb;
        color: white;
    }
    .buttons .cancel {
        background-color: #e5e7eb;
    }
    .buttons .save:hover {
        background-color: #1d4ed8;
    }
</style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Sistem Manajemen</h2>
        <a href="#">🏠 Dashboard</a>
        <a href="#" class="active">➕ Tambah Menu</a>
        <a href="#">🛒 Pembelian</a>
        <a href="#">📊 Laporan Kerugian & Keuntungan</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <h1>Tambah Menu</h1>
        <p>Tambahkan menu baru ke dalam sistem</p>

        <div class="form-container">
            <h3>Form Menu Baru</h3>
            <form method="POST" enctype="multipart/form-data" action="">
                <div class="upload-box" id="uploadBox">
                    <label for="gambar">
                        <div>📤 Klik untuk upload gambar (bisa lebih dari 1)</div>
                        <div style="font-size: 12px;">PNG, JPG atau JPEG</div>
                    </label>
                    <input type="file" name="gambar[]" id="gambar" accept="image/*" multiple required>
                </div>

                <label>Nama Menu</label>
                <input type="text" name="nama_menu" placeholder="Masukkan nama menu" required>

                <label>Kategori</label>
                <select name="kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="makanan">🍛 Makanan</option>
                    <option value="minuman">🥤 Minuman</option>
                    <option value="cemilan">🍟 Cemilan</option>
                </select>

                <label>Harga</label>
                <input type="number" name="harga" placeholder="Masukkan harga jual" step="0.01" required>

                <label>Status</label>
                <select name="status" required>
                    <option value="tersedia">✅ Tersedia</option>
                    <option value="habis">❌ Habis</option>
                </select>

                <label>Deskripsi</label>
                <textarea name="deskripsi" placeholder="Deskripsi menu"></textarea>

                <div class="buttons">
                    <button type="submit" class="save">Simpan Menu</button>
                    <button type="reset" class="cancel">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('gambar');
        const uploadBox = document.getElementById('uploadBox');
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                uploadBox.innerHTML = `<strong>${fileInput.files.length} gambar</strong> berhasil dipilih ✅`;
            }
        });
    </script>
</body>
</html>
