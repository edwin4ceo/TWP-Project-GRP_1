<?php
include("db_connection.php");

if (!isset($_GET['id'])) {
  die("Product ID is missing.");
}

$product_id = intval($_GET['id']);
$query = "SELECT * FROM products WHERE id = $product_id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
  die("Product not found.");
}

$product = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header>
    <div class="header-bar">
      <div class="logo-left">
        <a href="index.html">
          <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline" />
        </a>
        <h1 id="page-heading">Product Details</h1>
      </div>

      <!-- ✅ Added Header Right with Full Dropdowns -->
      <div class="header-right">
        <div class="nav-dropdown">
          <span class="nav-toggle" id="navToggle">☰</span>
          <div class="dropdown-menu" id="navMenu">
            <a href="index.html">Home</a>
            <a href="products.php">Products</a>
            <a href="about.html">About Us</a>
            <a href="contact.html">Contact</a>
          </div>
        </div>

        <div class="cart-dropdown">
          <span class="cart-icon" id="cartToggle">🛒</span>
          <div class="dropdown-menu" id="cartMenu">
            <a href="shoppingCart.html">View Cart</a>
            <a href="checkout.html">Checkout</a>
          </div>
        </div>

        <div class="profile-dropdown">
          <span class="profile-icon" id="profileToggle">👤</span>
          <div class="dropdown-menu" id="profileMenu">
            <a href="register.html">Sign Up</a>
            <a href="login.html">Login</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="product-detail-container">
    <section class="product-detail">
      <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" />
      <div class="product-info">
        <h2><?= htmlspecialchars($product['name']) ?></h2>
        <p><?= htmlspecialchars($product['description']) ?></p>
        <p><strong>RM <?= number_format($product['price'], 2) ?></strong></p>
        <ul>
          <li>Weight: 1kg (default)</li>
          <li>Freshly baked daily</li>
          <li>Fast delivery available</li>
        </ul>
        <button id="addToCartBtn">Add to Cart</button>
        <br><br>
        <a href="products.php" class="button">← Back to Products</a>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <!-- ✅ JavaScript -->
  <script>
    // Add to cart with localStorage
    document.getElementById("addToCartBtn").addEventListener("click", () => {
      const cartKey = "bakeeaseCart";
      const item = {
        name: <?= json_encode($product['name']) ?>,
        price: <?= json_encode($product['price']) ?>,
        desc: <?= json_encode($product['description']) ?>,
        image: <?= json_encode($product['image']) ?>,
        quantity: 1
      };

      let cart = JSON.parse(localStorage.getItem(cartKey)) || [];
      const existing = cart.find(p => p.name === item.name);
      if (existing) {
        existing.quantity += 1;
      } else {
        cart.push(item);
      }

      localStorage.setItem(cartKey, JSON.stringify(cart));
      window.location.href = "shoppingCart.html";
    });

    // Dropdown toggle logic
    const profileToggle = document.getElementById("profileToggle");
    const profileMenu = document.getElementById("profileMenu");
    const cartToggle = document.getElementById("cartToggle");
    const cartMenu = document.getElementById("cartMenu");
    const navToggle = document.getElementById("navToggle");
    const navMenu = document.getElementById("navMenu");

    profileToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      profileMenu.style.display = profileMenu.style.display === "block" ? "none" : "block";
      cartMenu.style.display = "none";
      navMenu.style.display = "none";
    });

    cartToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      cartMenu.style.display = cartMenu.style.display === "block" ? "none" : "block";
      profileMenu.style.display = "none";
      navMenu.style.display = "none";
    });

    navToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      navMenu.style.display = navMenu.style.display === "block" ? "none" : "block";
      profileMenu.style.display = "none";
      cartMenu.style.display = "none";
    });

    document.addEventListener("click", () => {
      profileMenu.style.display = "none";
      cartMenu.style.display = "none";
      navMenu.style.display = "none";
    });
  </script>
</body>
</html>