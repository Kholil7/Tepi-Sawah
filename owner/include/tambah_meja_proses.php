<?php
require '../../database/connect.php';

function tambahMeja($conn, $nomor_meja) {
    $kode_unik = uniqid('MEJA_');
    $qrcode_path = '../../assets/qrcode/' . $kode_unik . '.png';
    $status = 'kosong';

    // Cek duplikat nomor meja
    $cek = mysqli_query($conn, "SELECT * FROM meja WHERE nomor_meja = '$nomor_meja'");
    if (mysqli_num_rows($cek) > 0) {
        return ['msg' => 'Nomor meja sudah ada!'];
    }

    // Tambahkan meja baru
    $insert = mysqli_query($conn, "INSERT INTO meja (nomor_meja, kode_unik, status_meja, qrcode_url, last_update)
                                   VALUES ('$nomor_meja', '$kode_unik', '$status', '$qrcode_path', NOW())");

    if ($insert) {
        return ['msg' => 'Meja berhasil ditambahkan!'];
    } else {
        return ['msg' => 'Gagal menambahkan meja: ' . mysqli_error($conn)];
    }
}

function editMeja($conn, $id_meja, $nomor_meja, $status_meja) {
    $update = mysqli_query($conn, "UPDATE meja 
                                   SET nomor_meja='$nomor_meja', status_meja='$status_meja', last_update=NOW() 
                                   WHERE id_meja='$id_meja'");

    if ($update) {
        return ['msg' => 'Data meja berhasil diperbarui!'];
    } else {
        return ['msg' => 'Gagal memperbarui data: ' . mysqli_error($conn)];
    }
}

function hapusMeja($conn, $id_meja) {
    $hapus = mysqli_query($conn, "DELETE FROM meja WHERE id_meja='$id_meja'");
    if ($hapus) {
        return ['msg' => 'Meja berhasil dihapus!'];
    } else {
        return ['msg' => 'Gagal menghapus meja: ' . mysqli_error($conn)];
    }
}

function getAllMeja($conn) {
    $result = mysqli_query($conn, "SELECT * FROM meja ORDER BY id_meja ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

if (isset($_POST['tambah_meja'])) {
    $nomor_meja = trim($_POST['nomor_meja']);
    $res = tambahMeja($conn, $nomor_meja);
    echo "<script>alert('{$res['msg']}'); window.location.href='../inside/tambah_meja.php';</script>";
    exit;
}

if (isset($_POST['edit_meja'])) {
    $id_meja = $_POST['id_meja'];
    $nomor_meja = $_POST['nomor_meja'];
    $status_meja = $_POST['status_meja'];
    $res = editMeja($conn, $id_meja, $nomor_meja, $status_meja);
    echo "<script>alert('{$res['msg']}'); window.location.href='../inside/tambah_meja.php';</script>";
    exit;
}

if (isset($_GET['hapus'])) {
    $id_meja = $_GET['hapus'];
    $res = hapusMeja($conn, $id_meja);
    echo "<script>alert('{$res['msg']}'); window.location.href='../inside/tambah_meja.php';</script>";
    exit;
}
?>
