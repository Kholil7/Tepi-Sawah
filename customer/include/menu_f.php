<?php
/**
 * Fungsi untuk mengambil menu berdasarkan kategori
 * 
 * @param object $conn Koneksi database
 * @param string $kategori Kategori menu (semua, makanan, minuman, lainnya)
 * @return array Data menu
 */
function getMenuByKategori($conn, $kategori = 'semua') {
    try {
        if ($kategori == 'semua') {
            $query = "SELECT * FROM id_menu ORDER BY nama_menu ASC";
            $stmt = $conn->prepare($query);
        } else {
            $query = "SELECT * FROM id_menu WHERE kategori = :kategori ORDER BY nama_menu ASC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':kategori', $kategori);
        }
        
        $stmt->execute();
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $menus;
    } catch(PDOException $e) {
        error_log("Error fetching menu: " . $e->getMessage());
        return [];
    }
}

/**
 * Fungsi untuk mengambil semua menu (tanpa filter)
 * 
 * @param object $conn Koneksi database
 * @return array Data menu
 */
function getAllMenu($conn) {
    try {
        $query = "SELECT * FROM id_menu ORDER BY kategori, nama_menu ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching all menu: " . $e->getMessage());
        return [];
    }
}

/**
 * Fungsi untuk mengambil detail menu berdasarkan ID
 * 
 * @param object $conn Koneksi database
 * @param int $id_menu ID menu
 * @return array|null Data menu atau null jika tidak ditemukan
 */
function getMenuById($conn, $id_menu) {
    try {
        $query = "SELECT * FROM id_menu WHERE id_menu = :id_menu";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_menu', $id_menu);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching menu by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Fungsi untuk mengambil menu yang aktif saja
 * 
 * @param object $conn Koneksi database
 * @param string $kategori Kategori menu (opsional)
 * @return array Data menu aktif
 */
function getActiveMenu($conn, $kategori = null) {
    try {
        if ($kategori && $kategori != 'semua') {
            $query = "SELECT * FROM id_menu 
                     WHERE status_menu = 'aktif' AND kategori = :kategori 
                     ORDER BY nama_menu ASC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':kategori', $kategori);
        } else {
            $query = "SELECT * FROM id_menu WHERE status_menu = 'aktif' ORDER BY nama_menu ASC";
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching active menu: " . $e->getMessage());
        return [];
    }
}

/**
 * Fungsi untuk menghitung jumlah menu per kategori
 * 
 * @param object $conn Koneksi database
 * @return array Jumlah menu per kategori
 */
function countMenuByKategori($conn) {
    try {
        $query = "SELECT kategori, COUNT(*) as jumlah 
                 FROM id_menu 
                 GROUP BY kategori";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['kategori']] = $row['jumlah'];
        }
        
        return $result;
    } catch(PDOException $e) {
        error_log("Error counting menu: " . $e->getMessage());
        return [];
    }
}

/**
 * Fungsi untuk format harga rupiah
 * 
 * @param float $harga Harga dalam angka
 * @return string Harga terformat
 */
function formatRupiah($harga) {
    return "Rp " . number_format($harga, 0, ',', '.');
}

/**
 * Fungsi untuk validasi status menu
 * 
 * @param string $status Status menu
 * @return bool True jika valid, false jika tidak
 */
function isValidStatus($status) {
    return in_array($status, ['aktif', 'nonaktif']);
}

/**
 * Fungsi untuk validasi kategori menu
 * 
 * @param string $kategori Kategori menu
 * @return bool True jika valid, false jika tidak
 */
function isValidKategori($kategori) {
    return in_array($kategori, ['makanan', 'minuman', 'lainnya', 'semua']);
}
?>