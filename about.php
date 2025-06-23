<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Our Team - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="images/logo.png" type="image/png" />
</head>
<body>
    <header>
        <div class="header-bar">
            <div class="logo-left">
                <a href="index.php">
                    <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline" />
                </a>
                <div class="search-center">
                    <form class="search-form" action="products.php" method="get">
                        <input type="text" name="search" placeholder="Welcome To BakeEase" />
                        <button type="submit">Search</button>
                    </form>
                </div>
            </div>
            <div class="header-right">
                <div class="nav-dropdown">
                    <span class="nav-toggle" id="navToggle" title="Navigation Menu">☰</span>
                    <div class="dropdown-menu" id="navMenu">
                        <a href="index.php">Home</a>
                        <a href="products.php">Products</a>
                        <a href="about.php">About Us</a>
                        <a href="contact.php">Contact</a>
                    </div>
                </div>
                <div class="cart-dropdown">
                    <span class="cart-icon" id="cartToggle">🛒</span>
                    <div class="dropdown-menu" id="cartMenu">
                        <a href="shoppingCart.php">View Cart</a>
                        <a href="checkout.php">Checkout</a>
                    </div>
                </div>
                <div class="profile-dropdown">
                    <span class="profile-icon" id="profileToggle">👤</span>
                    <div class="dropdown-menu" id="profileMenu">
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer'): ?>
                            <a href="profile.php">Profile</a>
                            <a href="logout.php">Logout</a>
                        <?php else: ?>
                            <a href="register.php">Sign Up</a>
                            <a href="login.php">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="about-section">
            <div class="about-container">
                <h2>About Our Team</h2>
                <div class="team-member">
                    <img src="images/edwin.jpeg" alt="Edwin Photo" />
                    <h3>Edwin Teo Yuan Jing</h3>
                    <p>Front-End Developer (Visitor Role)</p>
                    <p>Student ID: 1231203190</p>
                </div>
                <div class="team-member">
                    <img src="images/zunyi.jpeg" alt="zunyi Photo" />
                    <h3>CHAN ZUN YI</h3>
                    <p>UI/UX Designer (Customer Role)</p>
                    <p>Student ID: 1231201459</p>
                </div>
                <div class="team-member">
                    <img src="images/soonkit.jpg" alt="Soon Kit Photo" />
                    <h3>WONG SOON KIT</h3>
                    <p>Admin Page Developer (Admin Role)</p>
                    <p>Student ID: 1231201251</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>© 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>

    <!-- JavaScript for dropdowns -->
  <script>
    const profileToggle = document.getElementById("profileToggle");
    const profileMenu = document.getElementById("profileMenu");
    const cartToggle = document.getElementById("cartToggle");
    const cartMenu = document.getElementById("cartMenu");
    const navToggle = document.getElementById("navToggle");
    const navMenu = document.getElementById("navMenu");

    // Toggle Profile Menu
    profileToggle.addEventListener("click", () => {
      profileMenu.style.display = (profileMenu.style.display === "block") ? "none" : "block";
      cartMenu.style.display = "none";
    });

    // Toggle Cart Menu
    cartToggle.addEventListener("click", () => {
      cartMenu.style.display = (cartMenu.style.display === "block") ? "none" : "block";
      profileMenu.style.display = "none";
    });

    navToggle.addEventListener("click", () => {
    navMenu.style.display = (navMenu.style.display === "block") ? "none" : "block";
  });

    // Hide dropdowns when clicking outside
    document.addEventListener("click", (e) => {
  if (!profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
    profileMenu.style.display = "none";
  }
  if (!cartToggle.contains(e.target) && !cartMenu.contains(e.target)) {
    cartMenu.style.display = "none";
  }
  if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
    navMenu.style.display = "none";
  }
});
  </script>
</body>
</html>