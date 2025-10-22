<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
      crossorigin="anonymous"
    />

    <link rel="stylesheet" href="../css/makanan.css" />

    <!-- Google font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />

    <!-- font awasome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    />
    <title>Makanan</title>
  </head>
  <body>
    <div class="container">
      <header>
        <a href="../menu.html" class="fa-solid fa-arrow-left"></a>
        <p>Makanan</p>
      </header>
      <main>
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Cari makanan..." />
        </div>
        <!-- 🍽️ Daftar menu -->
        <div class="menu-list" id="menuList">
          <div class="wrapper" data-name="Nasi Goreng">
            <img src="../asset/menu/makanan/rice.png" alt="" />
            <h3>Nasi Goreng</h3>
            <p>Rp. 10.000</p>
          </div>

          <div class="wrapper" data-name="Mie Ayam">
            <img src="../asset/menu/makanan/rice.png" alt="" />
            <h3>Mie Ayam</h3>
            <p>Rp. 12.000</p>
          </div>

          <div class="wrapper" data-name="Sate Ayam">
            <img src="../asset/menu/makanan/rice.png" alt="" />
            <h3>Sate Ayam</h3>
            <p>Rp. 15.000</p>
          </div>

          <div class="wrapper" data-name="Nasi Campur">
            <img src="../asset/menu/makanan/rice.png" alt="" />
            <h3>Nasi Campur</h3>
            <p>Rp. 13.000</p>
          </div>

          
        </div>
      </main>
    </div>

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
      crossorigin="anonymous"
    ></script>

    <!-- ...kode HTML kamu... -->

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const wrappers = document.querySelectorAll(".wrapper");

        wrappers.forEach((wrapper) => {
          const plus = wrapper.querySelector(".plus");
          const minus = wrapper.querySelector(".minus");
          const quantity = wrapper.querySelector(".quantity");
          let count = 1;

          plus.addEventListener("click", () => {
            count++;
            quantity.value = count;
          });

          minus.addEventListener("click", () => {
            if (count > 1) {
              count--;
              quantity.value = count;
            }
          });
        });
      });

      const searchInput = document.getElementById("searchInput");
      const wrappers = document.querySelectorAll(".wrapper");

      searchInput.addEventListener("keyup", function () {
        const searchValue = this.value.toLowerCase();

        wrappers.forEach((wrapper) => {
          const name = wrapper.dataset.name.toLowerCase();
          if (name.includes(searchValue)) {
            wrapper.style.display = "block";
          } else {
            wrapper.style.display = "none";
          }
        });
      });
    </script>

    <!-- Option 2: Separate Popper and Bootstrap JS -->
    <!--
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    -->
  </body>
</html>
