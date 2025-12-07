<?php
require_once 'database/connect.php';

$query_terlaris = "SELECT m.*, 
                          COALESCE(SUM(dp.jumlah), 0) AS total_terjual
                   FROM menu m
                   LEFT JOIN detail_pesanan dp 
                        ON m.id_menu = dp.id_menu
                   GROUP BY m.id_menu
                   ORDER BY total_terjual DESC
                   LIMIT 4";
$result_terlaris = mysqli_query($conn, $query_terlaris);
$query_menu = "SELECT * FROM menu ORDER BY kategori, nama_menu";
$result_menu = mysqli_query($conn, $query_menu);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesehan Tepi Sawah - Pengalaman Kuliner Terbaik</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-color: #2d5016;
    --secondary-color: #6b8e23;
    --accent-color: #ff6b35;
    --dark-bg: #1a1a1a;
    --light-bg: #f5f5f5;
    --text-dark: #333;
    --text-light: #666;
    --white: #ffffff;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--text-dark);
    overflow-x: hidden;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}


.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: rgba(26, 26, 26, 0.95);
    backdrop-filter: blur(10px);
    padding: 1rem 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.navbar .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-brand {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--white);
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav-brand i {
    color: var(--accent-color);
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 2rem;
    align-items: center;
}

.nav-menu a {
    color: var(--white);
    text-decoration: none;
    transition: color 0.3s;
    font-size: 0.95rem;
}

.nav-menu a:hover {
    color: var(--accent-color);
}

.btn-primary {
    background: var(--accent-color);
    padding: 0.7rem 1.5rem;
    border-radius: 25px;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #ff5722;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
}


.hero {
    height: 100vh;
    background: url('asset/LendingPage/bacground.jpg') center/cover no-repeat;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: var(--white);
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(45, 80, 22, 0.8), rgba(26, 26, 26, 0.7));
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: var(--accent-color);
}

.hero h1 {
    font-size: 4rem;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.hero p {
    font-size: 1.3rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.btn-hero {
    display: inline-block;
    background: var(--accent-color);
    color: var(--white);
    padding: 1rem 2.5rem;
    border-radius: 30px;
    text-decoration: none;
    font-size: 1.1rem;
    transition: all 0.3s;
}

.btn-hero:hover {
    background: #ff5722;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
}



@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0) translateX(-50%);
    }
    40% {
        transform: translateY(-10px) translateX(-50%);
    }
    60% {
        transform: translateY(-5px) translateX(-50%);
    }
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.section-header p {
    color: var(--text-light);
    font-size: 1.1rem;
}

.menu-terlaris {
    padding: 5rem 0;
    background: linear-gradient(135deg, #fff8f0 0%, #ffe8d6 100%);
}

.badge-terlaris {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, #ff6b35, #ff5722);
    color: var(--white);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    z-index: 10;
}

.badge-terlaris i {
    animation: fire 1s infinite alternate;
}

@keyframes fire {
    from {
        transform: scale(1);
    }
    to {
        transform: scale(1.2);
    }
}

.menu-section {
    padding: 5rem 0;
    background: var(--white);
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
}

.menu-card {
    background: var(--white);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
    position: relative;
}

.menu-card.featured {
    border: 2px solid var(--accent-color);
}

.menu-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}

.menu-card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.menu-card-content {
    padding: 1.5rem;
}

.menu-card h3 {
    font-size: 1.3rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.menu-card p {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.menu-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--accent-color);
}

.sold-count {
    background: var(--light-bg);
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    color: var(--text-dark);
}

.sold-count i {
    color: var(--accent-color);
}

/* Galeri Section */
.galeri-section {
    padding: 5rem 0;
    background: var(--light-bg);
}

.galeri-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.galeri-item {
    position: relative;
    overflow: hidden;
    border-radius: 10px;
    height: 250px;
    cursor: pointer;
}

