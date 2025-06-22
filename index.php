<?php
require_once 'db_connection.php';

// Fetch popular products (limiting to 3 as shown in the original HTML)
$query = "SELECT id, name, description, price, image FROM products WHERE category = 'Cake' LIMIT 3";
$result = mysqli_query($conn, $query);
$products = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    mysqli_free_result($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeEase Bakery</title>
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
        .search-bar {
            text-align: center;
            padding: 10px;
        }
        .search-bar input {
            padding: 5px;
            width: 200px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .hero {
            text-align: center;
            padding: 50px 20px;
            background-color: #f9f9f9;
        }
        .hero h1 {
            margin-bottom: 20px;
        }
        .hero p {
            margin-bottom: 20px;
        }
        .hero a {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
        }
        .hero a:hover {
            background-color: #555;
        }
        .popular {
            max-width: 1200px;
            margin: 50px auto;
            text-align: center;
        }
        .popular h2 {
            margin-bottom: 20px;
        }
        .product-grid {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .product-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 250px;
            padding: 20px;
            text-align: center;
        }
        .product-card img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .product-card h3 {
            margin: 10px 0;
        }
        .product-card p {
            margin: 10px 0;
            color: #666;
        }
        .product-card .price {
            font-weight: bold;
            color: #333;
        }
       ，唐
        .product-card a {
            display: inline-block;
            margin-top: 10px;
            background-color: #333;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 4px;
        }
        .product-card a:hover {
            background-color: #555;
        }
        .features {
            text-align: center;
            padding: 20px;
            background-color: #fff;
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
            <div class="search-bar">
                <input type="text" placeholder="Search">
            </div>
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

    <section class="hero">
        <h1>Freshly Baked Goodness, Just a Click Away!</h1>
        <p>At BakeEase, we deliver delicious breads, cakes, and pastries straight to your doorstep. Browse our menu and order your favorites today!</p>
        <a href="products.php">Explore Our Menu</a>
    </section>

    <section class="popular">
        <h2>Popular Picks</h2>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <p class="price">RM <?php echo number_format($product['price'], 2); ?></p>
                    <a href="product_details.php?id=<?php echo $product['id']; ?>">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="features">
        <h2>Why Choose BakeEase?</h2>
        <p>✅ Baked fresh daily</p>
        <p>✅ Premium ingredients</p>
        <p>✅ Fast delivery in your area</p>
    </section>

    <footer>
        <p>© 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
// Close database connection
mysqli_close($conn);
?>