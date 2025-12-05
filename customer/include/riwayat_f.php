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
                   p.aktif,
                   m.nama_menu,
                   m.harga AS harga_satuan,
                   dp.jumlah
            FROM pesanan p
            LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
            LEFT JOIN menu m ON dp.id_menu = m.id_menu
            WHERE p.id_meja = ?
            AND p.aktif = 1
            ORDER BY p.waktu_pesan DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $id_meja);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function nonaktifkanPesananMeja($id_meja, $conn) {
    $sql = "UPDATE pesanan SET aktif = 0 WHERE id_meja = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $id_meja);
    return $stmt->execute();
}

function batalkanPesanan($id_pesanan, $alasan, $conn) {
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM pesanan WHERE id_pesanan = ?");
        $stmt->bind_param('i', $id_pesanan);
        $stmt->execute();
        $pesanan = $stmt->get_result()->fetch_assoc();
        
        $stmt = $conn->prepare("INSERT INTO pembatalan (id_pesanan, id_meja, total_harga, alasan, waktu_batal) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param('iids', $id_pesanan, $pesanan['id_meja'], $pesanan['total_harga'], $alasan);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = 'dibatalkan', aktif = 0 WHERE id_pesanan = ?");
        $stmt->bind_param('i', $id_pesanan);
        $stmt->execute();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function getLaporanLabaRugi($tanggal_mulai, $tanggal_selesai, $conn) {
    $sql = "SELECT 
                DATE(p.waktu_pesan) as tanggal,
                SUM(p.total_harga) as total_pendapatan,
                SUM(dp.jumlah * m.harga_beli) as total_biaya,
                SUM(p.total_harga - (dp.jumlah * m.harga_beli)) as laba_bersih
            FROM pesanan p
            JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
            JOIN menu m ON dp.id_menu = m.id_menu
            WHERE DATE(p.waktu_pesan) BETWEEN ? AND ?
            AND p.status_pesanan != 'dibatalkan'
            AND p.status_pesanan = 'selesai'
            GROUP BY DATE(p.waktu_pesan)
            ORDER BY tanggal DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $tanggal_mulai, $tanggal_selesai);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>