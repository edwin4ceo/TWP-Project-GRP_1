<?php
session_start();
require_once 'db_connection.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart updates (remove or update quantity)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['remove'])) {
        $product_id = (int)$_POST['remove'];
        unset($_SESSION['cart'][$product_id]);
    } elseif (isset($_POST['update'])) {
        foreach ($_POST['quantity'] as $product_id => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id] = $qty;
            }
        }
    } elseif (isset($_POST['promo_code'])) {
        // Placeholder for promo code (no actual logic)
        $_SESSION['promo_message'] = "Promo code applied (feature not implemented).";
    }
}

// Fetch cart items from database
$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $query = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, str_repeat('i', count($product_ids)), ...$product_ids);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $row['total'] = $row['price'] * $row['quantity'];
        $subtotal += $row['total'];
        $cart_items[] = $row;
    }
}

// Calculate totals
$shipping = 0; // Free shipping
$tax_rate = 0.07;
$tax = $subtotal * $tax_rate;
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="cart-styles.css">
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
        <section class="cart-section">
            <div class="cart-container">
                <h2>Your Shopping Cart</h2>
                <div class="progress-bar">
                    <div class="progress-step active">
                        <span class="step-number">1</span>
                        <span class="step-label">Cart</span>
                    </div>
                    <div class="progress-step">
                        <span class="step-number">2</span>
                        <span class="step-label">Checkout</span>
                    </div>
                    <div class="progress-step">
                        <span class="step-number">3</span>
                        <span class="step-label">Confirmation</span>
                    </div>
                </div>

                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <span class="cart-icon-large">🛒</span>
                        <p>Your cart is empty</p>
                        <p>Add some delicious items to your cart!</p>
                        <a href="products.php" class="browse-button">Browse Products</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <?php if (isset($_SESSION['promo_message'])): ?>
                            <div class="success"><?php echo htmlspecialchars($_SESSION['promo_message']); unset($_SESSION['promo_message']); ?></div>
                        <?php endif; ?>
                        <div class="cart-table">
                            <div class="cart-header">
                                <span>Product</span>
                                <span>Price</span>
                                <span>Quantity</span>
                                <span>Total</span>
                                <span>Actions</span>
                            </div>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-row">
                                    <div class="cart-product">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                    <span>RM <?php echo number_format($item['price'], 2); ?></span>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                                    <span>RM <?php echo number_format($item['total'], 2); ?></span>
                                    <button type="submit" name="remove" value="<?php echo $item['id']; ?>" class="remove-button">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="cart-actions">
                            <a href="products.php" class="continue-shopping">← Continue Shopping</a>
                            <button type="submit" name="update" class="update-button">Update Cart</button>
                        </div>
                    </form>

                    <div class="cart-summary">
                        <h3>Summary</h3>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>RM <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>RM <?php echo number_format($shipping, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (7%):</span>
                            <span>RM <?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>RM <?php echo number_format($total, 2); ?></span>
                        </div>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="promo-form">
                            <input type="text" name="promo_code" placeholder="Enter promo code">
                            <button type="submit">Apply</button>
                        </form>
                        <a href="checkout.php" class="checkout-button">Proceed to Checkout</a>
                    </div>
                <?php endif; ?>
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