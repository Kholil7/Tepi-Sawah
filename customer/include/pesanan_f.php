<?php
require '../../database/connect.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false]); exit;
}

if (!isset($data['id_meja']) || empty($data['id_meja'])) {
    echo json_encode(['success' => false]); exit;
}

function generateRandomCode($length = 11) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

try {
    if ($conn->connect_error) { throw new Exception(); }
    $conn->begin_transaction();

    $kode_meja = $data['id_meja'];

    $stmt_get_id = $conn->prepare("SELECT id_meja FROM meja WHERE kode_unik = ? OR id_meja = ? OR nomor_meja = ?");
    $stmt_get_id->bind_param('sss', $kode_meja, $kode_meja, $kode_meja);
    $stmt_get_id->execute();
    $result_get_id = $stmt_get_id->get_result();
    if ($result_get_id->num_rows === 0) { throw new Exception(); }
    $row = $result_get_id->fetch_assoc();
    $id_meja = $row['id_meja'];
    $stmt_get_id->close();

    $catatan = isset($data['notes']) ? $data['notes'] : null;
    $metode_bayar = isset($data['payment_method']) ? $data['payment_method'] : 'cash';
    $total_harga = isset($data['total_amount']) ? floatval($data['total_amount']) : 0;
    if ($total_harga <= 0) { throw new Exception(); }

    $jenis_pesanan = 'dine_in';
    $status_pesanan = 'menunggu';
    $waktu_pesan = date('Y-m-d H:i:s');
    $id_pesanan = generateRandomCode();

    $stmt_pesanan = $conn->prepare(
        "INSERT INTO pesanan (id_pesanan, id_meja, waktu_pesan, jenis_pesanan, status_pesanan, metode_bayar, total_harga, catatan)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_pesanan->bind_param(
        'ssssssds',
        $id_pesanan, $id_meja, $waktu_pesan, $jenis_pesanan, $status_pesanan,
        $metode_bayar, $total_harga, $catatan
    );
    $stmt_pesanan->execute();
    $stmt_pesanan->close();

    $stmt_detail = $conn->prepare(
        "INSERT INTO detail_pesanan (id_detail, id_pesanan, id_menu, jumlah, harga_satuan, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($data['items'] as $item) {
        $id_detail = generateRandomCode();
        $id_menu = $item['id'];
        $jumlah = intval($item['quantity']);
        $harga_satuan = floatval($item['harga']);
        $subtotal = $jumlah * $harga_satuan;

        $stmt_detail->bind_param(
            'sssidd',
            $id_detail, $id_pesanan, $id_menu, $jumlah, $harga_satuan, $subtotal
        );
        $stmt_detail->execute();
    }
    $stmt_detail->close();

    $id_pembayaran = generateRandomCode();
    $waktu_pembayaran = date('Y-m-d H:i:s');

    $stmt_pembayaran = $conn->prepare(
        "INSERT INTO pembayaran (id_pembayaran, id_pesanan, metode, status, waktu_pembayaran, bukti_pembayaran)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $status_pembayaran = 'belum_bayar';
    $bukti = '';

    $stmt_pembayaran->bind_param(
        'ssssss',
        $id_pembayaran, $id_pesanan, $metode_bayar, $status_pembayaran, $waktu_pembayaran, $bukti
    );
    $stmt_pembayaran->execute();
    $stmt_pembayaran->close();

    $stmt_meja = $conn->prepare("UPDATE meja SET status_meja = 'terisi' WHERE id_meja = ?");
    $stmt_meja->bind_param('s', $id_meja);
    $stmt_meja->execute();
    $stmt_meja->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $id_pesanan,
        'payment_id' => $id_pembayaran,
        'status' => 'belum_bayar'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false]);
}

$conn->close();
?>
