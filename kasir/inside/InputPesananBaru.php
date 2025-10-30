<?php
// ====================
// KONEKSI DATABASE
// ====================
$koneksi = mysqli_connect("localhost", "root", "", "resto");
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ====================
// PROSES SIMPAN PESANAN
// ====================
if (isset($_POST['tambah'])) {
    $id_meja   = $_POST['id_meja'];
    $id_menu   = $_POST['id_menu'];
    $jumlah    = $_POST['jumlah'];
    $catatan   = $_POST['catatan'];

    // Ambil harga menu
    $menu = mysqli_query($koneksi, "SELECT harga FROM menu WHERE id_menu = '$id_menu'");
    $data_menu = mysqli_fetch_assoc($menu);
    $harga_satuan = $data_menu['harga'];
    $subtotal = $harga_satuan * $jumlah;

    // Buat pesanan baru
    $query_pesanan = "INSERT INTO pesanan (id_meja, metode_pembayaran, status_pesanan, total_harga, waktu_pesan, waktu_update)
                      VALUES ('$id_meja', 'cash', 'menunggu', '$subtotal', NOW(), NOW())";
    mysqli_query($koneksi, $query_pesanan);
    $id_pesanan = mysqli_insert_id($koneksi);

    // Tambahkan detail pesanan
    $query_detail = "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, harga_satuan, subtotal)
                     VALUES ('$id_pesanan', '$id_menu', '$jumlah', '$harga_satuan', '$subtotal')";
    mysqli_query($koneksi, $query_detail);

    // Update status meja
    mysqli_query($koneksi, "UPDATE meja SET status='aktif', waktu_update=NOW() WHERE id_meja='$id_meja'");

    echo "<script>alert('Pesanan berhasil ditambahkan!'); window.location='form_pesanan_manual.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Pemesanan Manual</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f6f7fb;
            padding: 20px;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            width: 600px;
            margin: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 5px;
        }
        p {
            color: #777;
            margin-top: 0;
            font-size: 14px;
        }
        select, input, textarea {
            width: 100%;
            padding: 10px;
            margin: 6px 0 12px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        button {
            background: #2979ff;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-size: 15px;
        }
        button:hover {
            background: #1565c0;
            cursor: pointer;
        }
        .card {
            background: #f0f2f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .card h4 {
            margin-top: 0;
            color: #333;
        }
        label {
            font-weight: bold;
            color: #444;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Form Pemesanan Manual</h2>
    <p>Tambahkan pesanan untuk customer</p>

    <form action="" method="POST">
        <!-- PILIH MEJA -->
        <label>Pilih Meja</label>
        <select name="id_meja" required>
            <option value="">-- Pilih Meja --</option>
            <?php
            $meja = mysqli_query($koneksi, "SELECT * FROM meja WHERE status='kosong' OR status='aktif'");
            while ($row = mysqli_fetch_assoc($meja)) {
                echo "<option value='{$row['id_meja']}'>Meja {$row['nomor_meja']}</option>";
            }
            ?>
        </select>

        <!-- TAMBAH ITEM -->
        <div class="card">
            <h4>Tambah Item</h4>

            <label>Menu</label>
            <select name="id_menu" required>
                <option value="">-- Pilih Menu --</option>
                <?php
                $menu = mysqli_query($koneksi, "SELECT * FROM menu WHERE status='tersedia'");
                while ($row = mysqli_fetch_assoc($menu)) {
                    echo "<option value='{$row['id_menu']}'>{$row['nama_menu']} - Rp " . number_format($row['harga'],0,',','.') . "</option>";
                }
                ?>
            </select>

            <label>Jumlah</label>
            <input type="number" name="jumlah" value="1" min="1" required>

            <label>Catatan (Opsional)</label>
            <textarea name="catatan" placeholder="Contoh: pedas level 5, tanpa bawang"></textarea>
        </div>

        <button type="submit" name="tambah">+ Tambah Item</button>
    </form>
</div>

</body>
</html>
