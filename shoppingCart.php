<?php
session_start();
include("db_connection.php");

$isLoggedIn = isset($_SESSION['customer_id']);
$customerId = $isLoggedIn ? $_SESSION['customer_id'] : null;
$cartItems = [];

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT p.name, p.description, p.price, p.image, c.quantity 
                            FROM cart c 
                            JOIN products p ON c.product_id = p.id 
                            WHERE c.customer_id = ?");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>Shopping Cart - BakeEase</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header>
    <div class="header-bar">
      <div class="logo-left">
        <a href="index.html">
        <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline" /></a>
        <h1 id="page-heading">Shopping Cart</h1>
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
            <a href="shoppingCart.php">View Cart</a>
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
    <div class="cart-container">
      <h2 class="cart-title">Your Shopping Cart</h2>
      <div id="empty-cart" style="display: none;">
        <p>Your cart is empty.</p>
        <a href="products.php" class="checkout-btn">Browse Products</a>
      </div>

      <table class="cart-table" id="cart-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
            <th>Remove</th>
          </tr>
        </thead>
        <tbody id="cart-body"></tbody>
      </table>

      <div class="cart-actions" id="cart-actions">
        <a href="products.php" class="continue-shopping">← Continue Shopping</a>
        <div class="cart-summary">
          <h3>Summary</h3>
          <p class="summary-row"><strong>Subtotal:</strong> <span id="summary-subtotal">RM 0.00</span></p>
          <p class="summary-row"><strong>Shipping:</strong> <span id="summary-shipping">RM 0.00</span></p>
          <p class="summary-row"><strong>Tax (7%):</strong> <span id="summary-tax">RM 0.00</span></p>
          <p class="summary-row total"><strong>Total:</strong> <span id="summary-total">RM 0.00</span></p>
          <a href="checkout.html" class="checkout-btn">Proceed to Checkout</a>
        </div>
      </div>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <script>
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    const cartData = <?php echo json_encode($cartItems); ?>;
    const cartKey = "bakeeaseCart";

    function loadCart() {
      const cart = isLoggedIn ? cartData : JSON.parse(localStorage.getItem(cartKey)) || [];
      const tbody = document.getElementById("cart-body");
      const table = document.getElementById("cart-table");
      const summary = document.getElementById("cart-actions");
      const emptyMsg = document.getElementById("empty-cart");

      if (cart.length === 0) {
        table.style.display = "none";
        summary.style.display = "none";
        emptyMsg.style.display = "block";
        return;
      }

      tbody.innerHTML = "";
      let subtotal = 0;

      cart.forEach((item, index) => {
        const row = document.createElement("tr");
        const total = item.price * item.quantity;
        subtotal += total;

        row.innerHTML = `
          <td>
            <div class="product-info">
              <img src="${item.image}" alt="${item.name}" class="product-image" />
              <div class="product-details">
                <h4>${item.name}</h4>
                <p>${item.description}</p>
              </div>
            </div>
          </td>
          <td>RM ${item.price.toFixed(2)}</td>
          <td>${item.quantity}</td>
          <td>RM ${total.toFixed(2)}</td>
          <td><button class="remove-item" onclick="removeItem(${index})">✕</button></td>
        `;
        tbody.appendChild(row);
      });

      const shipping = subtotal > 0 ? 5.99 : 0;
      const tax = subtotal * 0.07;
      const grandTotal = subtotal + shipping + tax;

      document.getElementById("summary-subtotal").textContent = `RM ${subtotal.toFixed(2)}`;
      document.getElementById("summary-shipping").textContent = `RM ${shipping.toFixed(2)}`;
      document.getElementById("summary-tax").textContent = `RM ${tax.toFixed(2)}`;
      document.getElementById("summary-total").textContent = `RM ${grandTotal.toFixed(2)}`;
    }

    function removeItem(index) {
      if (!isLoggedIn) {
        const cart = JSON.parse(localStorage.getItem(cartKey)) || [];
        cart.splice(index, 1);
        localStorage.setItem(cartKey, JSON.stringify(cart));
        loadCart();
      } else {
        alert("Removing items for logged-in users is not yet implemented.");
      }
    }

    document.addEventListener("DOMContentLoaded", loadCart);
  </script>
</body>
</html>