<?php
include("db_connection.php");

if (!isset($_GET['id'])) {
  die("Product ID is missing.");
}

$product_id = intval($_GET['id']);
$query = "SELECT * FROM products WHERE id = $product_id";
$result = mysqli_query($conn, $query);
$product = mysqli_fetch_assoc($result);

if (!$product) {
  die("Product not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($product['name']) ?> - BakeEase</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header>
    <!-- Add your site’s header HTML here -->
  </header>

  <main>
    <div class="product-detail">
      <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" />
      <div class="product-info">
        <h2><?= htmlspecialchars($product['name']) ?></h2>
        <p><?= htmlspecialchars($product['description']) ?></p>
        <p><strong>RM <?= number_format($product['price'], 2) ?></strong></p>
        <button class="button add-to-cart"
                data-name="<?= htmlspecialchars($product['name']) ?>"
                data-price="<?= htmlspecialchars($product['price']) ?>"
                data-desc="<?= htmlspecialchars($product['description']) ?>"
                data-image="<?= htmlspecialchars($product['image']) ?>">Add to Cart</button>
      </div>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 BakeEase Bakery</p>
  </footer>

  <script>
    const cartKey = "bakeeaseCart";
    document.querySelector(".add-to-cart").addEventListener("click", function () {
      const button = this;
      const name = button.getAttribute("data-name");
      const price = parseFloat(button.getAttribute("data-price"));
      const desc = button.getAttribute("data-desc");
      const image = button.getAttribute("data-image");

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