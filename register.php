<?php
session_start();
require_once 'db_connection.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms_agreed = isset($_POST['terms']) ? 1 : 0;

    $errors = [];

    // Validation
    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First and Last Name are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $errors[] = "Invalid phone number (10-15 digits).";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (!$terms_agreed) {
        $errors[] = "You must agree to the Terms of Service.";
    }

    // Check if email already exists
    $query = "SELECT id FROM customers WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email is already registered.";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $name = $first_name . ' ' . $last_name;
        $status = 'active';
        $created_at = date('Y-m-d H:i:s');

        // Use prepared statement for INSERT
        $query = "INSERT INTO customers (name, email, phone, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $phone, $hashed_password, $status, $created_at);

        // Check connection and execute
        if (!$stmt) {
            // Attempt to reconnect if the connection is lost
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $phone, $hashed_password, $status, $created_at);
        }

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Registration failed: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="register-form-styles.css">
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
        <section class="register-section">
            <div class="register-container">
                <h2>Join BakeEase Today</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms" name="terms">
                        <label for="terms">I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a></label>
                    </div>
                    <button type="submit">Create Account</button>
                </form>
                <p class="login-link">Already have an account? <a href="login.php">Login</a></p>
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