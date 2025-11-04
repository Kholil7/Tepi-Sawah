<?php
require '../../database/connect.php';
function getMejaByKode($kode_unik, $conn) {
    if (empty($kode_unik)) return null;

    $stmt = $conn->prepare("SELECT * FROM meja WHERE kode_unik = ?");
    $stmt->bind_param("s", $kode_unik);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}
