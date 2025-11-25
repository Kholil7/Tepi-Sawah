<?php
// $host     = "sql106.infinityfree.com";
// $user     = "if0_40493381";
// $password = "9t6CHUY4Vde5";
// $database = "if0_40493381_db_resto";

// $conn = mysqli_connect($host, $user, $password, $database);

// if (!$conn) {
//     die("Koneksi database gagal: " . mysqli_connect_error());
// }

// date_default_timezone_set('Asia/Jakarta');
// mysqli_query($conn, "SET time_zone = '+07:00'");

$host     = "localhost";
$user     = "root";
$password = "";
$database = "dbresto_app";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

?>
