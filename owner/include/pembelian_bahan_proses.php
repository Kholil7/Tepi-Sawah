<?php
require_once '../../database/connect.php';

$tanggal_hari_ini = date('Y-m-d');
$total_pembelian_hari_ini = 0;
$total_pengeluaran = 0;
$pembelian_data = [];

$query_total_pembelian = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pembelian WHERE tanggal_pembelian = '$tanggal_hari_ini'");
if ($query_total_pembelian) {
    $total_pembelian_hari_ini = mysqli_fetch_assoc($query_total_pembelian)['total'] ?? 0;
}

$query_pengeluaran = mysqli_query($conn, "SELECT SUM(total) AS total_pengeluaran FROM pembelian");
if ($query_pengeluaran) {
    $total_pengeluaran = mysqli_fetch_assoc($query_pengeluaran)['total_pengeluaran'] ?? 0;
}

// Ambil data daftar pembelian
$query_pembelian = mysqli_query($conn, "
    SELECT p.id_pembelian, pr.nama_produk AS nama_bahan, dp.jumlah AS kuantitas,
           dp.harga_beli AS harga, p.tanggal_pembelian AS tanggal, dp.catatan
    FROM detail_pembelian dp
    JOIN pembelian p ON dp.id_pembelian = p.id_pembelian
    JOIN produk pr ON dp.id_produk = pr.id_produk
    ORDER BY p.tanggal_pembelian DESC
");

while ($row = mysqli_fetch_assoc($query_pembelian)) {
    $pembelian_data[] = $row;
}

include 'pembelian.html';
