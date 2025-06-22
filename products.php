<?php
session_start();
require_once 'db_connection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_query = $search ? "WHERE name LIKE ? OR description LIKE ? OR category LIKE ?" : "";
$search_param = $search ? "%$search%" : "";
$query = "SELECT id, name, description, price, image, category FROM products $search_query ORDER BY category, name";
$stmt = mysqli_prepare($conn, $query);
if ($search) {
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Products - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="products-styles.css">
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
                        <input type="text" name="search" placeholder="Search Products" value="<?= htmlspecialchars($search) ?>">
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
        <?php
        $currentCategory = '';
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['category'] !== $currentCategory) {
                if ($currentCategory !== '') {
                    echo '</section>';
                }
                $currentCategory = $row['category'];
                echo '<h2 style="padding: 20px; color: #5a3921;">' . htmlspecialchars($currentCategory) . '</h2>';
                echo '<section class="product-grid">';
            }
            ?>
            <article class="product-card">
                <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" />
                <h4><?= htmlspecialchars($row['name']) ?></h4>
                <p><?= htmlspecialchars($row['description']) ?></p>
                <p><strong>RM <?= number_format($row['price'], 2) ?></strong></p>
                <a href="product-detail.php?id=<?= $row['id'] ?>" class="button">View Details</a>
                <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                    <input type="number" name="quantity" value="1" min="1" max="99" class="quantity-input">
                    <button type="submit" class="add-to-cart-button">Add to Cart</button>
                </form>
            </article>
            <?php
        }
        if ($currentCategory !== '') {
            echo '</section>';
        }
        if (mysqli_num_rows($result) === 0) {
            echo '<p style="padding: 20px; text-align: center;">No products found.</p>';
        }
        ?>
    </main>

    <footer>
        <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
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

<?php
mysqli_close($conn);
?>