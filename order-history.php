<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Fetch orders for the logged-in customer
$customer_id = $_SESSION['user_id'];
$orders = [];
$query = "SELECT id, total_amount, order_date, status FROM orders WHERE customer_id = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Failed to prepare order history query: " . mysqli_error($conn));
    $errors[] = "Unable to fetch order history.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - BakeEase Bakery</title>
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
                        <a href="order-history.php">Order History</a>
                        <a href="profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="order-history-section">
            <div class="order-history-container">
                <h2>Your Order History</h2>
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!empty($orders)): ?>
                    <table class="order-history-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= date('F j, Y, g:i a', strtotime($order['order_date'])) ?></td>
                                    <td>RM <?= number_format($order['total_amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($order['status']) ?></td>
                                    <td>
                                        <a href="confirmation.php?order_id=<?= $order['id'] ?>" class="view-details">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-orders">
                        <p>No orders found. Start shopping to create your order history!</p>
                        <a href="products.php" class="shop-now">Shop Now</a>
                    </div>
                <?php endif; ?>
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

<?php
mysqli_close($conn);
?>