<?php
function getMejaByKode($kode_unik, $conn) {
    $stmt = $conn->prepare("SELECT * FROM meja WHERE kode_unik = ?");
    $stmt->bind_param("s", $kode_unik);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getPesananByMeja($id_meja, $conn) {
    $sql = "SELECT p.id_pesanan,
                   p.id_meja,
                   p.waktu_pesan AS tanggal,
                   p.jenis_pesanan,
                   p.status_pesanan,
                   p.metode_bayar,
                   p.total_harga,
                   p.catatan,
                   m.nama_menu,
                   m.harga AS harga_satuan,
                   dp.jumlah
            FROM pesanan p
            LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
            LEFT JOIN menu m ON dp.id_menu = m.id_menu
            WHERE p.id_meja = ?
            ORDER BY p.waktu_pesan DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $id_meja);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
