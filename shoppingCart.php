<?php
session_start();
require_once 'db_connection.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize promo discount
if (!isset($_SESSION['promo_discount'])) {
    $_SESSION['promo_discount'] = 0;
    $_SESSION['promo_code_used'] = '';
}

// Promo codes database (you can move this to database later)
$promo_codes = [
    'SAVE10' => ['discount' => 10, 'type' => 'percentage', 'description' => '10% off'],
    'SAVE20' => ['discount' => 20, 'type' => 'percentage', 'description' => '20% off'],
    'NEWBIE15' => ['discount' => 15, 'type' => 'percentage', 'description' => '15% off for new customers'],
    'FLAT5' => ['discount' => 5, 'type' => 'fixed', 'description' => 'RM 5 off'],
    'FLAT10' => ['discount' => 10, 'type' => 'fixed', 'description' => 'RM 10 off'],
    'WELCOME' => ['discount' => 25, 'type' => 'percentage', 'description' => '25% welcome discount'],
    'BAKERY30' => ['discount' => 30, 'type' => 'percentage', 'description' => '30% bakery special'],
    'SWEET15' => ['discount' => 15, 'type' => 'fixed', 'description' => 'RM 15 sweet deal']
];

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
        $entered_code = strtoupper(trim($_POST['promo_code']));
        
        if (empty($entered_code)) {
            $_SESSION['promo_message'] = "Please enter a promo code.";
            $_SESSION['promo_message_type'] = "error";
        } elseif (array_key_exists($entered_code, $promo_codes)) {
            // Valid promo code
            $_SESSION['promo_discount'] = $promo_codes[$entered_code]['discount'];
            $_SESSION['promo_type'] = $promo_codes[$entered_code]['type'];
            $_SESSION['promo_code_used'] = $entered_code;
            $_SESSION['promo_message'] = "🎉 Promo code '{$entered_code}' applied successfully! " . $promo_codes[$entered_code]['description'];
            $_SESSION['promo_message_type'] = "success";
        } else {
            // Invalid promo code
            $_SESSION['promo_message'] = "❌ Invalid promo code. Please try again.";
            $_SESSION['promo_message_type'] = "error";
        }
    } elseif (isset($_POST['remove_promo'])) {
        // Remove promo code
        $_SESSION['promo_discount'] = 0;
        $_SESSION['promo_type'] = '';
        $_SESSION['promo_code_used'] = '';
        $_SESSION['promo_message'] = "Promo code removed successfully.";
        $_SESSION['promo_message_type'] = "success";
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

// Calculate discount
$discount_amount = 0;
if ($_SESSION['promo_discount'] > 0) {
    if ($_SESSION['promo_type'] == 'percentage') {
        $discount_amount = $subtotal * ($_SESSION['promo_discount'] / 100);
    } else { // fixed amount
        $discount_amount = min($_SESSION['promo_discount'], $subtotal); // Can't discount more than subtotal
    }
}

// Calculate totals
$shipping = 0; // Free shipping
$tax_rate = 0.07;
$discounted_subtotal = $subtotal - $discount_amount;
$tax = $discounted_subtotal * $tax_rate;
$total = $discounted_subtotal + $shipping + $tax;
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
    <style>
        /* Enhanced Continue Shopping Button Styles */
        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .continue-shopping-prominent {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #fd7e14, #e8690b);
            border: none;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-right: 15px;
            box-shadow: 0 3px 10px rgba(253, 126, 20, 0.3);
            position: relative;
            overflow: hidden;
        }

        .continue-shopping-prominent::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .continue-shopping-prominent:hover::before {
            left: 100%;
        }

        .continue-shopping-prominent:hover {
            background: linear-gradient(135deg, #e8690b, #d45807);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(253, 126, 20, 0.4);
            color: white;
            text-decoration: none;
        }

        .continue-shopping-prominent:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(253, 126, 20, 0.3);
        }

        .shopping-icon {
            margin-right: 8px;
            font-size: 18px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-3px);
            }
            60% {
                transform: translateY(-2px);
            }
        }

        .update-button {
            padding: 12px 24px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .update-button:hover {
            background-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(40, 167, 69, 0.3);
        }

        /* Promo code styles */
        .promo-form {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            align-items: stretch;
        }

        .promo-form input {
            flex: 1;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .promo-form input:focus {
            outline: none;
            border-color: #fd7e14;
            box-shadow: 0 0 0 3px rgba(253, 126, 20, 0.1);
        }

        .promo-form button {
            padding: 12px 20px;
            background: linear-gradient(135deg, #17a2b8, #117a8b);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .promo-form button:hover {
            background: linear-gradient(135deg, #138496, #0f6674);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(23, 162, 184, 0.3);
        }

        .promo-applied {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .promo-applied-text {
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .promo-applied-text::before {
            content: '🎉';
            margin-right: 8px;
            font-size: 18px;
        }

        .remove-promo {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .remove-promo:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }

        .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        }

        .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        }

        .discount-row {
            color: #28a745;
            font-weight: 700;
            font-size: 16px;
        }

        .discount-row span:first-child::before {
            content: '💰 ';
            margin-right: 5px;
        }

        .promo-hints {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            margin: 10px 0;
        }

        .promo-hints strong {
            color: #fd7e14;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cart-actions {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .continue-shopping-prominent {
                width: 100%;
                order: -1;
                margin-right: 0;
                margin-bottom: 10px;
            }

            .update-button {
                width: 100%;
            }

            .promo-form {
                flex-direction: column;
            }

            .promo-applied {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        /* Enhanced empty cart styling */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background-color: #f8f9fa;
            border-radius: 12px;
            margin: 30px 0;
        }

        .browse-button {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #fd7e14, #e8690b);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(253, 126, 20, 0.3);
        }

        .browse-button:hover {
            background: linear-gradient(135deg, #e8690b, #d45807);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(253, 126, 20, 0.4);
            color: white;
            text-decoration: none;
        }

        /* Enhanced checkout button */
        .checkout-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 18px;
            text-align: center;
            display: block;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }

        .checkout-button:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
            text-decoration: none;
        }

        .checkout-button::before {
            content: '🛒 ';
            margin-right: 8px;
        }
    </style>
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
                        <a href="products.php" class="browse-button">
                            <span class="shopping-icon">🛍️</span>Browse Products
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <?php if (isset($_SESSION['promo_message'])): ?>
                            <div class="<?php echo $_SESSION['promo_message_type']; ?>">
                                <?php echo htmlspecialchars($_SESSION['promo_message']); ?>
                            </div>
                            <?php unset($_SESSION['promo_message'], $_SESSION['promo_message_type']); ?>
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
                            <a href="products.php" class="continue-shopping-prominent">
                                <span class="shopping-icon">🛍️</span>Continue Shopping
                            </a>
                            <button type="submit" name="update" class="update-button">Update Cart</button>
                        </div>
                    </form>

                    <div class="cart-summary">
                        <h3>Summary</h3>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>RM <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <?php if ($discount_amount > 0): ?>
                            <div class="summary-row discount-row">
                                <span>Discount (<?php echo $_SESSION['promo_code_used']; ?>):</span>
                                <span>-RM <?php echo number_format($discount_amount, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
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
                        
                        <?php if (!empty($_SESSION['promo_code_used'])): ?>
                            <div class="promo-applied">
                                <div class="promo-applied-text">
                                    Promo code "<?php echo $_SESSION['promo_code_used']; ?>" applied
                                </div>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="remove_promo" class="remove-promo">Remove</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="promo-form">
                                <input type="text" name="promo_code" placeholder="Enter promo code" maxlength="20">
                                <button type="submit">Apply Code</button>
                            </form>
                            <div class="promo-hints">
                                <strong>Try these codes:</strong> SAVE10, SAVE20, NEWBIE15, FLAT5, FLAT10, WELCOME, BAKERY30, SWEET15
                            </div>
                        <?php endif; ?>
                        
                        <a href="checkout.php" class="checkout-button">Proceed to Checkout</a>
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

        // Add hover effect to continue shopping button
        const continueShoppingBtn = document.querySelector('.continue-shopping-prominent');
        if (continueShoppingBtn) {
            continueShoppingBtn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            
            continueShoppingBtn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        }

        // Auto-hide success/error messages after 5 seconds
        const messages = document.querySelectorAll('.success, .error');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.opacity = '0';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 300);
            }, 5000);
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>