.galeri-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.galeri-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(45, 80, 22, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.galeri-overlay i {
    color: var(--white);
    font-size: 2rem;
}

.galeri-item:hover img {
    transform: scale(1.1);
}

.galeri-item:hover .galeri-overlay {
    opacity: 1;
}

.tentang-section {
    padding: 5rem 0;
    background: var(--white);
}

.tentang-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.tentang-text h2 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.tentang-text p {
    color: var(--text-light);
    margin-bottom: 1rem;
    line-height: 1.8;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 2rem;
}

.stat-item {
    text-align: center;
    padding: 1.5rem;
    background: var(--light-bg);
    border-radius: 10px;
}

.stat-item i {
    font-size: 2.5rem;
    color: var(--accent-color);
    margin-bottom: 0.5rem;
}

.stat-item h3 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 0.3rem;
}

.stat-item p {
    color: var(--text-light);
    font-size: 0.9rem;
}

.tentang-image img {
    width: 100%;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.kontak-section {
    padding: 5rem 0;
    background: var(--light-bg);
}

.kontak-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}

.kontak-info {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.kontak-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.kontak-item i {
    font-size: 1.5rem;
    color: var(--accent-color);
    margin-top: 0.2rem;
}

.kontak-item h4 {
    color: var(--primary-color);
    margin-bottom: 0.3rem;
}

.kontak-item p {
    color: var(--text-light);
    line-height: 1.6;
}

.kontak-form-container {
    background: var(--white);
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.kontak-form-container h3 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.kontak-form input,
.kontak-form textarea {
    width: 100%;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: inherit;
}

.kontak-form input:focus,
.kontak-form textarea:focus {
    outline: none;
    border-color: var(--accent-color);
}

.btn-submit {
    width: 100%;
    background: var(--accent-color);
    color: var(--white);
    padding: 1rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit:hover {
    background: #ff5722;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
}

/* Footer */
.footer {
    background: var(--dark-bg);
    color: var(--white);
    padding: 3rem 0 1rem;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-section h3,
.footer-section h4 {
    margin-bottom: 1rem;
    color: var(--accent-color);
}

.footer-section p,
.footer-section li {
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.8;
}

.footer-section ul {
    list-style: none;
}

.footer-section ul li a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.3s;
}

.footer-section ul li a:hover {
    color: var(--accent-color);
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-links a {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: var(--white);
    transition: all 0.3s;
}

.social-links a:hover {
    background: var(--accent-color);
    transform: translateY(-3px);
}

.footer-bottom {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.5);
}


        .galeri-section {
            padding: 50px 20px;
        }

        .galeri-section .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .galeri-section h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .galeri-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            cursor: zoom-in;
        }

        .galeri-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: 0.3s ease;
        }

        .galeri-item:hover img {
            transform: scale(1.1);
            filter: brightness(70%);
        }

        .galeri-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: 0.3s ease;
            color: white;
            font-size: 40px;
        }

        .galeri-item:hover .galeri-overlay {
            opacity: 1;
        }

