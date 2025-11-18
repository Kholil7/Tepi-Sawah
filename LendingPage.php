<?php
// Koneksi ke database
require_once 'database/connect.php';

try {
    $sql = "SELECT 
                m.id_menu,
                m.nama_menu,
                m.kategori,
                m.harga,
                m.gambar,
                SUM(dp.jumlah) as jumlah_terjual
            FROM menu m
            INNER JOIN detail_pesanan dp ON m.id_menu = dp.id_menu
            INNER JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
            WHERE p.status_pesanan IN ('dibayar', 'selesai')
            AND p.waktu_pesan >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            AND m.status_menu = 'aktif'
            GROUP BY m.id_menu, m.nama_menu, m.kategori, m.harga, m.gambar
            ORDER BY jumlah_terjual DESC
            LIMIT 4";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $result = $stmt->get_result();
    $menus = $result->fetch_all(MYSQLI_ASSOC);

    if (!$menus) {
        $menus = [];
    }

} catch(Exception $e) {
    $menus = [];
}


// Function untuk format harga
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tepi Sawah</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>
<body class="bg-white text-gray-800">

  <!-- Navbar -->
  <header class="fixed top-0 left-0 w-full bg-white shadow z-50">
    <nav class="max-w-7xl mx-auto flex justify-between items-center py-4 px-6">
      <h1 class="text-2xl font-bold">🍽️ Tepi Sawah</h1>
      <ul class="hidden md:flex gap-6">
        <li><a href="#home" class="hover:text-yellow-600">Beranda</a></li>
        <li><a href="#tentang" class="hover:text-yellow-600">Tentang</a></li>
        <li><a href="#menu" class="hover:text-yellow-600">Menu</a></li>
        <li><a href="#galeri" class="hover:text-yellow-600">Galeri</a></li>
        <li><a href="#kontak" class="hover:text-yellow-600">Kontak</a></li>
      </ul>
      <a href="#menu" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">Pesan Sekarang</a>
    </nav>
  </header>

  <!-- Hero Section -->
  <section id="home" class="h-screen bg-[url('https://images.unsplash.com/photo-1600891964599-f61ba0e24092')] bg-cover bg-center flex flex-col justify-center items-center text-center text-white relative">
    <div class="absolute inset-0 bg-black bg-opacity-60"></div>
    <div class="relative z-10">
      <h2 class="text-4xl md:text-6xl font-bold mb-4">Tepi Sawah</h2>
      <p class="text-lg md:text-xl mb-6">Pengalaman Kuliner Terbaik dengan Cita Rasa Istimewa</p>
      <a href="#menu" class="bg-yellow-600 px-6 py-3 rounded-lg text-white font-semibold hover:bg-yellow-700">Pesan Sekarang</a>
    </div>
  </section>

  <!-- Menu Populer (1 Bulan Terakhir) -->
  <section id="menu" class="bg-gray-50 py-16 px-6">
    <div class="max-w-7xl mx-auto text-center">
      <h3 class="text-3xl font-bold mb-4">Menu Populer Bulan Ini</h3>
      <p class="text-gray-600 mb-10">Hidangan terlaris yang paling digemari pelanggan dalam 1 bulan terakhir</p>
      <div class="grid md:grid-cols-4 gap-6">
        <?php if(count($menus) > 0) { ?>
          <?php foreach($menus as $menu) { ?>
          <div class="bg-white shadow rounded-lg p-4 relative overflow-hidden hover:shadow-xl transition-shadow duration-300">
            <div class="absolute top-3 right-3 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs px-3 py-1 rounded-full font-semibold shadow-lg z-10">
              🔥 <?php echo intval($menu['jumlah_terjual']); ?>x Terjual
            </div>
            <img src="../../uploads/<?php echo htmlspecialchars($menu['gambar']); ?>" 
                 class="rounded-lg mb-4 w-full h-48 object-cover" 
                 alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>"
                 onerror="this.src='../../uploads/default.png'">
            <h4 class="font-semibold text-lg"><?php echo htmlspecialchars($menu['nama_menu']); ?></h4>
            <p class="text-sm text-gray-500 mb-2 capitalize"><?php echo htmlspecialchars($menu['kategori']); ?></p>
            <p class="font-bold text-yellow-600 text-lg"><?php echo formatRupiah($menu['harga']); ?></p>
          </div>
          <?php } ?>
        <?php } else { ?>
          <div class="col-span-4 text-center text-gray-500 py-10">
            <p>Belum ada data penjualan dalam 1 bulan terakhir. Menu populer akan muncul setelah ada transaksi.</p>
          </div>
        <?php } ?>
      </div>
    </div>
  </section>

  <!-- Galeri -->
  <section id="galeri" class="py-16 px-6">
    <div class="max-w-7xl mx-auto text-center">
      <h3 class="text-3xl font-bold mb-4">Galeri Kami</h3>
      <p class="text-gray-600 mb-10">Lihat keindahan hidangan dan suasana restoran kami.</p>
      <div class="grid md:grid-cols-3 gap-6">
        <img src="asset/LendingPage/tepi sawah 1.jpg" class="rounded-lg shadow">
        <img src="asset/LendingPage/tepi sawah 5.jpg" class="rounded-lg shadow">
        <img src="asset/LendingPage/tepi sawah 3.jpg"class="rounded-lg shadow">
        <img src="asset/LendingPage/tepi sawah 4.jpg" class="rounded-lg shadow">
        <img src="asset/LendingPage/tepi sawah 6.jpg" class="rounded-lg shadow">
        <img src="asset/LendingPage/tepi sawah 2.jpg" class="rounded-lg shadow">
      </div>
    </div>
  </section>

  <!-- Tentang Kami -->
  <section id="tentang" class="py-16 px-6 max-w-7xl mx-auto grid md:grid-cols-2 gap-10 items-center">
    <div>
      <h3 class="text-3xl font-bold mb-4">Tentang Kami</h3>
      <p class="text-gray-700 mb-6">Tepi Sawah didirikan dengan passion untuk menghadirkan pengalaman kuliner tak terlupakan. Sejak tahun 2015, kami telah menyajikan berbagai hidangan berkualitas tinggi dari bahan-bahan segar pilihan.</p>
      <div class="flex gap-10">
      </div>
    </div>
    <img src="https://images.unsplash.com/photo-1600891963933-c7b4c0c51f9b" alt="Chef Cooking" class="rounded-2xl shadow-lg">
  </section>

  <!-- Kontak -->
  <section id="kontak" class="py-16 px-6">
    <div class="max-w-7xl mx-auto">
      <h3 class="text-3xl font-bold text-center mb-10">Hubungi Kami</h3>
      <div class="grid md:grid-cols-2 gap-10">
        <div>
          <p class="mb-2"><strong>Alamat:</strong> Jl. Raya Wringin, Buduan, Kec.Suboh, Kabupaten Situbondo, Jawa Timur</p>
          <p class="mb-2"><strong>Telepon:</strong> +62 812 3675 2899</p>
          <p class="mb-2"><strong>Email:</strong> info@savoria.com</p>
          <p><strong>Jam Operasional:</strong><br>Senin–Jumat: 09:00–21:00<br>Sabtu–Minggu: 09:00–21:00</p>
        </div>
        <form class="bg-gray-50 p-6 rounded-lg shadow">
          <input type="text" placeholder="Nama Lengkap" class="w-full p-3 mb-4 border rounded">
          <input type="email" placeholder="Email" class="w-full p-3 mb-4 border rounded">
          <input type="text" placeholder="Telepon" class="w-full p-3 mb-4 border rounded">
          <textarea placeholder="Pesan Anda" class="w-full p-3 mb-4 border rounded h-32"></textarea>
          <button class="bg-yellow-600 text-white px-6 py-2 rounded hover:bg-yellow-700 w-full">Kirim Pesan</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-900 text-gray-400 py-10 text-sm">
    <div class="max-w-7xl mx-auto grid md:grid-cols-4 gap-8 px-6">
      <div>
        <h4 class="text-white font-semibold mb-3">Tepi Sawah</h4>
        <p>Pengalaman kuliner terbaik dengan cita rasa istimewa.</p>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-3">Menu</h4>
        <ul>
          <li><a href="#tentang">Tentang Kami</a></li>
          <li><a href="#menu">Menu</a></li>
          <li><a href="#galeri">Galeri</a></li>
          <li><a href="#kontak">Kontak</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-3">Jam Buka</h4>
        <p>Senin - Jumat: 09:00 - 21:00<br>Sabtu - Minggu: 09:00 - 21:00</p>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-3">Ikuti Kami</h4>
        <div class="flex gap-3">
          <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" class="w-6"></a>
          <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/733/733558.png" class="w-6"></a>
          <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/733/733614.png" class="w-6"></a>
        </div>
      </div>
    </div>
    <p class="text-center text-gray-500 mt-10">© 2025 Tepi Sawah. All rights reserved.</p>
  </footer>

</body>
</html>

<?php
$conn = null;
?>