<?php
include("db_connection.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Our Cakes</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header>
    <div class="header-bar">
      <div class="logo-left">
        <a href="index.html">
        <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline" /></a>
        <h1 id="page-heading">Our Delicious Cake</h1>
      </div>

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

  <main>
  <?php
  $query = "SELECT * FROM products ORDER BY category, name";
  $result = mysqli_query($conn, $query);

  $currentCategory = '';

  while ($row = mysqli_fetch_assoc($result)) {
    // Start a new category section
    if ($row['category'] !== $currentCategory) {
      if ($currentCategory !== '') {
        echo '</section>'; // Close previous section
      }
      $currentCategory = $row['category'];
      echo '<h2 style="padding: 20px; color: #5a3921;">' . htmlspecialchars($currentCategory) . '</h2>';
      echo '<section class="product-grid">';
    }

    echo '<article class="product-card" data-name="' . htmlspecialchars($row['name']) . '" data-desc="' . htmlspecialchars($row['description']) . '">';
    echo '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '" />';
    echo '<h4>' . htmlspecialchars($row['name']) . '</h4>';
    echo '<p>' . htmlspecialchars($row['description']) . '</p>';
    echo '<p><strong>RM ' . htmlspecialchars($row['price']) . '</strong></p>';
    echo '<a href="product-detail.php?id=' . $row['id'] . '" class="button">View Details</a>';
    echo '<button class="button add-to-cart" data-name="' . htmlspecialchars($row['name']) . '" data-price="' . htmlspecialchars($row['price']) . '" data-desc="' . htmlspecialchars($row['description']) . '">Add to Cart</button>';
    echo '</article>';
  }

  // Close the last section
  if ($currentCategory !== '') {
    echo '</section>';
  }
  ?>
</main>

  <footer>
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <script>
    const cartKey = "bakeeaseCart";

    document.querySelectorAll(".add-to-cart").forEach(button => {
      button.addEventListener("click", () => {
        const name = button.getAttribute("data-name");
        const price = parseFloat(button.getAttribute("data-price"));
        const desc = button.getAttribute("data-desc");
        const image = button.closest(".product-card").querySelector("img").getAttribute("src");

        let cart = JSON.parse(localStorage.getItem(cartKey)) || [];

        const existing = cart.find(item => item.name === name);
        if (existing) {
          existing.quantity += 1;
        } else {
          cart.push({ name, price, desc, image, quantity: 1 });
        }

        localStorage.setItem(cartKey, JSON.stringify(cart));
        window.location.href = "shoppingCart.html";
      });
    });
  </script>
</body>
</html>