@media (max-width: 768px) {
    .nav-menu {
        display: none;
    }
    
    .hero h1 {
        font-size: 2.5rem;
    }
    
    .hero p {
        font-size: 1rem;
    }
    
    .tentang-content,
    .kontak-grid {
        grid-template-columns: 1fr;
    }
    
    .stats {
        grid-template-columns: 1fr;
    }
    
    .menu-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<body>
    
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <i class="fas fa-utensils"></i>
                Lesehan Tepi Sawah
            </div>
            <ul class="nav-menu">
                <li><a href="#home">Beranda</a></li>
                <li><a href="#menu-terlaris">Menu Terlaris</a></li>
                <li><a href="#galeri">Galeri</a></li>
                <li><a href="#tentang">Tentang</a></li>
                <li><a href="#kontak">Kontak</a></li>
            </ul>
        </div>
    </nav>

    <section id="home" class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <i class="fas fa-utensils hero-icon"></i>
            <h1>Lesehan Tepi Sawah</h1>
            <p>Pengalaman Kuliner Terbaik dengan Cita Rasa Istimewa</p>
            <a href="#menu-terlaris" class="btn-hero">Lihat Menu</a>
        </div>
    </section>
   <section id="menu-terlaris" class="menu-terlaris">
    <div class="container">
        <div class="section-header">
            <h2>Menu Terlaris Bulan Ini</h2>
            <p>Menu favorit pelanggan kami selama 30 hari terakhir</p>
        </div>

        <div class="menu-grid">
            <?php while($menu = mysqli_fetch_assoc($result_terlaris)): ?>
            <div class="menu-card featured">
                <div class="badge-terlaris">
                    <i class="fas fa-fire"></i> Terlaris
                </div>

                <img src="assets/uploads/<?= htmlspecialchars($menu['gambar']) ?>" 
                     alt="<?= htmlspecialchars($menu['nama_menu']) ?>">

                <div class="menu-card-content">
                    <h3><?= htmlspecialchars($menu['nama_menu']) ?></h3>
                    <div class="menu-footer">
                        <span class="price">
                            Rp <?= number_format($menu['harga'], 0, ',', '.') ?>
                        </span>

                        <span class="sold-count">
                            <i class="fas fa-shopping-cart"></i>
                            <?= $menu['total_terjual'] ?> terjual
                        </span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
    
    <section id="galeri" class="galeri-section">
    <div class="container">
        <div class="section-header">
            <h2>Galeri Kami</h2>
            <p>Lihat suasana dan berbagai menu lezat di galeri online kami</p>
        </div>

        <div class="galeri-grid">
            <div class="galeri-item">
                <img src="asset/LendingPage/tepi sawah 1.jpg" alt="s">
                <div class="galeri-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>

            <div class="galeri-item">
                <img src="asset/LendingPage/tepi sawah 2.jpg" alt="">
                <div class="galeri-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>

            <div class="galeri-item">
                <img src="asset/LendingPage/tepi sawah 3.jpg" alt="">
                <div class="galeri-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>

            <div class="galeri-item">
                <img src="asset/LendingPage/tepi sawah 4.jpg" alt="">
                <div class="galeri-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>

        </div>
    </div>
</section>
    <section id="tentang" class="tentang-section">
        <div class="container">
            <div class="tentang-content">
                <div class="tentang-text">
                    <h2>Tentang Kami</h2>
                    <p>Lesehan Tepi Sawah merupakan restoran yang menawarkan konsep unik dengan pemandangan sawah yang indah. Kami berkomitmen memberikan pengalaman kuliner terbaik dengan cita rasa khas Indonesia.</p>
                    <p>Dengan suasana yang nyaman dan menu berkualitas, kami siap memberikan kepuasan maksimal kepada setiap pengunjung. Dibuka sejak tahun 2020, kami terus berinovasi dalam menghadirkan menu-menu terbaik.</p>
                </div>
                <div class="tentang-image">
                    <img src="asset/LendingPage/tepi sawah 5.jpg" alt="Suasana Restoran">
                </div>
            </div>
        </div>
    </section>
    <section id="kontak" class="kontak-section">
        <div class="container">
            <div class="section-header">
                <h2>Hubungi Kami</h2>
                <p>Silahkan lengkapi form di bawah untuk setiap pertanyaan anda.</p>
            </div>
            <div class="kontak-grid">
                <div class="kontak-info">
                    <div class="kontak-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Alamat</h4>
                            <p>Jl. Raya Wringin<br>Kecamatan Suboh, Situbondo<br>Jawa Timur</p>
                        </div>
                    </div>
                    <div class="kontak-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Telepon</h4>
                            <p>+62 821 3675 2899</p>
                        </div>
                    </div>
                    <div class="kontak-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@lesehantepisawah.com</p>
                        </div>
                    </div>
                    <div class="kontak-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Jam Operasional</h4>
                            <p>Senin - Minggu<br>09:00 - 21:00 WIB</p>
                        </div>
                    </div>
                </div>
       <div class="kontak-form-container">
    <h3>Kirim Pesan</h3>

    <form class="kontak-form" onsubmit="kirimWhatsapp(event)">
        <input type="text" id="nama" placeholder="Nama" required>
        <input type="email" id="email" placeholder="Email" required>
        <input type="tel" id="telepon" placeholder="Telepon" required>
        <textarea id="pesan" placeholder="Pesan Anda" rows="5" required></textarea>

        <button type="submit" class="btn-submit">Kirim Pesan</button>
    </form>
</div>

<script>
function kirimWhatsapp(e) {
    e.preventDefault(); 

    const nama = document.getElementById("nama").value;
    const email = document.getElementById("email").value;
    const telepon = document.getElementById("telepon").value;
    const pesan = document.getElementById("pesan").value;

    const nomorTujuan = "6285812215646"; 

    const text = 
        "Halo, saya ingin mengirim pesan:\n\n" +
        "Nama: " + nama + "\n" +
        "Email: " + email + "\n" +
        "Telepon: " + telepon + "\n" +
        "Pesan: " + pesan;

    const url = "https://api.whatsapp.com/send?phone=" + nomorTujuan + "&text=" + encodeURIComponent(text);

    window.open(url, "_blank");
}
</script>


            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Lesehan Tepi Sawah</h3>
                    <p>Nikmati pengalaman kuliner dengan pemandangan sawah yang indah dan menu berkualitas tinggi.</p>
                </div>
                <div class="footer-section">
                    <h4>Menu</h4>
                    <ul>
                        <li><a href="#home">Beranda</a></li>
                        <li><a href="#menu">Menu</a></li>
                        <li><a href="#galeri">Galeri</a></li>
                        <li><a href="#tentang">Tentang</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Jam Buka</h4>
                    <p>Senin - Minggu<br>09:00 - 22:00 WIB</p>
                </div>
                <div class="footer-section">
                    <h4>Ikuti Kami</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Lesehan Tepi Sawah. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
<script>

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(26, 26, 26, 1)';
    } else {
        navbar.style.background = 'rgba(26, 26, 26, 0.95)';
    }
});

