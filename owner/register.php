<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
        <link rel="stylesheet" href="../css/styleOwner.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>
  <div class="container">
    <div class="frame">
      <h1>Register</h1>
      <form action="">
        <label for="">Nama</label>
        <input type="text" placeholder="Masukkan nama">

        <label for="">Email</label>
        <input type="text" placeholder="Masukkan email">

        <label for="">Password</label>
        <div class="password-container">
          <input type="password" id="password" placeholder="Masukkan password">
          <i id="togglePassword" class="fas fa-eye"></i>
        </div>

        <button type="submit">Daftar</button>
        <p>Jika anda sudah memiliki akun silakan <a href="login.php">Masuk.</a></p>
      </form>
    </div>
  </div>

   <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

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