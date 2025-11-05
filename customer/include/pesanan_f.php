<?php
require '../../database/connect.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error langsung
ini_set('log_errors', 1);

// Ambil data dari request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log untuk debugging
error_log("Received data: " . print_r($data, true));

// Validasi data
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

// Validasi id_meja
if (!isset($data['id_meja']) || empty($data['id_meja'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID Meja tidak valid',
        'debug' => 'id_meja: ' . ($data['id_meja'] ?? 'null')
    ]);
    exit;
}

try {
    // Cek koneksi database
    if ($conn->connect_error) {
        throw new Exception('Koneksi database gagal: ' . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    // Data pesanan
    $id_meja = intval($data['id_meja']);
    $catatan = isset($data['notes']) && !empty($data['notes']) ? $data['notes'] : null;
    $metode_bayar = isset($data['payment_method']) ? $data['payment_method'] : 'cash';
    $total_harga = isset($data['total_amount']) ? floatval($data['total_amount']) : 0;
    
    // Validasi total harga
    if ($total_harga <= 0) {
        throw new Exception('Total harga tidak valid: ' . $total_harga);
    }

    // Tentukan jenis_pesanan dan status_pesanan
    $jenis_pesanan = 'dine_in';
    $status_pesanan = 'menunggu';
    $waktu_pesan = date('Y-m-d H:i:s');
    $dibuat_oleh = null;

    // 1. Insert ke tabel pesanan
    $query_pesanan = "INSERT INTO pesanan (
        id_meja,
        dibuat_oleh,
        waktu_pesan,
        jenis_pesanan,
        status_pesanan,
        metode_bayar,
        total_harga,
        catatan,
        diterima_oleh
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)";
    
    $stmt_pesanan = $conn->prepare($query_pesanan);
    
    if (!$stmt_pesanan) {
        throw new Exception('Prepare statement pesanan gagal: ' . $conn->error);
    }
    
    $stmt_pesanan->bind_param(
        'iissssds',
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
    
    $id_pesanan = $conn->insert_id;
    
    if (!$id_pesanan) {
        throw new Exception('Gagal mendapatkan ID pesanan yang baru dibuat');
    }

    // 2. Insert detail pesanan untuk setiap item
    // CATATAN: subtotal adalah generated column, tidak perlu di-insert
    $query_detail = "INSERT INTO detail_pesanan (
        id_pesanan,
        id_menu,
        jumlah,
        harga_satuan,
        status_item,
        catatan_item
    ) VALUES (?, ?, ?, ?, 'menunggu', NULL)";
    
    $stmt_detail = $conn->prepare($query_detail);
    
    if (!$stmt_detail) {
        throw new Exception('Prepare statement detail pesanan gagal: ' . $conn->error);
    }
    
    foreach ($data['items'] as $index => $item) {
        if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['harga'])) {
            throw new Exception('Data item tidak lengkap pada index ' . $index);
        }
        
        $id_menu = intval($item['id']);
        $jumlah = intval($item['quantity']);
        $harga_satuan = floatval($item['harga']);
        
        $stmt_detail->bind_param(
            'iiid',
            $id_pesanan,
            $id_menu,
            $jumlah,
            $harga_satuan
        );
        
        if (!$stmt_detail->execute()) {
            throw new Exception('Execute insert detail pesanan gagal: ' . $stmt_detail->error);
        }
    }

    // 3. Buat record pembayaran
    $status_pembayaran = ($metode_bayar === 'qris') ? 'sudah_bayar' : 'belum_bayar';
    $waktu_pembayaran = date('Y-m-d H:i:s');
    $jumlah_tagihan = $total_harga;
    $jumlah_dibayar = ($metode_bayar === 'qris') ? $total_harga : 0;
    $kembalian = 0;
    
    $query_pembayaran = "INSERT INTO pembayaran (
        id_pesanan,
        metode,
        status,
        jumlah_tagihan,
        jumlah_dibayar,
        kembalian,
        waktu_pembayaran,
        bukti_pembayaran
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)";
    
    $stmt_pembayaran = $conn->prepare($query_pembayaran);
    
    if (!$stmt_pembayaran) {
        throw new Exception('Prepare statement pembayaran gagal: ' . $conn->error);
    }
    
    $stmt_pembayaran->bind_param(
        'issddds',
        $id_pesanan,
        $metode_bayar,
        $status_pembayaran,
        $jumlah_tagihan,
        $jumlah_dibayar,
        $kembalian,
        $waktu_pembayaran
    );
    
    if (!$stmt_pembayaran->execute()) {
        throw new Exception('Execute insert pembayaran gagal: ' . $stmt_pembayaran->error);
    }
    
    $id_pembayaran = $conn->insert_id;

    // 4. Update status meja
    if ($id_meja) {
        $query_update_meja = "UPDATE meja SET status_meja = 'menunggu_pembayaran' WHERE id_meja = ?";
        $stmt_meja = $conn->prepare($query_update_meja);
        
        if (!$stmt_meja) {
            throw new Exception('Prepare statement update meja gagal: ' . $conn->error);
        }
        
        $stmt_meja->bind_param('i', $id_meja);
        
        if (!$stmt_meja->execute()) {
            throw new Exception('Execute update meja gagal: ' . $stmt_meja->error);
        }
    }

    // Commit transaction
    $conn->commit();

    // Response sukses
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
    // Rollback jika terjadi error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Order processing error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'id_meja' => $id_meja ?? 'not set',
            'total_harga' => $total_harga ?? 'not set',
            'items_count' => isset($data['items']) ? count($data['items']) : 0,
            'metode_bayar' => $metode_bayar ?? 'not set'
        ]
    ]);
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>