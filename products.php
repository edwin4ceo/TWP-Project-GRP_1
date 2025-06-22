<?php
session_start();
require_once 'db_connection.php';

// Get search and category parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) && $_GET['category'] !== 'all' ? trim($_GET['category']) : '';

// Debug: Log incoming parameters
error_log("Products page: search='$search', category='$category'");

// Build product query
$search_query = '';
$params = [];
$types = '';
if ($search && $category) {
    $search_query = "WHERE (name LIKE ? OR description LIKE ? OR category LIKE ?) AND category = ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $category];
    $types = "ssss";
} elseif ($search) {
    $search_query = "WHERE name LIKE ? OR description LIKE ? OR category LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
} elseif ($category) {
    $search_query = "WHERE category = ?";
    $params = [$category];
    $types = "s";
}
$query = "SELECT id, name, description, price, image, category FROM products $search_query ORDER BY name";
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $num_rows = mysqli_num_rows($result);
    error_log("Product query executed: $query, rows=$num_rows");
} else {
    error_log("Failed to prepare product query: " . mysqli_error($conn));
    $num_rows = 0;
}

// Fetch distinct categories
$categories = [];
$cat_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
$cat_result = mysqli_query($conn, $cat_query);
if ($cat_result) {
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $row['category'];
    }
    mysqli_free_result($cat_result);
    error_log("Categories fetched: " . implode(", ", $categories));
} else {
    error_log("Failed to fetch categories: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Products - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
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
                    <form class="search-form" action="products.php" method="get" id="productFilterForm">
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
                            <a href="order-history.php">Order History</a>
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
        <section class="products-section">
            <div class="products-container">
                <form action="products.php" method="get" id="categoryFilterForm">
                    <div class="category-filter">
                        <label for="category-select">Filter by Category:</label>
                        <select id="category-select" name="category">
                            <option value="all" <?= $category === '' ? 'selected' : '' ?>>All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    </div>
                </form>
                <?php if ($num_rows === 0): ?>
                    <div class="no-products">
                        <p>No products found.</p>
                        <a href="products.php" class="clear-filters">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
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
                        <?php endwhile; ?>
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
        const categorySelect = document.getElementById("category-select");
        const categoryFilterForm = document.getElementById("categoryFilterForm");

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

        profileMenu.addEventListener("click", (e) => e.stopPropagation());
        cartMenu.addEventListener("click", (e) => e.stopPropagation());
        navMenu.addEventListener("click", (e) => e.stopPropagation());

        // Auto-submit category filter form on change
        categorySelect.addEventListener("change", () => {
            console.log("Category selected: " + categorySelect.value);
            categoryFilterForm.submit();
        });
    });
    </script>
</body>
</html>

<?php
if ($stmt) {
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
?>