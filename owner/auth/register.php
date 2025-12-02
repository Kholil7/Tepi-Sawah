<?php
require_once '../../config/session.php';
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register Owner - Shopping Cart</title>
    <?php $version = filemtime('../../css/styleOwner.css'); ?>
    <link rel="stylesheet" type="text/css" href="../../css/styleOwner.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  </head>
  <body>
    <div class="container">
      <div class="frame">
        <!-- Shopping Cart Icon -->
        <div class="logo-container">
          <div class="cart-icon">
            <i class="fas fa-utensils"></i>
          </div>
        </div>

        <h1>Register Owner</h1>
        
        <form action="../include/registerProses.php" method="POST">
          <div class="input-group">
            <label for="nama">Nama Lengkap</label>
            <div class="input-wrapper">
              <i class="fas fa-user input-icon"></i>
              <input type="text" name="nama" id="nama" placeholder="Masukkan nama lengkap" required />
            </div>
          </div>

          <div class="input-group">
            <label for="email">Email</label>
            <div class="input-wrapper">
              <i class="fas fa-envelope input-icon"></i>
              <input type="email" name="email" id="email" placeholder="Masukkan email anda" required />
            </div>
          </div>
          
          <input type="hidden" name="role" value="owner" />

          <div class="input-group">
            <label for="password">Password</label>
            <div class="password-container">
              <div class="input-wrapper">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="password" placeholder="Buat password anda" required />
                <i id="togglePassword" class="fas fa-eye"></i>
              </div>
            </div>
          </div>

          <button type="submit">Daftar</button>
          
          <p>Sudah memiliki akun? <a href="login.php">Masuk sekarang</a></p>
        </form>
      </div>
    </div>

    <script>
      const togglePassword = document.querySelector("#togglePassword");
      const passwordInput = document.querySelector("#password");

      togglePassword.addEventListener("click", function () {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);

        this.classList.toggle("fa-eye");
        this.classList.toggle("fa-eye-slash");
      });
    </script>
  </body>
</html>