<?php
session_start();

// Include database connection (MySQLi)
require_once 'db_connection.php';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];
    
    // Check admin table first
    $query = "SELECT id, name, password, role FROM admins WHERE email = '$email' AND status = 'Active'";
    $result = mysqli_query($conn, $query);
    $admin = mysqli_fetch_assoc($result);
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['role'] = $admin['role'];
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Check customer table
    $query = "SELECT id, name, password FROM customers WHERE email = '$email' AND status = 'active'";
    $result = mysqli_query($conn, $query);
    $customer = mysqli_fetch_assoc($result);
    
    if ($customer && password_verify($password, $customer['password'])) {
        $_SESSION['user_id'] = $customer['id'];
        $_SESSION['user_name'] = $customer['name'];
        $_SESSION['user_type'] = 'customer';
        header("Location: index.php");
        exit();
    }
    
    $error = "Invalid email or password";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login-form-styles.css">
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
        <section class="login-section">
            <div class="login-container">
                <h2>Welcome Back!</h2>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit">Login</button>
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </form>
                <p class="signup-link">Don't have an account? <a href="signup.php">Sign Up</a></p>
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