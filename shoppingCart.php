<?php
session_start();
include("db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
  echo "<p style='text-align:center;padding:50px;'>You must <a href='login.html'>log in</a> to view your cart.</p>";
  exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch cart items for this customer
$query = "
  SELECT c.id as cart_id, c.quantity, p.name, p.description, p.price, p.image, p.id as product_id
  FROM cart c
  JOIN products p ON c.product_id = p.id
  WHERE c.customer_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
while ($row = $result->fetch_assoc()) {
  $cart_items[] = $row;
}

function format_price($price) {
  return 'RM ' . number_format($price, 2);
}

// Process quantity update or remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = max(1, intval($_POST['quantity']));
    $conn->query("UPDATE cart SET quantity = $quantity WHERE id = $cart_id AND customer_id = $customer_id");
    header("Location: shoppingCart.php");
    exit();
  }

  if (isset($_POST['remove_item'])) {
    $cart_id = $_POST['cart_id'];
    $conn->query("DELETE FROM cart WHERE id = $cart_id AND customer_id = $customer_id");
    header("Location: shoppingCart.php");
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Shopping Cart - BakeEase</title>
  <link rel="icon" href="images/logo.png" type="image/png" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    <?php include "cart-style.css"; ?>
  </style>
</head>
<body>
<header>
  <div class="header-bar">
    <div class="logo-left">
      <a href="index.php">
        <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline" />
      </a>
      <h1 id="page-heading">Shopping Cart</h1>
    </div>
    <div class="header-right">
      <div class="nav-dropdown">
        <span class="nav-toggle" id="navToggle">☰</span>
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
          <a href="register.php">Sign Up</a>
          <a href="login.php">Login</a>
        </div>
      </div>
    </div>
  </div>
</header>

<main>
  <div class="cart-container">
    <h2 class="cart-title">Your Shopping Cart</h2>

    <?php if (count($cart_items) === 0): ?>
      <div class="cart-empty">
        <div class="cart-empty-icon">🛒</div>
        <h3 class="cart-empty-message">Your cart is empty</h3>
        <a href="products.php" class="checkout-btn">Browse Products</a>
      </div>
    <?php else: ?>
      <table class="cart-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $subtotal = 0;
          foreach ($cart_items as $item):
            $total = $item['price'] * $item['quantity'];
            $subtotal += $total;
          ?>
          <tr>
            <td>
              <div class="product-info">
                <img class="product-image" src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="product-details">
                  <h4><?= htmlspecialchars($item['name']) ?></h4>
                  <p><?= htmlspecialchars($item['description']) ?></p>
                </div>
              </div>
            </td>
            <td class="item-price"><?= format_price($item['price']) ?></td>
            <td>
              <form method="POST" style="display:flex; align-items:center;">
                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="99" class="quantity-input" />
                <button type="submit" name="update_quantity" class="quantity-btn">⟳</button>
              </form>
            </td>
            <td class="item-total"><?= format_price($total) ?></td>
            <td>
              <form method="POST">
                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                <button type="submit" name="remove_item" class="remove-item">✕</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php
        $shipping = $subtotal > 0 ? 5.99 : 0;
        $tax = $subtotal * 0.07;
        $total = $subtotal + $shipping + $tax;
      ?>

      <div class="cart-actions">
        <a href="products.php" class="continue-shopping">← Continue Shopping</a>

        <div class="cart-summary">
          <h3>Summary</h3>
          <p class="summary-row"><strong>Subtotal:</strong> <span><?= format_price($subtotal) ?></span></p>
          <p class="summary-row"><strong>Shipping:</strong> <span><?= format_price($shipping) ?></span></p>
          <p class="summary-row"><strong>Tax (7%):</strong> <span><?= format_price($tax) ?></span></p>
          <p class="summary-row total"><strong>Total:</strong> <span><?= format_price($total) ?></span></p>
          <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<footer>
  <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
</footer>
</body>
</html>