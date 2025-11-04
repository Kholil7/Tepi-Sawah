<?php
require '../../database/connect.php'; // koneksi database

function getMejaByKode($kode_unik, $conn) {
    if (empty($kode_unik)) return null;

    $stmt = $conn->prepare("SELECT * FROM meja WHERE kode_unik = ?");
    $stmt->bind_param("s", $kode_unik);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function getPesananByMeja($id_meja, $conn) {
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE id_meja = ? ORDER BY tanggal DESC");
    $stmt->bind_param("i", $id_meja);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}
?>
