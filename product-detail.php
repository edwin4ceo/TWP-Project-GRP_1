<?php
include("db_connection.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Product ID is missing.");
}

$productId = intval($_GET['id']);
$query = "SELECT * FROM products WHERE id = $productId";
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
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title><?php echo htmlspecialchars($product['name']); ?> - BakeEase</title>
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
  <section class="product-detail">
    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
    <div class="product-info">
      <h2><?php echo htmlspecialchars($product['name']); ?></h2>
      <p><?php echo htmlspecialchars($product['description']); ?></p>
      <p><strong>Price: RM <?php echo number_format($product['price'], 2); ?></strong></p>
      <p>Category: <?php echo htmlspecialchars($product['category']); ?></p>
      <button class="button add-to-cart"
              data-name="<?php echo htmlspecialchars($product['name']); ?>"
              data-price="<?php echo htmlspecialchars($product['price']); ?>"
              data-desc="<?php echo htmlspecialchars($product['description']); ?>">
              Add to Cart
      </button>
      <a href="products.php" class="button">Back to Products</a>
    </div>
  </section>
</main>

<footer>
  <p>© 2025 BakeEase Bakery. All rights reserved.</p>
</footer>

<script>
// JavaScript to handle Add to Cart
const cartKey = "bakeeaseCart";
document.querySelector(".add-to-cart").addEventListener("click", function() {
  const name = this.getAttribute("data-name");
  const price = parseFloat(this.getAttribute("data-price"));
  const desc = this.getAttribute("data-desc");
  const image = document.querySelector("img").getAttribute("src");

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
</script>

</body>
</html>