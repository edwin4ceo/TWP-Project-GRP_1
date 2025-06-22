<?php
session_start();
require_once 'db_connection.php';

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: shoppingCart.php");
    exit();
}

$order_id = (int)$_GET['order_id'];

// Fetch order details
$query = "SELECT o.id, o.total, o.created_at, c.name, c.email FROM orders o JOIN customers c ON o.user_id = c.id WHERE o.id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: shoppingCart.php");
    exit();
}

$order = mysqli_fetch_assoc($result);

// Fetch order items
$query = "SELECT oi.quantity, oi.price, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$order_items = [];
$subtotal = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $row['total'] = $row['quantity'] * $row['price'];
    $subtotal += $row['total'];
    $order_items[] = $row;
}

// Calculate totals
$shipping = 0;
$tax_rate = 0.07;
$tax = $subtotal * $tax_rate;
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&display=swap" rel="stylesheet">
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
        <section class="confirmation-section">
            <div class="confirmation-container">
                <div class="progress-bar">
                    <div class="progress-step">
                        <span class="step-number">1</span>
                        <span class="step-label">Cart</span>
                    </div>
                    <div class="progress-step">
                        <span class="step-number">2</span>
                        <span class="step-label">Checkout</span>
                    </div>
                    <div class="progress-step active">
                        <span class="step-number">3</span>
                        <span class="step-label">Confirmation</span>
                    </div>
                </div>
                <h2>Thank You for Your Order!</h2>
                <p>Your order has been successfully placed. You'll receive a confirmation email soon.</p>
                <div class="order-details">
                    <h3>Order Details</h3>
                    <p><strong>Order ID:</strong> #<?= sprintf("%06d", $order['id']) ?></p>
                    <p><strong>Customer:</strong> <?= htmlspecialchars($order['name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                    <p><strong>Order Date:</strong> <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></p>
                    <div class="order-items">
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <div class="item-details">
                                    <span><?= htmlspecialchars($item['name']) ?></span>
                                    <span>Qty: <?= $item['quantity'] ?></span>
                                    <span>RM <?= number_format($item['total'], 2) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-totals">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>RM <?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>RM <?= number_format($shipping, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (7%):</span>
                            <span>RM <?= number_format($tax, 2) ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>RM <?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                </div>
                <div class="confirmation-actions">
                    <a href="products.php" class="continue-shopping">Continue Shopping</a>
                    <a href="profile.php" class="view-history">View Order History</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>© 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const profileToggle = document.getElementById("profileToggle");
        const profileMenu = document.getElementById("profileMenu");
        const cartToggle = document.getElementById("cartToggle");
        const cartMenu = document.getElementById("cartMenu");
        const navToggle = document.getElementById("navToggle");
        const navMenu = document.getElementById("navMenu");

        if (!profileToggle || !profileMenu || !cartToggle || !cartMenu || !navToggle || !navMenu) {
            console.error("One or more dropdown elements not found.");
            return;
        }

        profileToggle.addEventListener("click", (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle("show");
            cartMenu.classList.remove("show");
            navMenu.classList.remove("show");
        });

        cartToggle.addEventListener("click", (e) => {
            e.stopPropagation();
            cartMenu.classList.toggle("show");
            profileMenu.classList.remove("show");
            navMenu.classList.remove("show");
        });

        navToggle.addEventListener("click", (e) => {
            e.stopPropagation();
            navMenu.classList.toggle("show");
            profileMenu.classList.remove("show");
            cartMenu.classList.remove("show");
        });

        document.addEventListener("click", (e) => {
            if (!profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.remove("show");
            }
            if (!cartToggle.contains(e.target) && !cartMenu.contains(e.target)) {
                cartMenu.classList.remove("show");
            }
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove("show");
            }
        });

        profileMenu.addEventListener("click", (e) => e.stopPropagation());
        cartMenu.addEventListener("click", (e) => e.stopPropagation());
        navMenu.addEventListener("click", (e) => e.stopPropagation());
    });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>