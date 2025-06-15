<?php
session_start();
include("db_connection.php");

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$cartItems = [];

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $query = "SELECT p.name, p.image, p.description, p.price, c.quantity 
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE c.customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
} else {
    $cartItems = isset($_COOKIE['bakeeaseCart']) ? json_decode($_COOKIE['bakeeaseCart'], true) : [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Shopping Cart - BakeEase</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="images/png" href="images/logo.png" />
  <style>
    /* Additional styles for cart page */
    .cart-container {
      max-width: 1000px;
      margin: 40px auto;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      background-color: #fff;
    }
    
    .cart-title {
      color: #5a3921;
      margin-bottom: 30px;
      text-align: center;
    }
    
    .cart-empty {
      text-align: center;
      padding: 40px 20px;
    }
    
    .cart-empty-icon {
      font-size: 48px;
      color: #ddd;
      margin-bottom: 20px;
    }
    
    .cart-empty-message {
      color: #666;
      font-size: 18px;
      margin-bottom: 30px;
    }
    
    .cart-steps {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
      position: relative;
      max-width: 1000px;
    }
    
    .cart-steps::after {
      content: '';
      position: absolute;
      top: 15px;
      left: 40px;
      right: 40px; 
      height: 2px;
      background-color: #ddd;
      z-index: 1;
    }
    
    .step {
      text-align: center;
      position: relative;
      z-index: 2;
      width: 100px;
    }
    
    .step-number {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: #ddd;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 10px;
      font-weight: bold;
    }
    
    .step.active .step-number {
      background-color: #e67e22;
    }
    
    .step.completed .step-number {
      background-color: #2ecc71;
    }
    
    .step-label {
      font-size: 14px;
      color: #5a3921;
      font-weight: bold;
    }
    
    .cart-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin-bottom: 30px;
      background-color: #fff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .cart-table thead {
      background-color: #f9f3e9;
      border-bottom: 2px solid #e67e22;
    }
    
    .cart-table th {
      padding: 15px 20px;
      text-align: left;
      color: #5a3921;
      font-weight: bold;
      font-size: 14px;
      text-transform: uppercase;
    }

    .cart-table tbody tr {
      transition: background-color 0.3s ease;
    }

    .cart-table tbody tr:hover {
      background-color: #f5e9d2;
    }
    
    .cart-table td {
      padding: 20px;
      border-bottom: 1px solid #f0e6d9;
      vertical-align: middle;
      color: #5a3921;
    }
    
    .product-info {
      display: flex;
      align-items: center;
    }
    
    .product-image {
      width: 80px;
      height: 80px;
      background-color: #f9f3e9;
      border-radius: 5px;
      margin-right: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ccc;
      font-size: 20px;
      border: 1px solid #eee;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .product-details h4 {
      margin: 0 0 8px;
      color: #5a3921;
      font-size: 18px;
      font-weight: bold;
    }

    .product-details p {
      margin: 0;
      color: #888;
      font-size: 14px;
      line-height: 1.4;
    }
    
    .quantity-control {
      display: flex;
      align-items: center;
      background-color: #f9f9f9;
      border-radius: 20px;
      padding: 2px;
      border: 1px solid #ddd;
    }
    
    .quantity-btn {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: none;
      background-color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 18px;
      font-weight: bold;
      color: #5a3921;
      user-select: none;
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    .quantity-btn:hover {
      background-color: #e67e22;
      color: white;
    }

    .quantity-btn:active {
      background-color: #d35400;
    }

    .quantity-input {
      width: 40px;
      height: 30px;
      border: none;
      border-radius: 4px;
      text-align: center;
      margin: 0 5px;
      font-size: 16px;
      background-color: transparent;
      color: #5a3921;
    }

    .quantity-input:focus {
      outline: none;
    }

    .item-price, .item-total {
      font-weight: bold;
      color: #e67e22;
      font-size: 16px;
    }
    
    .remove-item {
      color: #e74c3c;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: color 0.3s ease;
    }
    
    .remove-item:hover {
      color: #c0392b;
    }
    
    .cart-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-top: 1px solid #f0e6d9;
    }
    
    .continue-shopping {
      color: #e67e22;
      text-decoration: none;
      font-weight: bold;
      font-size: 16px;
    }
    
    .continue-shopping:hover {
      text-decoration: underline;
    }
    
    .cart-summary {
      width: 100%;
      max-width: 350px;
      margin-left: auto;
      background-color: #f9f3e9;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      font-size: 16px;
      color: #5a3921;
    }
    
    .summary-row.total {
      font-weight: bold;
      font-size: 1.2em;
      border-top: 2px solid #e67e22;
      margin-top: 10px;
      padding-top: 15px;
      color: #5a3921;
    }
    
    .promo-code {
      margin-top: 20px;
      display: flex;
      border: 1px solid #ddd;
      border-radius: 5px;
      overflow: hidden;
    }
    
    .promo-input {
      flex: 1;
      padding: 12px;
      border: none;
      font-size: 16px;
      color: #5a3921;
    }
    
    .promo-input:focus {
      outline: none;
    }

    .promo-btn {
      background-color: #5a3921;
      color: white;
      border: none;
      padding: 12px 20px;
      cursor: pointer;
      transition: background-color 0.3s;
      font-weight: bold;
    }
    
    .promo-btn:hover {
      background-color: #4a2e1c;
    }
    
    .checkout-btn {
      background-color: #e67e22;
      color: white;
      border: none;
      padding: 15px 5px;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
      width: 100%;
      margin-top: 20px;
      font-weight: bold;
      text-align: center;
      display: block;
      text-decoration: none;
    }
    
    .checkout-btn:hover {
      background-color: #d35400;
    }
    
    @media (max-width: 768px) {
      .cart-table thead {
        display: none;
      }
      
      .cart-table, .cart-table tbody, .cart-table tr, .cart-table td {
        display: block;
        width: 100%;
      }
      
      .cart-table tr {
        margin-bottom: 20px;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 10px;
        background-color: #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
      }
      
      .cart-table td {
        text-align: right;
        padding: 12px 15px;
        position: relative;
        border-bottom: 1px solid #f0e6d9;
      }
      
      .cart-table td:last-child {
        border-bottom: none;
      }
      
      .cart-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        top: 12px;
        font-weight: bold;
        color: #5a3921;
        font-size: 14px;
      }
      
      .product-info {
        justify-content: flex-start;
        margin-bottom: 10px;
      }
      
      .cart-actions {
        flex-direction: column;
        gap: 15px;
      }
      
      .cart-summary {
        max-width: 100%;
      }
    }
    <?php include('cart-style.css'); // If style is in external file ?>
  </style>
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
            <a href="checkout.php">Checkout</a>
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

      <?php if (empty($cartItems)): ?>
        <div class="cart-empty">
          <div class="cart-empty-icon">🛒</div>
          <h3 class="cart-empty-message">Your cart is empty</h3>
          <a href="products.php" class="checkout-btn">Browse Products</a>
        </div>
      <?php else: ?>
        <table class="cart-table">
          <thead>
            <tr>
              <th style="width: 50%;">Product</th>
              <th>Price</th>
              <th>Quantity</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $subtotal = 0;
              foreach ($cartItems as $item):
                $total = $item['price'] * $item['quantity'];
                $subtotal += $total;
            ?>
            <tr>
              <td>
                <div class="product-info">
                  <div class="product-image">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" width="70" height="70" />
                  </div>
                  <div class="product-details">
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <p><?= htmlspecialchars($item['description']) ?></p>
                  </div>
                </div>
              </td>
              <td class="item-price">RM <?= number_format($item['price'], 2) ?></td>
              <td><?= $item['quantity'] ?></td>
              <td class="item-total">RM <?= number_format($total, 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php
          $shipping = $subtotal > 0 ? 5.99 : 0;
          $tax = $subtotal * 0.07;
          $grandTotal = $subtotal + $shipping + $tax;
        ?>

        <div class="cart-actions">
          <a href="products.php" class="continue-shopping">← Continue Shopping</a>
          <div class="cart-summary">
            <h3>Summary</h3>
            <p class="summary-row"><strong>Subtotal:</strong> <span id="summary-subtotal">RM <?= number_format($subtotal, 2) ?></span></p>
            <p class="summary-row"><strong>Shipping:</strong> <span id="summary-shipping">RM <?= number_format($shipping, 2) ?></span></p>
            <p class="summary-row"><strong>Tax (7%):</strong> <span id="summary-tax">RM <?= number_format($tax, 2) ?></span></p>
            <p class="summary-row total"><strong>Total:</strong> <span id="summary-total">RM <?= number_format($grandTotal, 2) ?></span></p>
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