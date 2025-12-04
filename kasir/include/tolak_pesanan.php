<?php
require '../../database/connect.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$json = file_get_contents('php://input');
$data = json_decode($json, true);

error_log("Tolak pesanan - Received data: " . print_r($data, true));

// Validasi Input
if (!isset($data['id_pesanan']) || empty($data['id_pesanan'])) {
    echo json_encode(['success' => false, 'message' => 'ID Pesanan tidak valid']);
    exit;
}
if (!isset($data['id_meja']) || empty($data['id_meja'])) {
    echo json_encode(['success' => false, 'message' => 'ID Meja tidak valid']);
    exit;
}
if (!isset($data['alasan']) || empty($data['alasan'])) {
    echo json_encode(['success' => false, 'message' => 'Alasan wajib diisi']);
    exit;
}

$id_pesanan = $data['id_pesanan'];
$id_meja = $data['id_meja'];
$alasan = $data['alasan'];

try {
    $conn->begin_transaction();

    // 1. Generate ID Batal (Format: BTL + Random Angka)
    $id_batal = 'BTL' . rand(10000000, 99999999); 
    $id_batal = substr($id_batal, 0, 11);

    // 2. Insert ke tabel pembatalan_pesanan
    $query_insert_batal = "INSERT INTO pembatalan_pesanan 
                          (id_batal, id_pesanan, alasan, dibatalkan_oleh, waktu_batal) 
                          VALUES (?, ?, ?, 'kasir', NOW())";
    
    $stmt_batal = $conn->prepare($query_insert_batal);
    if (!$stmt_batal) { throw new Exception('Prepare insert batal gagal: ' . $conn->error); }
    
    $stmt_batal->bind_param('sss', $id_batal, $id_pesanan, $alasan);
    if (!$stmt_batal->execute()) { throw new Exception('Gagal menyimpan data pembatalan: ' . $stmt_batal->error); }
    $stmt_batal->close();


    // 3. Update status pesanan menjadi 'dibatalkan'
    $query_update_status = "UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = ?";
    $stmt_update_status = $conn->prepare($query_update_status);
    
    if (!$stmt_update_status) { throw new Exception('Prepare update status pesanan gagal: ' . $conn->error); }
    
    $stmt_update_status->bind_param('s', $id_pesanan);
    if (!$stmt_update_status->execute()) { throw new Exception('Gagal update status pesanan: ' . $stmt_update_status->error); }
    $stmt_update_status->close();


    // 4. Update field 'aktif' menjadi 0 (untuk menyembunyikan dari list aktif) <-- TAMBAHAN KODE
    $query_update_aktif = "UPDATE pesanan SET aktif = 0 WHERE id_pesanan = ?";
    $stmt_update_aktif = $conn->prepare($query_update_aktif);
    
    if (!$stmt_update_aktif) { throw new Exception('Prepare update aktif gagal: ' . $conn->error); }
    
    $stmt_update_aktif->bind_param('s', $id_pesanan);
    if (!$stmt_update_aktif->execute()) { throw new Exception('Gagal update field aktif: ' . $stmt_update_aktif->error); }
    $stmt_update_aktif->close();


    // 5. Update status meja menjadi 'kosong'
    $query_meja = "UPDATE meja SET status_meja = 'kosong' WHERE id_meja = ?";
    $stmt_meja = $conn->prepare($query_meja);
    
    if (!$stmt_meja) { throw new Exception('Prepare update meja gagal: ' . $conn->error); }
    
    $stmt_meja->bind_param('s', $id_meja);
    if (!$stmt_meja->execute()) { throw new Exception('Gagal mengosongkan meja: ' . $stmt_meja->error); }
    $stmt_meja->close();

    // Commit Transaksi
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil ditolak, data diarsipkan, dan meja dikosongkan.'
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    error_log("Tolak pesanan error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>