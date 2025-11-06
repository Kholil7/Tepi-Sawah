<?php
function getMejaByKode($kode_unik, $conn) {
    $stmt = $conn->prepare("SELECT * FROM meja WHERE kode_unik = ?");
    $stmt->bind_param("s", $kode_unik);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getPesananByMeja($id_meja, $conn) {
    $sql = "SELECT dp.*, m.nama_menu, p.total_harga, 
                   p.waktu_pesan AS tanggal, 
                   p.status_pesanan
            FROM detail_pesanan dp
            JOIN menu m ON dp.id_menu = m.id_menu
            JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
            WHERE p.id_meja = ?
            ORDER BY p.waktu_pesan DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_meja);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