const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.menu-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'all 0.6s ease-out';
    observer.observe(card);
});

document.querySelectorAll('.galeri-item').forEach(item => {
    item.style.opacity = '0';
    item.style.transform = 'scale(0.9)';
    item.style.transition = 'all 0.5s ease-out';
    observer.observe(item);
});

const contactForm = document.querySelector('.kontak-form');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
    
        alert('Terima kasih! Pesan Anda telah terkirim. Kami akan segera menghubungi Anda.');
        this.reset();
    });
}

document.querySelectorAll('.galeri-item').forEach(item => {
    item.addEventListener('click', function() {
        const img = this.querySelector('img');
        console.log('Image clicked:', img.src);
    });
});


function animateCounter(element, target, duration) {
    let current = 0;
    const increment = target / (duration / 16);
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target + '+';
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current) + '+';
        }
    }, 16);
}

const statsSection = document.querySelector('.stats');
if (statsSection) {
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statItems = entry.target.querySelectorAll('.stat-item h3');
                statItems.forEach((item, index) => {
                    const targets = [10, 50, 4.9];
                    setTimeout(() => {
                        const text = item.textContent;
                        const num = parseFloat(text.replace(/[^0-9.]/g, ''));
                        item.textContent = '0';
                        
                        if (index === 2) {
                            
                            animateRating(item, 4.9);
                        } else {
                            animateCounter(item, targets[index], 2000);
                        }
                    }, index * 200);
                });
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    statsObserver.observe(statsSection);
}

function animateRating(element, target) {
    let current = 0;
    const increment = target / 100;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target + '★';
            clearInterval(timer);
        } else {
            element.textContent = current.toFixed(1) + '★';
        }
    }, 20);
}

window.addEventListener('load', function() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s';
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
});
</script>
</html>