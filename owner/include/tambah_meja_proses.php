<?php
require '../../database/connect.php';

// ========== TAMBAH MEJA ==========
if (isset($_POST['tambah_meja'])) {
  $nomor_meja = trim($_POST['nomor_meja']);
  $kode_unik = uniqid('MJ');
  $status_meja = 'kosong';
  $qrcode_url = 'uploads/qrcode_' . $kode_unik . '.png';
  $last_update = date('Y-m-d H:i:s');

  $stmt = $conn->prepare("INSERT INTO meja (nomor_meja, kode_unik, status_meja, qrcode_url, last_update) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("sssss", $nomor_meja, $kode_unik, $status_meja, $qrcode_url, $last_update);
  $stmt->execute();

  header("Location: index.php?success=1");
  exit;
}

// ========== EDIT MEJA ==========
if (isset($_POST['edit_meja'])) {
  $id_meja = $_POST['id_meja'];
  $nomor_meja = trim($_POST['nomor_meja']);
  $status_meja = $_POST['status_meja'];
  $last_update = date('Y-m-d H:i:s');

  $stmt = $conn->prepare("UPDATE meja SET nomor_meja=?, status_meja=?, last_update=? WHERE id_meja=?");
  $stmt->bind_param("sssi", $nomor_meja, $status_meja, $last_update, $id_meja);
  $stmt->execute();

  header("Location: index.php?updated=1");
  exit;
}

// ========== HAPUS MEJA ==========
if (isset($_GET['hapus'])) {
  $id = (int) $_GET['hapus'];
  $stmt = $conn->prepare("DELETE FROM meja WHERE id_meja=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();

  header("Location: index.php?deleted=1");
  exit;
}
?>
