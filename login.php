<?php
session_start();

// Database connection
include("db_connection.php");

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Check admin table first
    $stmt = $conn->prepare("SELECT id, name, password, role FROM admins WHERE email = :email AND status = 'Active'");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['role'] = $admin['role'];
        header("Location: admin_dashboard.php"); // Redirect to admin dashboard
        exit();
    }
    
    // Check customer table
    $stmt = $conn->prepare("SELECT id, name, password FROM customers WHERE email = :email AND status = 'active'");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer && password_verify($password, $customer['password'])) {
        $_SESSION['user_id'] = $customer['id'];
        $_SESSION['user_name'] = $customer['name'];
        $_SESSION['user_type'] = 'customer';
        header("Location: index.php"); // Redirect to home page
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
    <title>Login</title>
    <style>
        /* Basic CSS to maintain original design */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            background-color: #333;
            color: white;
            padding: 10px 0;
            text-align: center;
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .nav-links, .cart, .account {
            position: relative;
            display: inline-block;
        }
        .dropdown {
            display: none;
            position: absolute;
            background-color: #fff;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
        }
        .dropdown a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .dropdown a:hover {
            background-color: #f1f1f1;
        }
        .nav-links:hover .dropdown,
        .cart:hover .dropdown,
        .account:hover .dropdown {
            display: block;
        }
        .main-content {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .main-content h2 {
            margin-bottom: 20px;
        }
        .main-content input[type="email"],
        .main-content input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .main-content input[type="submit"] {
            background-color: #333;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .main-content input[type="submit"]:hover {
            background-color: #555;
        }
        .main-content a {
            color: #333;
            text-decoration: none;
        }
        .main-content a:hover {
            text-decoration: underline;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="nav-links">
                <span>☰</span>
                <div class="dropdown">
                    <a href="index.php">Home</a>
                    <a href="products.php">Products</a>
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact</a>
                </div>
            </div>
            <h1>Login</h1>
            <div class="cart">
                <span>🛒</span>
                <div class="dropdown">
                    <a href="cart.php">View Cart</a>
                    <a href="checkout.php">Checkout</a>
                </div>
            </div>
            <div class="account">
                <span>👤</span>
                <div class="dropdown">
                    <a href="signup.php">Sign Up</a>
                    <a href="login.php">Login</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="main-content">
        <h2>Welcome Back!</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            
            <input type="submit" value="Login">
            <a href="forgot_password.php">Forgot Password?</a>
        </form>
        
        <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
    </div>

    <footer>
        <p>© 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>
</body>
</html>