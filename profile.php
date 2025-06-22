<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$query = "SELECT name, email, phone, created_at FROM customers WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    // Handle case where user not found (e.g., deleted account)
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="header-bar">
            <div class="logo-left">
                <a href="index.php">
                    <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline">
                </a>
                <div class="search-center">
                    <form class="search-form" action="products.php" method="get">
                        <input type="text" name="search" placeholder="Welcome To BakeEase">
                        <button type="submit">Search</button>
                    </form>
                </div>
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
                        <a href="cart.php">View Cart</a>
                        <a href="checkout.php">Checkout</a>
                    </div>
                </div>
                <div class="profile-dropdown">
                    <span class="profile-icon" id="profileToggle">👤</span>
                    <div class="dropdown-menu" id="profileMenu">
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer'): ?>
                            <a href="profile.php">Profile</a>
                            <a href="profile.php?logout=1">Logout</a>
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
        <section class="profile-section">
            <div class="profile-container">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <div class="profile-details">
                    <div class="profile-field">
                        <span class="field-label">Full Name:</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Email:</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Phone:</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Joined:</span>
                        <span class="field-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="edit_profile.php" class="action-button">Edit Profile</a>
                    <a href="profile.php?logout=1" class="action-button logout-button">Logout</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>© 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>

    <script>
        const profileToggle = document.getElementById("profileToggle");
        const profileMenu = document.getElementById("profileMenu");
        const cartToggle = document.getElementById("cartToggle");
        const cartMenu = document.getElementById("cartMenu");
        const navToggle = document.getElementById("navToggle");
        const navMenu = document.getElementById("navMenu");

        profileToggle.addEventListener("click", () => {
            profileMenu.classList.toggle("show");
            cartMenu.classList.remove("show");
            navMenu.classList.remove("show");
        });

        cartToggle.addEventListener("click", () => {
            cartMenu.classList.toggle("show");
            profileMenu.classList.remove("show");
            navMenu.classList.remove("show");
        });

        navToggle.addEventListener("click", () => {
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
    </script>
</body>
</html>