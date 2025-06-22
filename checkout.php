<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch cart items
$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $query = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, str_repeat('i', count($product_ids)), ...$product_ids);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $row['quantity'] = $_SESSION['cart'][$row['id']];
            $row['total'] = $row['price'] * $row['quantity'];
            $subtotal += $row['total'];
            $cart_items[] = $row;
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Failed to prepare cart query: " . mysqli_error($conn));
        $errors[] = "Unable to load cart items.";
    }
}

// Calculate totals
$shipping = 0; // Free shipping
$tax_rate = 0.07;
$tax = $subtotal * $tax_rate;
$total = $subtotal + $shipping + $tax;

// Fetch user details
$customer_id = $_SESSION['user_id'];
$query = "SELECT name, email, phone FROM customers WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result) ?: ['name' => '', 'email' => '', 'phone' => ''];
    mysqli_stmt_close($stmt);
} else {
    error_log("Failed to prepare user query: " . mysqli_error($conn));
    $errors[] = "Unable to load user details.";
    $user = ['name' => '', 'email' => '', 'phone' => ''];
}

// Split name for default values
$name_parts = explode(' ', $user['name'], 2);
$default_first_name = $name_parts[0];
$default_last_name = isset($name_parts[1]) ? $name_parts[1] : '';

// Initialize form data and errors
$errors = [];
$form_data = [
    'first_name' => isset($_POST['first_name']) ? trim($_POST['first_name']) : $default_first_name,
    'last_name' => isset($_POST['last_name']) ? trim($_POST['last_name']) : $default_last_name,
    'email' => isset($_POST['email']) ? trim($_POST['email']) : $user['email'],
    'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : $user['phone'],
    'street_address' => isset($_POST['street_address']) ? trim($_POST['street_address']) : '',
    'apartment' => isset($_POST['apartment']) ? trim($_POST['apartment']) : '',
    'city' => isset($_POST['city']) ? trim($_POST['city']) : '',
    'state' => isset($_POST['state']) ? trim($_POST['state']) : '',
    'zip_code' => isset($_POST['zip_code']) ? trim($_POST['zip_code']) : '',
    'country' => isset($_POST['country']) ? trim($_POST['country']) : 'Malaysia',
    'delivery_instructions' => isset($_POST['delivery_instructions']) ? trim($_POST['delivery_instructions']) : '',
    'card_name' => isset($_POST['card_name']) ? trim($_POST['card_name']) : '',
    'card_number' => isset($_POST['card_number']) ? trim($_POST['card_number']) : '',
    'expiration_date' => isset($_POST['expiration_date']) ? trim($_POST['expiration_date']) : '',
    'cvv' => isset($_POST['cvv']) ? trim($_POST['cvv']) : '',
    'billing_zip' => isset($_POST['billing_zip']) ? trim($_POST['billing_zip']) : ''
];

