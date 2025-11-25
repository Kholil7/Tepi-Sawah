<?php
require_once '../../config/session.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT id_pengguna, nama, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        setUserSession([
            'id_pengguna' => $user['id_pengguna'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);
        
        if ($user['role'] === 'kasir') {
            header("Location: /kasir/inside/dashboard_kasir.php");
        } else {
            header("Location: /owner/inside/dashboard.php");
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register</title>
    <?php $version = filemtime('../../css/styleOwner.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/styleOwner.css?v=<?php echo $version; ?>">
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  </head>
  <body>
    <div class="container">
      <div class="frame">
        <h1>Register Owner</h1>
        <form action="../include/registerProses.php" method="POST">
          <label for="">Nama</label>
          <input type="text" name="nama" placeholder="Masukkan nama" required />

          <label for="">Email</label>
          <input
            type="email"
            name="email"
            placeholder="Masukkan email"
            required
          />
          <input type="hidden" name="role" value="owner" />

          <label for="">Password</label>
          <div class="password-container">
            <input
              type="password"
              name="password"
              id="password"
              placeholder="Masukkan password"
              required
            />
            <i id="togglePassword" class="fas fa-eye"></i>
          </div>

          <button type="submit">Daftar</button>
          <p>
            Jika anda sudah memiliki akun silakan <a href="login.php">Masuk.</a>
          </p>
        </form>
      </div>
    </div>

    <script
      src="https://kit.fontawesome.com/a076d05399.js"
      crossorigin="anonymous"
    ></script>

    <script>
      const togglePassword = document.querySelector("#togglePassword");
      const passwordInput = document.querySelector("#password");

      togglePassword.addEventListener("click", function () {
        const type =
          passwordInput.getAttribute("type") === "password"
            ? "text"
            : "password";
        passwordInput.setAttribute("type", type);

        this.classList.toggle("fa-eye");
        this.classList.toggle("fa-eye-slash");
      });
    </script>
  </body>
</html>
