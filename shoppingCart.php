<?php
// Prevent caching to ensure fresh cart data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
include("db_connection.php");

// Debugging: Log session and cookie data (remove after testing)
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));
error_log("bakeeaseCart Cookie: " . (isset($_COOKIE['bakeeaseCart']) ? $_COOKIE['bakeeaseCart'] : "Not set"));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$cartItems = [];
$dbError = '';

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    try {
        $query = "SELECT p.id, p.name, p.image, p.description, p.price, c.quantity 
                  FROM cart c
                  JOIN products p ON c.product_id = p.id
                  WHERE c.customer_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            $dbError = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($stmt->error) {
                $dbError = "Query error: " . $stmt->error;
            } else {
                while ($row = $result->fetch_assoc()) {
                    $cartItems[] = $row;
                }
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $dbError = "Database error: " . $e->getMessage();
    }
} else {
    $cartItems = isset($_COOKIE['bakeeaseCart']) ? json_decode($_COOKIE['bakeeaseCart'], true) : [];
    if (json_last_error() !== JSON_ERROR_NONE) {
        $dbError = "Invalid cookie data: " . json_last_error_msg();
        $cartItems = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - BakeEase</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        /* [Original CSS styles retained, no changes needed] */
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
            border-radius: 5px;
            margin-right: 15px;
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
        }
        .item-price, .item-total {
            font-weight: bold;
            color: #e67e22;
            font-size: 16px;
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
        }
        .checkout-btn {
            background-color: #e67e22;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
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
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 20px;
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
            }
            .cart-table td {
                text-align: right;
                padding: 12px 15px;
                position: relative;
                border-bottom: 1px solid #f0e6d9;
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
            .cart-actions {
                flex-direction: column;
                gap: 15px;
            }
            .cart-summary {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-bar">
            <div class="logo-left">
                <a href="index.html"><img src="images/logo.png" alt="BakeEase Logo" class="logo-inline"></a>
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

            <?php if ($dbError): ?>
                <div class="error-message"><?php echo htmlspecialchars($dbError); ?></div>
            <?php endif; ?>

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
                            <td data-label="Product">
                                <div class="product-info">
                                    <div class="product-image">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" width="70" height="70">
                                    </div>
                                    <div class="product-details">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Price" class="item-price">RM <?php echo number_format($item['price'], 2); ?></td>
                            <td data-label="Quantity"><?php echo $item['quantity']; ?></td>
                            <td data-label="Total" class="item-total">RM <?php echo number_format($total, 2); ?></td>
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
                        <p class="summary-row"><strong>Subtotal:</strong> <span id="summary-subtotal">RM <?php echo number_format($subtotal, 2); ?></span></p>
                        <p class="summary-row"><strong>Shipping:</strong> <span id="summary-shipping">RM <?php echo number_format($shipping, 2); ?></span></p>
                        <p class="summary-row"><strong>Tax (7%):</strong> <span id="summary-tax">RM <?php echo number_format($tax, 2); ?></span></p>
                        <p class="summary-row total"><strong>Total:</strong> <span id="summary-total">RM <?php echo number_format($grandTotal, 2); ?></span></p>
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>© 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>
</body>
</html>