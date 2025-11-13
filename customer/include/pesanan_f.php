<?php
require '../../database/connect.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$json = file_get_contents('php://input');
$data = json_decode($json, true);

error_log("Received data: " . print_r($data, true));

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak dapat dibaca atau format JSON tidak valid',
        'debug' => 'Raw input: ' . substr($json, 0, 200)
    ]);
    exit;
}

if (!isset($data['items']) || empty($data['items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data pesanan tidak valid - items kosong atau tidak ada',
        'debug' => 'Received keys: ' . implode(', ', array_keys($data))
    ]);
    exit;
}

if (!isset($data['id_meja']) || empty($data['id_meja'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID Meja tidak valid',
        'debug' => 'id_meja: ' . ($data['id_meja'] ?? 'null')
    ]);
    exit;
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
    if ($conn->connect_error) {
        throw new Exception('Koneksi database gagal: ' . $conn->connect_error);
    }

    $conn->begin_transaction();

    $kode_meja = $data['id_meja'];
    error_log("Mencari meja dengan nilai: " . $kode_meja);

    $query_get_id = "SELECT id_meja FROM meja WHERE kode_unik = ? OR id_meja = ? OR nomor_meja = ?";
    $stmt_get_id = $conn->prepare($query_get_id);

    if (!$stmt_get_id) {
        throw new Exception('Prepare statement get id_meja gagal: ' . $conn->error);
    }

    $stmt_get_id->bind_param('sss', $kode_meja, $kode_meja, $kode_meja);
    $stmt_get_id->execute();
    $result_get_id = $stmt_get_id->get_result();

    if ($result_get_id->num_rows === 0) {
        throw new Exception('Meja tidak ditemukan dengan nilai: ' . $kode_meja);
    }

    $row = $result_get_id->fetch_assoc();
    $id_meja = $row['id_meja'];
    $stmt_get_id->close();

    if (empty($id_meja)) {
        throw new Exception('ID Meja kosong setelah query');
    }

    error_log("ID Meja ditemukan: " . $id_meja);

    $query_verify = "SELECT id_meja FROM meja WHERE id_meja = ?";
    $stmt_verify = $conn->prepare($query_verify);
    $stmt_verify->bind_param('s', $id_meja);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    
    if ($result_verify->num_rows === 0) {
        throw new Exception('ID Meja tidak ada di database: ' . $id_meja);
    }
    $stmt_verify->close();

    $catatan = isset($data['notes']) && !empty($data['notes']) ? $data['notes'] : null;
    $metode_bayar = isset($data['payment_method']) ? $data['payment_method'] : 'cash';
    $total_harga = isset($data['total_amount']) ? floatval($data['total_amount']) : 0;
    
    if ($total_harga <= 0) {
        throw new Exception('Total harga tidak valid: ' . $total_harga);
    }

    $jenis_pesanan = 'dine_in';
    $status_pesanan = 'menunggu';
    $waktu_pesan = date('Y-m-d H:i:s');
    $dibuat_oleh = null;

    $id_pesanan = generateRandomCode(11);

    $query_pesanan = "INSERT INTO pesanan (
        id_pesanan,
        id_meja,
        dibuat_oleh,
        waktu_pesan,
        jenis_pesanan,
        status_pesanan,
        metode_bayar,
        total_harga,
        catatan,
        diterima_oleh
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)";
    
    $stmt_pesanan = $conn->prepare($query_pesanan);
    
    if (!$stmt_pesanan) {
        throw new Exception('Prepare statement pesanan gagal: ' . $conn->error);
    }
    
    $stmt_pesanan->bind_param(
        'ssissssds',
        $id_pesanan,
        $id_meja,
        $dibuat_oleh,
        $waktu_pesan,
        $jenis_pesanan,
        $status_pesanan,
        $metode_bayar,
        $total_harga,
        $catatan
    );
    
    if (!$stmt_pesanan->execute()) {
        throw new Exception('Execute insert pesanan gagal: ' . $stmt_pesanan->error);
    }
    
    error_log("Pesanan berhasil dibuat dengan ID: " . $id_pesanan);

    $query_detail = "INSERT INTO detail_pesanan (
        id_detail,
        id_pesanan,
        id_menu,
        jumlah,
        harga_satuan,
        subtotal,
        status_item,
        catatan_item
    ) VALUES (?, ?, ?, ?, ?, ?, 'menunggu', NULL)";
    
    $stmt_detail = $conn->prepare($query_detail);
    
    if (!$stmt_detail) {
        throw new Exception('Prepare statement detail pesanan gagal: ' . $conn->error);
    }
    
    foreach ($data['items'] as $index => $item) {
        if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['harga'])) {
            throw new Exception('Data item tidak lengkap pada index ' . $index);
        }
        
        $id_detail = generateRandomCode(11);
        $id_menu = $item['id'];
        $jumlah = intval($item['quantity']);
        $harga_satuan = floatval($item['harga']);
        $subtotal = $jumlah * $harga_satuan;
        
        $stmt_detail->bind_param(
            'sssidd',
            $id_detail,
            $id_pesanan,
            $id_menu,
            $jumlah,
            $harga_satuan,
            $subtotal
        );
        
        if (!$stmt_detail->execute()) {
            throw new Exception('Execute insert detail pesanan gagal: ' . $stmt_detail->error);
        }
    }

    error_log("Detail pesanan berhasil dibuat untuk " . count($data['items']) . " item");

    $status_pembayaran = 'belum_bayar';
    $waktu_pembayaran = date('Y-m-d H:i:s');
    $jumlah_tagihan = $total_harga;
    $jumlah_dibayar = 0;
    $kembalian = 0;
    $bukti_pembayaran = '';
    
    $id_pembayaran = generateRandomCode(11);

    $query_pembayaran = "INSERT INTO pembayaran (
        id_pembayaran,
        id_pesanan,
        metode,
        status,
        jumlah_tagihan,
        jumlah_dibayar,
        kembalian,
        waktu_pembayaran,
        bukti_pembayaran
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_pembayaran = $conn->prepare($query_pembayaran);
    
    if (!$stmt_pembayaran) {
        throw new Exception('Prepare statement pembayaran gagal: ' . $conn->error);
    }
    
    $stmt_pembayaran->bind_param(
        'ssssdddss',
        $id_pembayaran,
        $id_pesanan,
        $metode_bayar,
        $status_pembayaran,
        $jumlah_tagihan,
        $jumlah_dibayar,
        $kembalian,
        $waktu_pembayaran,
        $bukti_pembayaran
    );
    
    if (!$stmt_pembayaran->execute()) {
        throw new Exception('Execute insert pembayaran gagal: ' . $stmt_pembayaran->error);
    }

    error_log("Pembayaran berhasil dibuat dengan ID: " . $id_pembayaran);

    if ($id_meja) {
        $query_update_meja = "UPDATE meja SET status_meja = 'terisi' WHERE id_meja = ?";
        $stmt_meja = $conn->prepare($query_update_meja);
        
        if (!$stmt_meja) {
            throw new Exception('Prepare statement update meja gagal: ' . $conn->error);
        }
        
        $stmt_meja->bind_param('s', $id_meja);
        
        if (!$stmt_meja->execute()) {
            throw new Exception('Execute update meja gagal: ' . $stmt_meja->error);
        }

        error_log("Status meja berhasil diupdate untuk ID: " . $id_meja);
    }

    $conn->commit();

    error_log("Transaksi berhasil - Order ID: " . $id_pesanan);

    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil disimpan',
        'order_id' => $id_pesanan,
        'payment_id' => $id_pembayaran,
        'payment_method' => $metode_bayar,
        'status' => $status_pembayaran,
        'order_status' => $status_pesanan
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    error_log("Order processing error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'kode_meja' => $kode_meja ?? 'not set',
            'id_meja' => $id_meja ?? 'not set',
            'total_harga' => $total_harga ?? 'not set',
            'items_count' => isset($data['items']) ? count($data['items']) : 0,
            'metode_bayar' => $metode_bayar ?? 'not set',
            'error_line' => $e->getLine()
        ]
    ]);
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>