// Construct delivery address
$delivery_address = trim(
    ($form_data['street_address'] ? $form_data['street_address'] : '') . ' ' .
    ($form_data['apartment'] ? $form_data['apartment'] . ', ' : '') .
    ($form_data['city'] ? $form_data['city'] . ', ' : '') .
    ($form_data['state'] ? $form_data['state'] . ', ' : '') .
    ($form_data['zip_code'] ? $form_data['zip_code'] . ', ' : '') .
    ($form_data['country'] ? $form_data['country'] : '')
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($cart_items)) {
    // Validate inputs
    if (empty($form_data['first_name']) || empty($form_data['last_name'])) {
        $errors[] = "First and Last Name are required.";
    }
    if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    if (!preg_match("/^[0-9]{10,15}$/", $form_data['phone'])) {
        $errors[] = "Invalid phone number (10-15 digits).";
    }
    if (empty($form_data['street_address']) || empty($form_data['city']) || empty($form_data['state']) || empty($form_data['zip_code']) || empty($form_data['country'])) {
        $errors[] = "All shipping address fields are required except apartment and delivery instructions.";
    }
    if (!preg_match("/^[0-9]{5,10}$/", $form_data['zip_code'])) {
        $errors[] = "Invalid ZIP/Postal code.";
    }
    if (empty($form_data['card_name']) || empty($form_data['card_number']) || empty($form_data['expiration_date']) || empty($form_data['cvv']) || empty($form_data['billing_zip'])) {
        $errors[] = "All payment fields are required.";
    }
    if (!preg_match("/^[0-9]{16}$/", str_replace(' ', '', $form_data['card_number']))) {
        $errors[] = "Invalid card number (16 digits).";
    }
    if (!preg_match("/^(0[1-9]|1[0-2])\/[0-9]{2}$/", $form_data['expiration_date'])) {
        $errors[] = "Invalid expiration date (MM/YY).";
    }
    if (!preg_match("/^[0-9]{3,4}$/", $form_data['cvv'])) {
        $errors[] = "Invalid CVV (3-4 digits).";
    }

    // If no errors, process order
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        try {
            // Insert order
            $customer_name = $form_data['first_name'] . ' ' . $form_data['last_name'];
            $query = "INSERT INTO orders (customer_id, customer_name, total_amount, status, delivery_address, customer_email, customer_phone) VALUES (?, ?, ?, 'pending', ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                throw new Exception("Failed to prepare order insert query: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "issdss", $customer_id, $customer_name, $total, $delivery_address, $form_data['email'], $form_data['phone']);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute order insert query: " . mysqli_stmt_error($stmt));
            }
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Insert order items
            $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                throw new Exception("Failed to prepare order items insert query: " . mysqli_error($conn));
            }
            foreach ($cart_items as $item) {
                mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to execute order items insert query: " . mysqli_stmt_error($stmt));
                }
            }
            mysqli_stmt_close($stmt);

            // Commit transaction
            mysqli_commit($conn);

            // Clear cart
            $_SESSION['cart'] = [];

            // Redirect to confirmation
            header("Location: confirmation.php?order_id=$order_id");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Failed to process order: " . htmlspecialchars($e->getMessage());
            error_log("Order processing error: " . $e->getMessage() . " | Customer ID: $customer_id | Total: $total | Time: " . date('Y-m-d H:i:s'));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="checkout-confirmation-styles.css">
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
        <section class="checkout-section">
            <div class="checkout-container">
                <a href="shoppingCart.php" class="return-link">← Return to Cart</a>
                <h2>Secure Checkout</h2>
                <div class="progress-bar">
                    <div class="progress-step">
                        <span class="step-number">1</span>
                        <span class="step-label">Cart</span>
                    </div>
                    <div class="progress-step active">
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
                        <p>Add some delicious items to your cart before proceeding to checkout.</p>
                        <a href="products.php" class="browse-button">Browse Products</a>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="error">
                            <?php foreach ($errors as $error): ?>
                                <p><?= htmlspecialchars($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="checkout-grid">
                        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="checkout-form">
                            <div class="form-section">
                                <h3>Contact Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name">First Name*</label>
                                        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($form_data['first_name']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name">Last Name*</label>
                                        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($form_data['last_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email Address*</label>
                                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number*</label>
                                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($form_data['phone']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>Shipping Address</h3>
                                <div class="form-group">
                                    <label for="street_address">Street Address*</label>
                                    <input type="text" id="street_address" name="street_address" value="<?= htmlspecialchars($form_data['street_address']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="apartment">Apartment, Suite, etc. (optional)</label>
                                    <input type="text" id="apartment" name="apartment" value="<?= htmlspecialchars($form_data['apartment']) ?>">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city">City*</label>
                                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($form_data['city']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="state">State/Province*</label>
                                        <select id="state" name="state" required>
                                            <option value="">Select State</option>
                                            <option value="Johor" <?= $form_data['state'] === 'Johor' ? 'selected' : '' ?>>Johor Darul Ta'zim</option>
                                            <option value="Kedah" <?= $form_data['state'] === 'Kedah' ? 'selected' : '' ?>>Kedah Darul Aman</option>
                                            <option value="Kelantan" <?= $form_data['state'] === 'Kelantan' ? 'selected' : '' ?>>Kelantan Darul Naim</option>
                                            <option value="Kuala Lumpur" <?= $form_data['state'] === 'Kuala Lumpur' ? 'selected' : '' ?>>Kuala Lumpur</option>
                                            <option value="Labuan" <?= $form_data['state'] === 'Labuan' ? 'selected' : '' ?>>Labuan</option>
                                            <option value="Malacca" <?= $form_data['state'] === 'Malacca' ? 'selected' : '' ?>>Malacca</option>
                                            <option value="Negeri Sembilan" <?= $form_data['state'] === 'Negeri Sembilan' ? 'selected' : '' ?>>Negeri Sembilan Darul Khusus</option>
                                            <option value="Pahang" <?= $form_data['state'] === 'Pahang' ? 'selected' : '' ?>>Pahang Darul Makmur</option>
                                            <option value="Perlis" <?= $form_data['state'] === 'Perlis' ? 'selected' : '' ?>>Perlis Indera Kayangan</option>
                                            <option value="Perak" <?= $form_data['state'] === 'Perak' ? 'selected' : '' ?>>Perak Darul Ridzuan</option>
                                            <option value="Penang" <?= $form_data['state'] === 'Penang' ? 'selected' : '' ?>>Penang</option>
                                            <option value="Putrajaya" <?= $form_data['state'] === 'Putrajaya' ? 'selected' : '' ?>>Putrajaya</option>
                                            <option value="Selangor" <?= $form_data['state'] === 'Selangor' ? 'selected' : '' ?>>Selangor Darul Ehsan</option>
                                            <option value="Terengganu" <?= $form_data['state'] === 'Terengganu' ? 'selected' : '' ?>>Terengganu Darul Iman</option>
                                            <option value="Sabah" <?= $form_data['state'] === 'Sabah' ? 'selected' : '' ?>>Sabah</option>
                                            <option value="Sarawak" <?= $form_data['state'] === 'Sarawak' ? 'selected' : '' ?>>Sarawak</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="zip_code">ZIP/Postal Code*</label>
                                        <input type="text" id="zip_code" name="zip_code" value="<?= htmlspecialchars($form_data['zip_code']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="country">Country*</label>
                                        <select id="country" name="country" required>
                                            <option value="Malaysia" <?= $form_data['country'] === 'Malaysia' ? 'selected' : '' ?>>Malaysia</option>
                                            <option value="Singapore" <?= $form_data['country'] === 'Singapore' ? 'selected' : '' ?>>Singapore</option>
                                            <option value="Indonesia" <?= $form_data['country'] === 'Indonesia' ? 'selected' : '' ?>>Indonesia</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="delivery_instructions">Delivery Instructions (optional)</label>
                                    <textarea id="delivery_instructions" name="delivery_instructions"><?= htmlspecialchars($form_data['delivery_instructions']) ?></textarea>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>Payment Information</h3>
                                <div class="form-group">
                                    <label>Card Type*</label>
                                    <div class="card-types">
                                        <input type="radio" id="visa" name="card_type" value="Visa" required>
                                        <label for="visa">Visa</label>
                                        <input type="radio" id="mc" name="card_type" value="MasterCard">
                                        <label for="mc">MasterCard</label>
                                        <input type="radio" id="amex" name="card_type" value="Amex">
                                        <label for="amex">Amex</label>
                                        <input type="radio" id="disc" name="card_type" value="Discover">
                                        <label for="disc">Discover</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="card_name">Name on Card*</label>
                                    <input type="text" id="card_name" name="card_name" value="<?= htmlspecialchars($form_data['card_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="card_number">Card Number*</label>
                                    <input type="text" id="card_number" name="card_number" value="<?= htmlspecialchars($form_data['card_number']) ?>" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="expiration_date">Expiration Date* (MM/YY)</label>
                                        <input type="text" id="expiration_date" name="expiration_date" value="<?= htmlspecialchars($form_data['expiration_date']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="cvv">CVV*</label>
                                        <input type="text" id="cvv" name="cvv" value="<?= htmlspecialchars($form_data['cvv']) ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="billing_zip">Billing ZIP Code*</label>
                                    <input type="text" id="billing_zip" name="billing_zip" value="<?= htmlspecialchars($form_data['billing_zip']) ?>" required>
                                </div>
                            </div>
                            <button type="submit" class="place-order-button">Place Order</button>
                        </form>
                        <div class="order-summary">
                            <h3>Order Summary</h3>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="summary-item">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    <div class="item-details">
                                        <span><?= htmlspecialchars($item['name']) ?></span>
                                        <span>Qty: <?= $item['quantity'] ?></span>
                                        <span>RM <?= number_format($item['total'], 2) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="summary-totals">
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