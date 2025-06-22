<?php
session_start();
require_once 'db_connection.php';

// Initialize variables
$errors = [];
$success = '';
$form_data = [
    'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
    'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
    'message' => isset($_POST['message']) ? trim($_POST['message']) : ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    if (empty($form_data['name'])) {
        $errors[] = "Name is required.";
    }
    if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    if (empty($form_data['message'])) {
        $errors[] = "Message is required.";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $customer_id = isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer' ? $_SESSION['user_id'] : null;
        $query = "INSERT INTO contact_messages (name, email, message, customer_id) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssi", $form_data['name'], $form_data['email'], $form_data['message'], $customer_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Message sent successfully! We'll get back to you soon.";
                $form_data = ['name' => '', 'email' => '', 'message' => ''];
            } else {
                $errors[] = "Failed to send message. Please try again.";
                error_log("Contact form error: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database error. Please try again.";
            error_log("Failed to prepare contact query: " . mysqli_error($conn));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - BakeEase Bakery</title>
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
        <section class="contact-section">
            <div class="contact-container">
                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success">
                        <p><?= htmlspecialchars($success) ?></p>
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="contact-form">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($form_data['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message" required><?= htmlspecialchars($form_data['message']) ?></textarea>
                    </div>
                    <button type="submit">Send Message</button>
                </form>
            </div>
        </section>
        <section class="map-section">
            <h3>Find Us:</h3>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.8150700207036!2d103.76147247404707!3d1.2832063618795024!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31da11238a9b451f%3A0xe9a8f621b0f8b0a6!2sJohor%20Bahru%2C%20Johor%2C%20Malaysia!5e0!3m2!1sen!2sus!4v1697051234567!5m2!1sen!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
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